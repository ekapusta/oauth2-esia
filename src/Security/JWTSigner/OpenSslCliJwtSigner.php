<?php

namespace Ekapusta\OAuth2Esia\Security\JWTSigner;

use Ekapusta\OAuth2Esia\Transport\Process;
use Lcobucci\JWT\Signer\BaseSigner;
use Lcobucci\JWT\Signer\Key;

final class OpenSslCliJwtSigner extends BaseSigner
{
    private $toolPath;
    private $algorythmId;
    private $postParams = '';

    public function __construct($toolPath = 'openssl', $algorythmId = 'GOST3410_2012_256')
    {
        $this->toolPath = $toolPath;
        $this->algorythmId = $algorythmId;

        if (false !== stristr($this->getAlgorithmId(), 'gost')) {
            $this->postParams = '-engine gost';
        }
    }

    public function getAlgorithmId()
    {
        return $this->algorythmId;
    }

    public function doVerify($expected, $payload, Key $key)
    {
        $verify = new TmpFile($key->getContent());
        $signature = new TmpFile($expected);

        Process::fromArray([
            $this->toolPath,
            'dgst',
            '-verify '.escapeshellarg($verify),
            '-signature '.escapeshellarg($signature),
            $this->postParams,
        ], $payload);

        return true;
    }

    public function createHash($payload, Key $key)
    {
        $sign = new TmpFile($key->getContent());

        return (string) Process::fromArray([
            $this->toolPath,
            'dgst',
            '-sign '.escapeshellarg($sign),
            $this->postParams,
        ], $payload);
    }
}
