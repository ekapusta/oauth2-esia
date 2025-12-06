<?php

namespace Ekapusta\OAuth2Esia\Tests\Provider;

use Ekapusta\OAuth2Esia\Tests\Factory;
use Ekapusta\OAuth2Esia\Token\EsiaAccessToken;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class EsiaProviderTest extends EsiaProviderUnitTest
{
    public function testUserLoggedInToEsia()
    {
        $loginUrl = $this->provider->getAuthorizationUrl();

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

        $this->assertIsArray($url);
        $this->assertArrayHasKey('code', $url);

        $accessToken = $this->provider->getAccessToken('authorization_code', [
            'code' => $url['code'],
        ]);

        return $accessToken;
    }

    /**
     * @depends testAccessTokenRequested
     */
    public function testPersonGeneralInfoRequested(EsiaAccessToken $accessToken)
    {
        $resourceOwner = $this->provider->getResourceOwner($accessToken);

        $this->assertGreaterThan(100000000, (int) $resourceOwner->getId());

        $info = $resourceOwner->toArray();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('firstName', $info);

        $this->assertMatchesRegularExpression('/^[А-Я][а-я0-9]+$/u', $info['firstName']);

        Factory::createLogger('esia-provider')->warning('Person info', $info);
    }

    public function testPersonGeneralInfoFailsAsOfBadSignedToken()
    {
        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('Unauthorized');
        $this->expectExceptionCode(401);

        $accessToken = Factory::createSha256AccessToken(
            Factory::KEYS.'ekapusta.rsa.test.key',
            Factory::KEYS.'ekapusta.rsa.test.cer'
        );

        $this->provider->getResourceOwner($accessToken);
    }
}
