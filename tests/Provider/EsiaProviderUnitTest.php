<?php

namespace Ekapusta\OAuth2Esia\Tests\Provider;

use Ekapusta\OAuth2Esia\Provider\EsiaProvider;
use Ekapusta\OAuth2Esia\Security\JWTSigner\OpenSslCliJwtSigner;
use Ekapusta\OAuth2Esia\Security\Signer\OpensslCli;
use Ekapusta\OAuth2Esia\Tests\Factory;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

class EsiaProviderUnitTest extends TestCase
{
    protected $redirectUri;

    /**
     * @var \Ekapusta\OAuth2Esia\Interfaces\Security\SignerInterface
     */
    protected $signer;

    protected $provider;

    protected function setUp(): void
    {
        $formatter = new MessageFormatter(MessageFormatter::DEBUG);
        $logger = Middleware::log(Factory::createLogger('esia-http'), $formatter, LogLevel::DEBUG);
        $httpStack = HandlerStack::create();
        $httpStack->push($logger, 'logger');

        $this->redirectUri = 'https://system.dev/esia/auth';

        $this->signer = new OpensslCli(
            Factory::KEYS.'ekapusta.gost2012.test.cer',
            Factory::KEYS.'ekapusta.gost2012.test.key',
            null,
            getenv('ESIA_CLIENT_OPENSSL_TOOL_PATH') ?: 'openssl',
            '-engine gost'
        );
        $this->provider = new EsiaProvider([
            'clientId' => '500201',
            'redirectUri' => $this->redirectUri,
            'remoteUrl' => 'https://esia-portal1.test.gosuslugi.ru',
            'remotePublicKey' => EsiaProvider::RESOURCES.'esia.gost.test.public.key',
            'defaultScopes' => [
                // needed for authenticating
                'openid',

                // root entity
                'fullname',
                'snils',

                // docs collections
                'id_doc',
            ],
        ], [
            'httpClient' => new HttpClient(['handler' => $httpStack]),
            'signer' => $this->signer,
            'remoteSigner' => new OpenSslCliJwtSigner(getenv('ESIA_CLIENT_OPENSSL_TOOL_PATH') ?: 'openssl', 'GOST3410_2012_256'),
        ]);
    }

    public function testLoginRequestCreated()
    {
        $loginUrl = $this->provider->getAuthorizationUrl();

        $this->assertStringStartsWith('https://esia-portal1.test.gosuslugi.ru/aas/oauth2/ac', $loginUrl);
    }

    public function testResourceOwnerDetailsUrl()
    {
        $esiaToken = Factory::createAccessToken(
            Factory::KEYS.'ekapusta.rsa.test.key',
            Factory::KEYS.'ekapusta.rsa.test.public.key'
        );
        $detailsUrl = $this->provider->getResourceOwnerDetailsUrl($esiaToken);

        $this->assertEquals('https://esia-portal1.test.gosuslugi.ru/rs/prns/1?embed=(contacts.elements)', $detailsUrl);
    }

    public function testAccessTokenInvalid()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access token is invalid');

        $provider = $this->getMockBuilder(EsiaProvider::class)->setConstructorArgs([[], ['signer' => $this->signer]])->setMethods(['getResponse'])->getMock();
        $response = new Response(200, [], '{"access_token": "'.file_get_contents(__DIR__.'/../Fixtures/expired.token.txt').'"}');
        $provider->expects($this->once())->method('getResponse')->willReturn($response);

        $provider->getAccessToken('authorization_code', ['code' => 'some code']);
    }

    public function testCheckResponseThrowsException()
    {
        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('Some remote error');

        $provider = $this->getMockBuilder(EsiaProvider::class)->setConstructorArgs([[], ['signer' => $this->signer]])->setMethods(['getResponse'])->getMock();
        $response = new Response(400, [], '{"error": "Some remote error"}');
        $provider->expects($this->once())->method('getResponse')->willReturn($response);

        $request = new Request('get', 'http://some.site/');
        $provider->getParsedResponse($request);
    }

    public function testPersonGeneralInfoRequested()
    {
        $provider = $this->getMockBuilder(EsiaProvider::class)->setConstructorArgs([[], ['signer' => $this->signer]])->setMethods(['getResponse'])->getMock();
        $response = new Response(200, [], '{"stateFacts":["EntityRoot"],"firstName":"Сергейъ","lastName":"Ивановъ","middleName":"Александровичъ","trusted":true,"citizenship":"RUS","snils":"000-000-600 15","inn":"376864406601","updatedOn":1764854668,"documents":{"stateFacts":["hasSize"],"size":1,"eTag":"8D6F0DCE1B03CA1EC0437278F7908513C195619B","elements":[{"stateFacts":["EntityRoot"],"id":2081561,"type":"RF_PASSPORT","vrfStu":"VERIFIED","series":"7899","number":"654323","issueDate":"03.12.2025","issueId":"198001","issuedBy":"НЕ МЕНЯТЬ ДАННЫЕ 04.12.2025 г., ПОЛЬЗОВАТЕЛЬ ИСПОЛЬЗУЕТСЯ ДЛЯ ДЕМОНСТРАЦИИ","eTag":"4E1D4C4522E6DE952D5713FD86E22B97E2E03498"}]},"rfgUOperatorCheck":false,"status":"REGISTERED","verifying":false,"rIdDoc":2081561,"containsUpCfmCode":false,"kidAccCreatedByParent":false,"eTag":"8300117EDF6FBE726FB5F4AEAD1E8FA72BE81D6C"}');
        $provider->expects($this->once())->method('getResponse')->willReturn($response);

        $esiaToken = Factory::createAccessToken(
            Factory::KEYS.'ekapusta.rsa.test.key',
            Factory::KEYS.'ekapusta.rsa.test.public.key'
        );
        $resourceOwner = $provider->getResourceOwner($esiaToken);

        $this->assertEquals(1, (int) $resourceOwner->getId());

        $info = $resourceOwner->toArray();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('firstName', $info);

        $this->assertRegExp('/^[А-Я][а-я0-9]+$/u', $info['firstName']);
    }

    public function testSignerIsRequired()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Signer is not provided!');

        new EsiaProvider();
    }

    public function testRemoteUrlIsRequired()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Remote URL is not provided!');

        new EsiaProvider(['remoteUrl' => ''], ['signer' => $this->signer]);
    }

    public function testRemotePublicKeyIsRequired()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Remote public key is not provided!');

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
}
