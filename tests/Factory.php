<?php

namespace Ekapusta\OAuth2Esia\Tests;

use Ekapusta\OAuth2Esia\Token\EsiaAccessToken;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;

class Factory
{
    const KEYS = __DIR__.'/../resources/';

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
