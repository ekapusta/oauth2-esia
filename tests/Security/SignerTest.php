<?php

namespace Ekapusta\OAuth2Esia\Tests\Security;

use Ekapusta\OAuth2Esia\Interfaces\Security\SignerInterface;
use PHPUnit\Framework\TestCase;

abstract class SignerTest extends TestCase
{
    /**
     * @return SignerInterface
     */
    abstract protected function create(
        $certificatePath = null,
        $privateKeyPath = null,
        $privateKeyPassword = null
    );

    abstract protected function pathToCertificate();

    abstract protected function pathToAnotherCertificate();

    public function testMessageSigned()
    {
        $signature = $this->create()->sign('hello world');

        $this->assertNotEmpty($signature);

        return $signature;
    }

    public function testBadCertificate()
    {
        $this->expectException(\Ekapusta\OAuth2Esia\Security\Signer\Exception\SignException::class);
        $this->create('/dev/null')->sign('hello world');
    }

    public function testUnexistentPrivateKey()
    {
        $this->expectException(\Ekapusta\OAuth2Esia\Security\Signer\Exception\SignException::class);
        $this->create($this->pathToCertificate(), '/dev/null')->sign('hello world');
    }

    public function testCertificateInsteadOfPrivateKey()
    {
        $this->expectException(\Ekapusta\OAuth2Esia\Security\Signer\Exception\SignException::class);
        $this->create($this->pathToCertificate(), $this->pathToCertificate())->sign('hello world');
    }

    public function testAnotherCertificate()
    {
        $this->expectException(\Ekapusta\OAuth2Esia\Security\Signer\Exception\SignException::class);
        $this->create($this->pathToAnotherCertificate())->sign('hello world');
    }
}
