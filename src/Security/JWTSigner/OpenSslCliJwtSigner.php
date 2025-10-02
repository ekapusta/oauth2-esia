<?php

namespace Ekapusta\OAuth2Esia\Security\JWTSigner;

use Ekapusta\OAuth2Esia\Transport\Process;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;

final class OpenSslCliJwtSigner implements Signer
{
    private $toolPath;
    private $algorythmId;
    private $postParams = '';

    public function __construct($toolPath = 'openssl', $algorythmId = 'GOST3410_2012_256')
    {
        $this->toolPath = $toolPath;
        $this->algorythmId = $algorythmId;

        if (stripos($this->algorithmId(), 'gost') !== false) {
            $this->postParams = '-engine gost';
        }
    }
    public function algorithmId(): string
    {
        return $this->algorythmId;
    }

    public function sign(string $payload, Key $key): string
    {
        $sign = new TmpFile($key->contents());

        return (string) Process::fromArray([
            $this->toolPath,
            'dgst',
            '-sign '.escapeshellarg($sign),
            $this->postParams,
        ], $payload);
    }

    public function verify(string $expected, string $payload, Key $key): bool
    {
        $verify = new TmpFile($key->contents());
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
}
