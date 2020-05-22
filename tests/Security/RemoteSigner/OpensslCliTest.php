<?php

namespace Ekapusta\OAuth2Esia\Tests\Security\RemoteSigner;

use Ekapusta\OAuth2Esia\Security\RemoteSigner\OpensslCli;
use Ekapusta\OAuth2Esia\Tests\Security\RemoteSignerTest;

class OpensslCliTest extends RemoteSignerTest
{
    protected function create()
    {
        return new OpensslCli(
            getenv('ESIA_CLIENT_OPENSSL_TOOL_PATH') ?: 'openssl'
        );
    }

}
