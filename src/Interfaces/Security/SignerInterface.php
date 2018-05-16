<?php

namespace Ekapusta\OAuth2Esia\Interfaces\Security;

interface SignerInterface
{
    /**
     * @param string $message
     *
     * @throws \Ekapusta\OAuth2Esia\Security\Signer\Exception\SignException
     *
     * @return string
     */
    public function sign($message);
}
