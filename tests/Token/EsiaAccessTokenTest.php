<?php

namespace Ekapusta\OAuth2Esia\Tests\Token;

use Ekapusta\OAuth2Esia\Tests\Factory;
use Ekapusta\OAuth2Esia\Token\EsiaAccessToken;
use PHPUnit\Framework\TestCase;

class EsiaAccessTokenTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Access token is invalid
     */
    public function testInvalidAsItExpired()
    {
        new EsiaAccessToken([
            'access_token' => file_get_contents(__DIR__.'/../Fixtures/expired.token.txt'),
        ]);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Access token can not be verified
     */
    public function testInvalidAsBadSignature()
    {
        Factory::createAccessToken(
            Factory::KEYS.'ekapusta.rsa.test.key',
            Factory::KEYS.'another.rsa.test.public.key'
        );
    }

    public function testFullyValid()
    {
        $esiaToken = Factory::createAccessToken(
            Factory::KEYS.'ekapusta.rsa.test.key',
            Factory::KEYS.'ekapusta.rsa.test.public.key'
        );

        $this->assertInstanceOf(EsiaAccessToken::class, $esiaToken);

        return $esiaToken;
    }

    /**
     * @depends testFullyValid
     */
    public function testScopesExtracted(EsiaAccessToken $token)
    {
        $this->assertEquals(['one', 'two', 'three'], $token->getScopes());
    }
}
