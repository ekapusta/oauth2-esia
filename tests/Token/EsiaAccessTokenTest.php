<?php

namespace Ekapusta\OAuth2Esia\Tests\Token;

use Ekapusta\OAuth2Esia\Tests\Factory;
use Ekapusta\OAuth2Esia\Token\EsiaAccessToken;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use PHPUnit\Framework\TestCase;

class EsiaAccessTokenTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Access token can not be verified
     */
    public function testInvalidAsItExpired()
    {
        new EsiaAccessToken([
            'access_token' => file_get_contents(__DIR__.'/../Fixtures/expired.token.txt'),
        ], Factory::KEYS.'another.rsa.test.public.key', new Sha256());
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

    public function testGostFullyValidAndScopesExtraced()
    {
        $esiaToken = Factory::createGostAccessToken(
            Factory::KEYS.'another.gost.test.key',
            Factory::KEYS.'another.gost.test.public.key'
        );

        $this->assertInstanceOf(EsiaAccessToken::class, $esiaToken);

        $this->assertEquals(['one', 'two', 'three'], $esiaToken->getScopes());
    }

    /**
     * @expectedException \Lcobucci\JWT\Signer\InvalidKeyProvided
     * @expectedExceptionMessage Key cannot be empty
     */
    public function testGostIsInvalid()
    {
        Factory::createGostAccessToken(
            Factory::KEYS.'another.gost.test.key',
            '/dev/null'
        );
    }

    public function testRsaFullyValidAndScopesExtraced()
    {
        $esiaToken = Factory::createRsaAccessToken(
            Factory::KEYS.'ekapusta.rsa.test.key',
            Factory::KEYS.'ekapusta.rsa.test.public.key'
        );

        $this->assertInstanceOf(EsiaAccessToken::class, $esiaToken);

        $this->assertEquals(['one', 'two', 'three'], $esiaToken->getScopes());
    }
}
