<?php

namespace Ekapusta\OAuth2Esia\Token;

use DateTimeZone;
use Ekapusta\OAuth2Esia\Interfaces\Token\ScopedTokenInterface;
use InvalidArgumentException;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use League\OAuth2\Client\Token\AccessToken;

class EsiaAccessToken extends AccessToken implements ScopedTokenInterface
{
    /** @var UnencryptedToken */
    private $parsedToken;

    public function __construct(array $options, $publicKeyPath = null, Signer $signer = null)
    {
        parent::__construct($options);

        $this->parsedToken = (new Parser(new JoseEncoder()))->parse($this->accessToken);
        $this->resourceOwnerId = $this->parsedToken->claims()->get('urn:esia:sbj_id');

        $validator = new Validator();
        $constraints = [
            new LooseValidAt(new SystemClock(new DateTimeZone('UTC'))),
        ];
        if ($publicKeyPath) {
            $constraints[] = new SignedWith($signer, Key\InMemory::plainText(file_get_contents($publicKeyPath)));
        }
        if (!$validator->validate($this->parsedToken, ...$constraints)) {
            throw new InvalidArgumentException('Access token can not be verified');
        }
    }

    public function getScopes(): array
    {
        $scopes = [];
        foreach (explode(' ', $this->parsedToken->claims()->get('scope', '')) as $scope) {
            $scopes[] = parse_url($scope, PHP_URL_PATH);
        }

        return $scopes;
    }
}
