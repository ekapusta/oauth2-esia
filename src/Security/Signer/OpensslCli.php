<?php

namespace Ekapusta\OAuth2Esia\Security\Signer;

use Ekapusta\OAuth2Esia\Security\Signer;
use Ekapusta\OAuth2Esia\Security\Signer\Exception\SignException;
use Ekapusta\OAuth2Esia\Transport\Process;
use RuntimeException;

class OpensslCli extends Signer
{
    private $toolPath;
    private $postParams = '';

    public function __construct(
        $certificatePath,
        $privateKeyPath,
        $privateKeyPassword = null,
        $toolPath = 'openssl',
        $postParams = ''
    ) {
        parent::__construct($certificatePath, $privateKeyPath, $privateKeyPassword);
        $this->toolPath = $toolPath;
        $this->postParams = $postParams;
    }

    public function sign($message)
    {
        try {
            return Process::fromArray([
                $this->toolPath,
                'smime -sign -binary -outform DER -noattr',
                '-signer '.escapeshellarg($this->certificatePath),
                '-inkey '.escapeshellarg($this->privateKeyPath),
                '-passin '.escapeshellarg('pass:'.$this->privateKeyPassword),
                $this->postParams,
            ], $message);
        } catch (RuntimeException $e) {
            throw SignException::signFailedAsOf($e->getMessage(), $e->getCode());
        }
    }
}
