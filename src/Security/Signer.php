<?php

namespace Ekapusta\OAuth2Esia\Security;

use Ekapusta\OAuth2Esia\Interfaces\Security\SignerInterface;

abstract class Signer implements SignerInterface
{
    protected $certificatePath;
    protected $privateKeyPath;
    protected $privateKeyPassword;

    /**
     * @param string $certificatePath
     * @param string $privateKeyPath
     * @param string $privateKeyPassword
     */
    public function __construct($certificatePath, $privateKeyPath, $privateKeyPassword = null)
    {
        $this->certificatePath = $certificatePath;
        $this->privateKeyPath = $privateKeyPath;
        $this->privateKeyPassword = $privateKeyPassword;
    }
}
