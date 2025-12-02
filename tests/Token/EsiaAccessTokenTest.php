<?php

namespace Ekapusta\OAuth2Esia\Tests\Token;

use Ekapusta\OAuth2Esia\Tests\Factory;
use Ekapusta\OAuth2Esia\Token\EsiaAccessToken;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use PHPUnit\Framework\TestCase;

class EsiaAccessTokenTest extends TestCase
{
    public function testInvalidAsItExpired()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access token is invalid');

        new EsiaAccessToken([
            'access_token' => file_get_contents(__DIR__.'/../Fixtures/expired.token.txt'),
        ], 'anything', new Sha256());
    }

    public function testInvalidAsBadSignature()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Access token can not be verified');

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
        $this->assertEquals(['one', 'two', 'three', 'contacts'], $token->getScopes());
    }

    public function testGostFullyValidAndScopesExtraced()
    {
        $esiaToken = Factory::createGostAccessToken(
            Factory::KEYS.'another.gost.test.key',
            Factory::KEYS.'another.gost.test.public.key'
        );

        $this->assertInstanceOf(EsiaAccessToken::class, $esiaToken);

        $this->assertEquals(['one', 'two', 'three', 'contacts'], $esiaToken->getScopes());
    }

    public function testGostIsInvalid()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('unable to load');

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

        $this->assertEquals(['one', 'two', 'three', 'contacts'], $esiaToken->getScopes());
    }
}
