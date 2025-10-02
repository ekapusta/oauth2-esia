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

    /**
     * @expectedException \Ekapusta\OAuth2Esia\Security\JWTSigner\Signer\Exception\SignException
     */
    public function testBadCertificate()
    {
        $this->create('/dev/null')->sign('hello world');
    }

    /**
     * @expectedException \Ekapusta\OAuth2Esia\Security\JWTSigner\Signer\Exception\SignException
     */
    public function testUnexistentPrivateKey()
    {
        $this->create($this->pathToCertificate(), '/dev/null')->sign('hello world');
    }

    /**
     * @expectedException \Ekapusta\OAuth2Esia\Security\JWTSigner\Signer\Exception\SignException
     */
    public function testCertificateInsteadOfPrivateKey()
    {
        $this->create($this->pathToCertificate(), $this->pathToCertificate())->sign('hello world');
    }

    /**
     * @expectedException \Ekapusta\OAuth2Esia\Security\JWTSigner\Signer\Exception\SignException
     */
    public function testAnotherCertificate()
    {
        $this->create($this->pathToAnotherCertificate())->sign('hello world');
    }
}
