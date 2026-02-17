<?php

namespace Ekapusta\OAuth2Esia\Token;

use InvalidArgumentException;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;

class EsiaAccessToken extends TrustedEsiaAccessToken
{
    public function __construct(array $options, $publicKeyPath, Signer $signer)
    {
        parent::__construct($options);

        $validator = new Validator();
        $signedWithConstraint = new SignedWith($signer, InMemory::file($publicKeyPath));

        if (!$validator->validate($this->parsedToken, $signedWithConstraint)) {
            throw new InvalidArgumentException('Access token can not be verified: '.var_export($options, true));
        }
    }
}
