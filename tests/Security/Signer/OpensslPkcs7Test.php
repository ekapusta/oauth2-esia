<?php

namespace Ekapusta\OAuth2Esia\Tests\Security\Signer;

use Ekapusta\OAuth2Esia\Security\JWTSigner\Signer\OpensslPkcs7;
use Ekapusta\OAuth2Esia\Tests\Factory;
use Ekapusta\OAuth2Esia\Tests\Security\SignerTest;

class OpensslPkcs7Test extends SignerTest
{
    protected function create(
        $certificatePath = null,
        $privateKeyPath = null,
        $privateKeyPassword = null
    ) {
        return new OpensslPkcs7(
            $certificatePath ?: $this->pathToCertificate(),
            $privateKeyPath ?: Factory::KEYS.'ekapusta.rsa.test.key',
            $privateKeyPassword
        );
    }

    protected function pathToCertificate()
    {
        return  Factory::KEYS.'ekapusta.rsa.test.cer';
    }

    protected function pathToAnotherCertificate()
    {
        return  Factory::KEYS.'another.rsa.test.cer';
    }
}
