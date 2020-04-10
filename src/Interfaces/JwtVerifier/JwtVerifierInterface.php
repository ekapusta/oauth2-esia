<?php

namespace Ekapusta\OAuth2Esia\Interfaces\JwtVerifier;

use Lcobucci\JWT\Token;

interface JwtVerifierInterface
{
    /**
     * @param Token $token
     *
     * @return bool
     */
    public function verify(Token $token);
}

