<?php

namespace Ekapusta\OAuth2Esia\Tests;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Logs in to ESIA URL by provided login/password and dumps out redirection URL.
 */
class AuthenticationBot
{
    use LoggerAwareTrait;

    private $toolRoot;
    private $mobileOrEmail;
    private $password;
    private $headless;
    private $post;

    public function __construct($mobileOrEmail, $password, $headless = true, $post = false)
    {
        $this->toolRoot = __DIR__.'/AuthenticationBot';
        $this->mobileOrEmail = $mobileOrEmail;
        $this->password = $password;
        $this->headless = $headless;
        $this->post = $post;

        $this->setLogger(new NullLogger());
    }

    /**
     * Login to provided URL.
     *
     * @return string redirected URL, which contains state and code
     */
    public function login($url, $redirectUrlPrefix)
    {
        $this->installIfNeeded();

        $command = sprintf(
            '%s --mobileOrEmail %s --password %s --loginUrl %s --redirectUrlPrefix %s %s %s',
            $this->toolRoot.'/authentication-bot.js',
            escapeshellarg($this->mobileOrEmail),
            escapeshellarg($this->password),
            escapeshellarg($url),
            escapeshellarg($redirectUrlPrefix),
            $this->headless ? '--headless' : '',
            $this->post ? '--post' : ''
        );

        $this->logger->debug("Requesting command $command");

        $pipes = [];
        $process = proc_open($command, [
            ['pipe', 'r'], // stdin
            ['pipe', 'w'], // stdout
            ['pipe', 'w'], // stderr
        ], $pipes);

        fclose($pipes[0]);

        while (false !== ($line = fgets($pipes[2], 4096))) { // read from stderr and translate to logs
            $this->logger->debug($line);
        }

        $response = '';
        while (false !== ($line = fgets($pipes[1], 4096))) { // read from stdout for return
            $response .= $line;
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        proc_close($process);

        $this->logger->debug("Response \n$response");

        return trim($response);
    }

    private function installIfNeeded()
    {
        if (file_exists($this->toolRoot.'/node_modules')) {
            return;
        }
        $this->logger->debug('Installing tool dependencies');
        shell_exec('cd '.$this->toolRoot.' && npm install');
    }
}
