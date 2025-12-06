<?php

namespace Ekapusta\OAuth2Esia\Tests;

use Bramus\Monolog\Formatter\ColoredLineFormatter;
use Bramus\Monolog\Formatter\ColorSchemes\TrafficLight;
use Ekapusta\OAuth2Esia\Provider\EsiaProvider;
use Ekapusta\OAuth2Esia\Security\JWTSigner\OpenSslCliJwtSigner;
use Ekapusta\OAuth2Esia\Token\EsiaAccessToken;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class Factory
{
    const KEYS = EsiaProvider::RESOURCES;

    /**
     * @return LoggerInterface
     */
    public static function createLogger($channel = 'esia')
    {
        if (!in_array('--debug', $_SERVER['argv'])) {
            return new Logger($channel, [new NullHandler()]);
        }

        $logger = new Logger($channel);

        $formatter = new ColoredLineFormatter(new TrafficLight());
        $formatter->allowInlineLineBreaks();
        $formatter->ignoreEmptyContextAndExtra();

        $handler = (new StreamHandler('php://stderr'))->setFormatter($formatter);
        $logger->pushHandler($handler);

        return $logger;
    }

    /**
     * @return AuthenticationBot
     */
    public static function createAuthenticationBot()
    {
        $bot = new AuthenticationBot(
            'EsiaTest015@yandex.ru',
            '11111111',
            !getenv('DISPLAY'),
            'post' == getenv('ESIA_CLIENT_AUTH_METHOD')
        );
        $bot->setLogger(self::createLogger('authentication-bot'));

        return $bot;
    }

    /**
     * @return EsiaAccessToken
     */
    public static function createAccessToken($privateKeyPath, $publicKeyPath, Signer $signer)
    {
        $builder = new Builder();
        $isFresh = method_exists($builder, 'issuedAt');
        $key = new Key(file_get_contents($privateKeyPath));

        if ($isFresh) {
            $now = new \DateTimeImmutable();
            $hourLater = new \DateTimeImmutable('+1 hour');
            $builder->issuedAt($now);
            $builder->canOnlyBeUsedAfter($now);
            $builder->expiresAt($hourLater);
            $builder->withClaim('urn:esia:sbj_id', 1);
            $builder->withClaim('scope', 'one?oid=123 two?oid=456 three?oid=789 contacts?oid=999');
        } else {
            $now = time();
            $hourLater = $now + 3600;
            $builder->setIssuedAt($now);
            $builder->setNotBefore($now);
            $builder->setExpiration($hourLater);
            $builder->set('urn:esia:sbj_id', 1);
            $builder->set('scope', 'one?oid=123 two?oid=456 three?oid=789 contacts?oid=999');
            $builder->sign($signer, $key);
        }

        $accessToken = $builder->getToken($signer, $key);

        return new EsiaAccessToken(['access_token' => (string) $accessToken], $publicKeyPath, $signer);
    }

    /**
     * @return EsiaAccessToken
     */
    public static function createSha256AccessToken($privateKeyPath, $publicKeyPath)
    {
        return self::createAccessToken($privateKeyPath, $publicKeyPath, new Sha256());
    }

    /**
     * @return EsiaAccessToken
     */
    public static function createGostAccessToken($privateKeyPath, $publicKeyPath)
    {
        return self::createAccessToken($privateKeyPath, $publicKeyPath, new OpenSslCliJwtSigner(getenv('ESIA_CLIENT_OPENSSL_TOOL_PATH') ?: 'openssl'));
    }

    /**
     * @return EsiaAccessToken
     */
    public static function createRsaAccessToken($privateKeyPath, $publicKeyPath)
    {
        return self::createAccessToken($privateKeyPath, $publicKeyPath, new OpenSslCliJwtSigner(getenv('ESIA_CLIENT_OPENSSL_TOOL_PATH') ?: 'openssl', 'RS256'));
    }
}
