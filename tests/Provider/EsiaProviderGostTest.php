<?php

namespace Ekapusta\OAuth2Esia\Tests\Provider;

use Ekapusta\OAuth2Esia\Provider\EsiaProvider;
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

class EsiaProviderGostTest extends TestCase
{
    private $redirectUri;

    /**
     * @var \Ekapusta\OAuth2Esia\Interfaces\Security\SignerInterface
     */
    private $signer;

    private $provider;

    protected function setUp()
    {
        $formatter = new MessageFormatter(MessageFormatter::DEBUG);
        $logger = Middleware::log(Factory::createLogger('esia-http'), $formatter, LogLevel::DEBUG);
        $httpStack = HandlerStack::create();
        $httpStack->push($logger, 'logger');

        $this->redirectUri = 'https://system.dev/esia/auth';

        $signerClass = getenv('ESIA_SIGNER_CLASS') ?: OpensslCli::class;
        $certificate = getenv('ESIA_CERTIFICATE') ?: 'ekapusta.gost2012.test.cer';
        $privateKey = getenv('ESIA_PRIVATE_KEY') ?: 'ekapusta.gost2012.test.key';

        $this->signer = new $signerClass(
            Factory::KEYS.$certificate,
            Factory::KEYS.$privateKey,
            null,
            getenv('ESIA_CLIENT_OPENSSL_TOOL_PATH') ?: 'openssl'
        );
        $this->provider = new EsiaProvider([
            'clientId' => 'MNSV36',
            'redirectUri' => $this->redirectUri,
            'remoteUrl' => 'https://esia-portal1.test.gosuslugi.ru',
            'remoteCertificatePath' => EsiaProvider::RESOURCES.'esia.gost.test.cer',
            'defaultScopes' => [
                // needed for authenticating
                'openid',

                // root entity
                'fullname',
                'birthdate',
                'gender',
                'snils',
                'inn',
                'birthplace',

                // docs collections
                'id_doc',
                'drivers_licence_doc',

                // vehicles collection
                'vehicles',

                // contacts collection
                'email',
                'mobile',
                'contacts',
            ],
        ], [
            'httpClient' => new HttpClient(['handler' => $httpStack]),
            'signer' => $this->signer,
            'remoteSigner' => new \Ekapusta\OAuth2Esia\Security\RemoteSigner\OpensslCli(
                getenv('ESIA_CLIENT_OPENSSL_TOOL_PATH') ?: 'openssl'
            ),
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
        parse_str(parse_url($authUrl, PHP_URL_QUERY), $url);

        $this->assertInternalType('array', $url);
        $this->assertNull(isset($url['error_description']) ? $url['error_description'] : null);
        $this->assertArrayHasKey('code', $url);

        $accessToken = $this->provider->getAccessToken('authorization_code', [
            'code' => $url['code'],
        ]);

        return $accessToken->getToken();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Access token is invalid
     */
    public function testAccessTokenInvalid()
    {
        $collaborators = [
            'signer' => $this->signer,
        ];
        $provider = $this->getMockBuilder(EsiaProvider::class)->setConstructorArgs([[], $collaborators])->setMethods(['getResponse'])->getMock();
        $response = new Response(200, [], '{"access_token": "'.file_get_contents(__DIR__.'/../Fixtures/expired.token.txt').'"}');
        $provider->expects($this->once())->method('getResponse')->willReturn($response);

        $provider->getAccessToken('authorization_code', ['code' => 'some code']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Signer is not provided!
     */
    public function testSignerIsRequired()
    {
        new EsiaProvider();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Remote URL is not provided!
     */
    public function testRemoteUrlIsRequired()
    {
        new EsiaProvider(['remoteUrl' => ''], ['signer' => $this->signer]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Remote certificate is not provided!
     */
    public function testRemoteCertificateIsRequired()
    {
        new EsiaProvider(['remoteCertificatePath' => ''], ['signer' => $this->signer]);
    }

    /**
     * @depends testAccessTokenRequested
     */
    public function testPersonGeneralInfoRequested($accessToken)
    {
        $accessToken = new EsiaAccessToken(['access_token' => $accessToken]);

        $resourceOwner = $this->provider->getResourceOwner($accessToken);

        $this->assertEquals('1000404446', $resourceOwner->getId());

        $info = $resourceOwner->toArray();

        $this->assertInternalType('array', $info);
        $this->assertArrayHasKey('firstName', $info);

        $this->assertEquals('Имя006', $info['firstName']);

        Factory::createLogger('esia-provider')->warning('Person info', $info);
    }

    /**
     * @expectedException \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     * @expectedExceptionMessage Unauthorized
     * @expectedExceptionCode 401
     */
    public function testPersonGeneralInfoFailsAsOfBadSignedToken()
    {
        $accessToken = Factory::createAccessToken(Factory::KEYS.'ekapusta.rsa.test.key');

        $this->provider->getResourceOwner($accessToken);
    }
}
