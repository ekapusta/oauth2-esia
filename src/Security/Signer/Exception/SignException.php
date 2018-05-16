<?php

namespace Ekapusta\OAuth2Esia\Security\Signer\Exception;

/**
 * Exception thrown if the signature fails.
 */
class SignException extends \Exception
{
    /**
     * @param string $path
     *
     * @return \Ekapusta\OAuth2Esia\Security\Signer\Exception\SignException
     */
    public static function canNotReadCertificate($path)
    {
        return new static('Can not read certificate '.$path);
    }

    /**
     * @param string $path
     *
     * @return \Ekapusta\OAuth2Esia\Security\Signer\Exception\SignException
     */
    public static function canNotReadPrivateKey($path)
    {
        return new static('Can not read private key '.$path);
    }

    /**
     * @param string $reason
     * @param int    $code
     *
     * @return \Ekapusta\OAuth2Esia\Security\Signer\Exception\SignException
     */
    public static function signFailedAsOf($reason, $code = 0)
    {
        return new static('Sign failed as of: '.$reason, $code);
    }
}
