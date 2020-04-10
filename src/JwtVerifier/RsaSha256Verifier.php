<?php

namespace Ekapusta\OAuth2Esia\JwtVerifier;

use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token;

class RsaSha256Verifier
{
    protected $publicKeyPath;

    public function __construct($publicKeyPath)
    {
        $this->publicKeyPath = $publicKeyPath;
    }

    public function verify(Token $token)
    {
        return !$token->verify(new Sha256(), new Key(file_get_contents($this->publicKeyPath)));
    }
}