<?php

namespace Ekapusta\OAuth2Esia\Tests\Security\Signer;

use Ekapusta\OAuth2Esia\Security\Signer\OpensslCli;
use Ekapusta\OAuth2Esia\Tests\Factory;
use Ekapusta\OAuth2Esia\Tests\Security\SignerTest;

class OpensslCliTest extends SignerTest
{
    protected function create(
        $certificatePath = null,
        $privateKeyPath = null,
        $privateKeyPassword = null
    ) {
        return new OpensslCli(
            $certificatePath ?: $this->pathToCertificate(),
            $privateKeyPath ?: Factory::KEYS.'ekapusta.gost.test.key',
            $privateKeyPassword,
            getenv('ESIA_CLIENT_OPENSSL_TOOL_PATH') ?: 'openssl',
            '-engine gost'
        );
    }

    protected function pathToCertificate()
    {
        return Factory::KEYS.'ekapusta.gost.test.cer';
    }

    protected function pathToAnotherCertificate()
    {
        return Factory::KEYS.'another.gost.test.cer';
    }
}
