<?php

namespace Ekapusta\OAuth2Esia\Token;

use InvalidArgumentException;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;

class EsiaAccessToken extends TrustedEsiaAccessToken
{
    public function __construct(array $options, $publicKeyPath, Signer $signer)
    {
        parent::__construct($options);

        if (!$this->parsedToken->verify($signer, new Key(file_get_contents($publicKeyPath)))) {
            throw new InvalidArgumentException('Access token can not be verified: '.var_export($options, true));
        }
    }
}
