<?php

namespace Ekapusta\OAuth2Esia\Transport;

use RuntimeException;

/**
 * Runs command with input from STDIN.
 */
class Process
{
    private $stdout;

    /**
     * @throws RuntimeException When operation fails
     */
    public function __construct($command, $input)
    {
        $pipes = [];
        $process = proc_open($command, [
            ['pipe', 'r'], // stdin
            ['pipe', 'w'], // stdout
            ['pipe', 'w'], // stderr
        ], $pipes);

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $this->stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $code = proc_close($process);
        if (0 != $code) {
            throw new RuntimeException("Operation failed with code#$code as of: $stderr");
        }
    }

    /**
     * @return Process
     */
    public static function fromArray(array $command, $input)
    {
        return new self(implode(' ', $command), $input);
    }

    public function __toString()
    {
        return $this->stdout;
    }
}
