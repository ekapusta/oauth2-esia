<?php

namespace Ekapusta\OAuth2Esia;

use Ekapusta\OAuth2Esia\Interfaces\EsiaServiceInterface;
use Ekapusta\OAuth2Esia\Interfaces\Provider\ProviderInterface;
use UnexpectedValueException;

class EsiaService implements EsiaServiceInterface
{
    private $provider;

    public function __construct(ProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return string
     */
    public function generateState()
    {
        return $this->provider->generateState();
    }

    /**
     * @param string $generatedState
     *
     * @return string
     */
    public function getAuthorizationUrl($generatedState)
    {
        return $this->provider->getAuthorizationUrl(['state' => $generatedState]);
    }

    /**
     * @param string $generatedState
     * @param string $passedState
     * @param string $passedCode
     *
     * @throws UnexpectedValueException
     *
     * @return array
     */
    public function getResourceOwner($generatedState, $passedState, $passedCode)
    {
        if ($generatedState != $passedState) {
            throw new UnexpectedValueException("Generated and passed states must be same: $generatedState != $passedState");
        }

        $accessToken = $this->provider->getAccessToken('authorization_code', ['code' => $passedCode]);
        $resourceOwner = $this->provider->getResourceOwner($accessToken);

        return $resourceOwner->toArray();
    }
}
