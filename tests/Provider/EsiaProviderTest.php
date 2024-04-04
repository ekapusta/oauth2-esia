<?php

namespace Ekapusta\OAuth2Esia\Tests\Provider;

use Ekapusta\OAuth2Esia\Provider\EsiaProvider;
use Ekapusta\OAuth2Esia\Security\JWTSigner\OpenSslCliJwtSigner;
use Ekapusta\OAuth2Esia\Security\Signer\OpensslCli;
use Ekapusta\OAuth2Esia\Tests\Factory;
use Ekapusta\OAuth2Esia\Token\EsiaAccessToken;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class EsiaProviderTest extends TestCase
{
    private $redirectUri;

    /**
     * @var \Ekapusta\OAuth2Esia\Interfaces\Security\SignerInterface
     */
    private $signer;

    private $provider;

    protected function setUp(): void
    {
        $formatter = new MessageFormatter(MessageFormatter::DEBUG);
        $logger = Middleware::log(Factory::createLogger('esia-http'), $formatter, LogLevel::DEBUG);
        $httpStack = HandlerStack::create();
        $httpStack->push($logger, 'logger');

        $this->redirectUri = 'https://system.dev/esia/auth';

        $clientId = getenv('ESIA_CLIENT_ID') ?: '500201';
        $signerClass = getenv('ESIA_SIGNER_CLASS') ?: OpensslCli::class;
        $certificate = getenv('ESIA_CERTIFICATE') ?: 'ekapusta.gost2012.test.cer';
        $privateKey = getenv('ESIA_PRIVATE_KEY') ?: 'ekapusta.gost2012.test.key';
        $remoteSignerClass = getenv('ESIA_REMOTE_SIGNER_CLASS') ?: OpenSslCliJwtSigner::class;
        $remotePublicKey = getenv('ESIA_REMOTE_PUBLIC_KEY') ?: 'esia.gost.test.public.key';
        $remoteAlgorythmId = getenv('ESIA_REMOTE_ALGORYTHM_ID') ?: 'GOST3410_2012_256';

        $this->signer = new $signerClass(
            Factory::KEYS.$certificate,
            Factory::KEYS.$privateKey,
            null,
            getenv('ESIA_CLIENT_OPENSSL_TOOL_PATH') ?: 'openssl',
            '-engine gost'
        );
        $this->provider = new EsiaProvider([
            'clientId' => $clientId,
            'redirectUri' => $this->redirectUri,
            'remoteUrl' => 'https://esia-portal1.test.gosuslugi.ru',
            'remotePublicKey' => EsiaProvider::RESOURCES.$remotePublicKey,
            'defaultScopes' => [
                // needed for authenticating
                'openid',

                // root entity
                'fullname',
//                 'birthdate',
//                 'gender',
                'snils',
//                 'inn',
//                 'birthplace',

//                 // docs collections
                'id_doc',
//                 'drivers_licence_doc',

//                 // vehicles collection
//                 'vehicles',

//                 // contacts collection
//                 'email',
//                 'mobile',
//                 'contacts',
            ],
        ], [
            'httpClient' => new HttpClient(['handler' => $httpStack]),
            'signer' => $this->signer,
            'remoteSigner' => new $remoteSignerClass(getenv('ESIA_CLIENT_OPENSSL_TOOL_PATH') ?: 'openssl', $remoteAlgorythmId),
        ]);
    }

    public function testLoginRequestCreated()
    {
        $loginUrl = $this->provider->getAuthorizationUrl();

        $this->assertStringStartsWith('https://esia-portal1.test.gosuslugi.ru/aas/oauth2/ac', $loginUrl);

        return $loginUrl;
    }

    /**
     * @depends testLoginRequestCreated
     */
    public function testUserLoggedInToEsia($loginUrl)
    {
        $bot = Factory::createAuthenticationBot();

        $maxLoginAttempts = getenv('ESIA_LOGIN_ATTEMPTS') ?: 1;
        for ($loginAttemps = 0; $loginAttemps < $maxLoginAttempts; ++$loginAttemps) {
            $authUrl = $bot->login($loginUrl, $this->redirectUri);
            if ($authUrl) {
                break;
            }
            $loginUrl = $this->provider->getAuthorizationUrl();
        }

        $authUrl = filter_var($authUrl, FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE);
        $this->assertNotNull($authUrl, 'Automatic login to ESIA failed. Try again.');

        return $authUrl;
    }

    /**
     * @depends testUserLoggedInToEsia
     */
    public function testAccessTokenRequested($authUrl)
    {
        $url = null;
        parse_str(parse_url($authUrl, PHP_URL_QUERY), $url);

        $this->assertInternalType('array', $url);
        $this->assertArrayHasKey('code', $url);

        $accessToken = $this->provider->getAccessToken('authorization_code', [
            'code' => $url['code'],
        ]);

        return $accessToken;
    }

    public function testAccessTokenInvalid()
    {
        $this->expectExceptionMessage("Access token is invalid");
        $this->expectException(\InvalidArgumentException::class);
        $collaborators = [
            'signer' => $this->signer,
        ];
        $provider = $this->getMockBuilder(EsiaProvider::class)->setConstructorArgs([[], $collaborators])->onlyMethods(['getResponse'])->getMock();
        $response = new Response(200, [], '{"access_token": "'.file_get_contents(__DIR__.'/../Fixtures/expired.token.txt').'"}');
        $provider->expects($this->once())->method('getResponse')->willReturn($response);

        $provider->getAccessToken('authorization_code', ['code' => 'some code']);
    }

    public function testSignerIsRequired()
    {
        $this->expectExceptionMessage("Signer is not provided!");
        $this->expectException(\InvalidArgumentException::class);
        new EsiaProvider();
    }

    public function testRemoteUrlIsRequired()
    {
        $this->expectExceptionMessage("Remote URL is not provided!");
        $this->expectException(\InvalidArgumentException::class);
        new EsiaProvider(['remoteUrl' => ''], ['signer' => $this->signer]);
    }

    public function testRemotePublicKeyIsRequired()
    {
        $this->expectExceptionMessage("Remote public key is not provided!");
        $this->expectException(\InvalidArgumentException::class);
        new EsiaProvider(['remotePublicKey' => ''], ['signer' => $this->signer]);
    }

    /**
     * Provider uses remoteCertificate as alias of remotePublicKey.
     */
    public function testRemoteCertificateIsRenamedToPublicKey()
    {
        new EsiaProvider(['remoteCertificatePath' => '/dev/null', 'remotePublicKey' => ''], ['signer' => $this->signer]);

        $this->assertTrue(true);
    }

    /**
     * @depends testAccessTokenRequested
     */
    public function testPersonGeneralInfoRequested(EsiaAccessToken $accessToken)
    {
        $resourceOwner = $this->provider->getResourceOwner($accessToken);

        $this->assertGreaterThan(100000000, (int) $resourceOwner->getId());

        $info = $resourceOwner->toArray();

        $this->assertInternalType('array', $info);
        $this->assertArrayHasKey('firstName', $info);

        $this->assertRegExp('/^[А-Я][а-я0-9]+$/u', $info['firstName']);

        Factory::createLogger('esia-provider')->warning('Person info', $info);
    }

    public function testPersonGeneralInfoFailsAsOfBadSignedToken()
    {
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage("Unauthorized");
        $this->expectException(\League\OAuth2\Client\Provider\Exception\IdentityProviderException::class);
        $accessToken = Factory::createAccessToken(
            Factory::KEYS.'ekapusta.rsa.test.key',
            Factory::KEYS.'ekapusta.rsa.test.cer'
        );

        $this->provider->getResourceOwner($accessToken);
    }
}
