<?php

namespace Ekapusta\OAuth2Esia\Token;

use Ekapusta\OAuth2Esia\Interfaces\Token\ScopedTokenInterface;
use InvalidArgumentException;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\ValidationData;
use League\OAuth2\Client\Token\AccessToken;

class EsiaAccessToken extends AccessToken implements ScopedTokenInterface
{
    private $parsedToken;

    public function __construct(array $options, $publicKeyPath = null, Signer $signer = null)
    {
        parent::__construct($options);

        $this->parsedToken = (new Parser())->parse($this->accessToken);
        $this->resourceOwnerId = $this->parsedToken->getClaim('urn:esia:sbj_id');

        if (!$this->parsedToken->validate(new ValidationData())) {
            throw new InvalidArgumentException('Access token is invalid: '.var_export($options, true));
        }

        if ($publicKeyPath && !$this->parsedToken->verify($signer, new Key(file_get_contents($publicKeyPath)))) {
            throw new InvalidArgumentException('Access token can not be verified: '.var_export($options, true));
        }
    }

    public function getScopes()
    {
        $scopes = [];
        foreach (explode(' ', $this->parsedToken->getClaim('scope', '')) as $scope) {
            $scopes[] = parse_url($scope, PHP_URL_PATH);
        }

        return $scopes;
    }
}
