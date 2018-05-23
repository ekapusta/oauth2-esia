<?php

namespace Ekapusta\OAuth2Esia\Tests;

use Bramus\Monolog\Formatter\ColoredLineFormatter;
use Bramus\Monolog\Formatter\ColorSchemes\TrafficLight;
use Ekapusta\OAuth2Esia\Provider\EsiaProvider;
use Ekapusta\OAuth2Esia\Token\EsiaAccessToken;
use Lcobucci\JWT\Builder;
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
            'EsiaTest006@yandex.ru',
            '11111111',
            !getenv('DISPLAY'),
            getenv('ESIA_CLIENT_AUTH_METHOD') == 'post'
        );
        $bot->setLogger(self::createLogger('authentication-bot'));

        return $bot;
    }

    /**
     * @return EsiaAccessToken
     */
    public static function createAccessToken($privateKeyPath, $publicKeyPath = null)
    {
        $accessToken = (new Builder())
            ->setIssuedAt(time())
            ->setNotBefore(time())
            ->setExpiration(time() + 3600)
            ->set('urn:esia:sbj_id', 1)
            ->set('scope', 'one?oid=123 two?oid=456 three?oid=789')
            ->sign(new Sha256(), new Key(file_get_contents($privateKeyPath)))
            ->getToken();

        return new EsiaAccessToken(['access_token' => (string) $accessToken], $publicKeyPath);
    }
}
