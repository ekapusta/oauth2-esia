<?php

namespace Ekapusta\OAuth2Esia\Security\Signer;

use Ekapusta\OAuth2Esia\Security\Signer;
use Ekapusta\OAuth2Esia\Security\Signer\Exception\SignException;

class OpensslCli extends Signer
{
    private $toolPath;
    private $middleParams;

    public function __construct(
        $certificatePath,
        $privateKeyPath,
        $privateKeyPassword = null,
        $toolPath = 'openssl'
    ) {
        parent::__construct($certificatePath, $privateKeyPath, $privateKeyPassword);
        if (is_array($toolPath) && 2 == count($toolPath)) {
            $this->middleParams = end($toolPath);
            $toolPath = reset($toolPath);
        }
        $this->toolPath = $toolPath;
    }

    public function sign($message)
    {
        return $this->runParameters([
            'smime -sign -binary -outform DER -noattr',
            $this->middleParams,
            '-signer '.escapeshellarg($this->certificatePath),
            '-inkey '.escapeshellarg($this->privateKeyPath),
            '-passin '.escapeshellarg('pass:'.$this->privateKeyPassword),
        ], $message);
    }

    private function runParameters(array $parameters, $input)
    {
        array_unshift($parameters, $this->toolPath);

        return $this->run(implode(' ', $parameters), $input);
    }

    /**
     * Runs command with input from STDIN.
     */
    private function run($command, $input)
    {
        $pipes = [];
        $process = proc_open($command, [
            ['pipe', 'r'], // stdin
            ['pipe', 'w'], // stdout
            ['pipe', 'w'], // stderr
        ], $pipes);

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $result = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $code = proc_close($process);

        if (0 != $code) {
            $errors = trim($errors) ?: 'unknown';
            throw SignException::signFailedAsOf($errors, $code);
        }

        return $result;
    }
}
