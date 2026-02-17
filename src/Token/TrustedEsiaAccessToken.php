<?php

namespace Ekapusta\OAuth2Esia\Token;

use Ekapusta\OAuth2Esia\Interfaces\Token\ScopedTokenInterface;
use InvalidArgumentException;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\Plain;
use League\OAuth2\Client\Token\AccessToken;

class TrustedEsiaAccessToken extends AccessToken implements ScopedTokenInterface
{
    protected $parsedToken;

    public function __construct(array $options)
    {
        parent::__construct($options);

        $this->parsedToken = (new Parser(new JoseEncoder()))->parse($this->accessToken);
        if ($this->parsedToken instanceof Plain) {
            $this->resourceOwnerId = $this->parsedToken->claims()->get('urn:esia:sbj_id');
        }

        if ($this->parsedToken->isExpired(new \DateTimeImmutable())) {
            throw new InvalidArgumentException('Access token is invalid: '.var_export($options, true));
        }
    }

    public function getScopes()
    {
        $scopes = [];
        foreach (explode(' ', $this->parsedToken->claims()->get('scope', '')) as $scope) {
            $scopes[] = parse_url($scope, PHP_URL_PATH);
        }

        return $scopes;
    }
}
