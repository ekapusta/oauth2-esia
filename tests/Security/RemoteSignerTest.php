<?php

namespace Ekapusta\OAuth2Esia\Tests\Security;

use Ekapusta\OAuth2Esia\Tests\Factory;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer as RemoteSignerInterface;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\OpenSSL;
use PHPUnit\Framework\TestCase;

abstract class RemoteSignerTest extends TestCase
{
    const JWT = 'eyJ2ZXIiOjEsInR5cCI6IkpXVCIsInNidCI6ImF1dGhvcml6YXRpb25fY29kZSIsImFsZyI6IkdPU1QzNDEwXzIwMTJfMjU2In0.eyJuYmYiOjE1OTA4MzY5MzEsInNjb3BlIjoidmVoaWNsZXM_b2lkPTEwMDA0MDQ0NDYgZnVsbG5hbWU_b2lkPTEwMDA0MDQ0NDYgc25pbHM_b2lkPTEwMDA0MDQ0NDYgb3BlbmlkIGNvbnRhY3RzP29pZD0xMDAwNDA0NDQ2IGRyaXZlcnNfbGljZW5jZV9kb2M_b2lkPTEwMDA0MDQ0NDYgaW5uP29pZD0xMDAwNDA0NDQ2IG1vYmlsZT9vaWQ9MTAwMDQwNDQ0NiBiaXJ0aGRhdGU_b2lkPTEwMDA0MDQ0NDYgZ2VuZGVyP29pZD0xMDAwNDA0NDQ2IGJpcnRocGxhY2U_b2lkPTEwMDA0MDQ0NDYgZW1haWw_b2lkPTEwMDA0MDQ0NDYgaWRfZG9jP29pZD0xMDAwNDA0NDQ2IiwiYXV0aF90aW1lIjoxNTkwODM2OTMxLCJpc3MiOiJodHRwOlwvXC9lc2lhLmdvc3VzbHVnaS5ydVwvIiwidXJuOmVzaWE6c2lkIjoiZjg4ZWY3ODc1ZTIzYzczMDI3NmUxMjUxODE4NjIxYTliZGU3ZWU2Yzc4ZWVhNDNmMGM5ZjlmMmI2ZWMwMDI0MiIsInVybjplc2lhOmNsaWVudDpzdGF0ZSI6Ijg5YzZiNjVhLThmN2MtNDJkYS05NmQ1LTI0YTAwZjdjMzFiNiIsImF1dGhfbXRoZCI6IlBXRCIsInVybjplc2lhOnNiaiI6eyJ1cm46ZXNpYTpzYmo6dHlwIjoiUCIsInVybjplc2lhOnNiajppc190cnUiOnRydWUsInVybjplc2lhOnNiajpvaWQiOjEwMDA0MDQ0NDYsInVybjplc2lhOnNiajpuYW0iOiJPSUQuMTAwMDQwNDQ0NiIsInVybjplc2lhOnNiajplaWQiOjc1MTg2NDF9LCJleHAiOjE1OTA4MzcxNzEsInBhcmFtcyI6eyJyZW1vdGVfaXAiOiIxMC42OC4zNS41IiwidXNlcl9hZ2VudCI6Ik1vemlsbGFcLzUuMCAoWDExOyBMaW51eCB4ODZfNjQpIEFwcGxlV2ViS2l0XC81MzcuMzYgKEtIVE1MLCBsaWtlIEdlY2tvKSBIZWFkbGVzc0Nocm9tZVwvNjcuMC4zMzkxLjAgU2FmYXJpXC81MzcuMzYifSwiaWF0IjoxNTkwODM2OTMxLCJjbGllbnRfaWQiOiJNTlNWMzYifQ.aiKZ-18dUBxpFj8Hyn33UoQ9U4-rQXhY7tPky2FsIsgmsOJZgJZUwCrRrNot4g42hkz8GzOOe86_8Zk3ZYSzbA';

    /**
     * @return RemoteSignerInterface
     */
    abstract protected function create();

    /**
     * @return string
     */
    protected function pathToCertificate()
    {
        return Factory::KEYS . 'esia.gost.test.cer';
    }

    /**
     * @return string
     */
    protected function pathToInvalidCertificate()
    {
        return Factory::KEYS . 'esia.gost.prod.cer';
    }

    /**
     * @return Key
     */
    protected function createKey()
    {
        return new Key(file_get_contents(Factory::KEYS . 'ekapusta.gost2012.test.key'));
    }

    /**
     * @return \Lcobucci\JWT\Token
     */
    protected function createSampleToken()
    {
        return (new Parser())->parse(self::JWT);
    }

    public function testTokenSign()
    {
        $this->expectException(\RuntimeException::class);
        $signer = $this->create();
        /* @var $signer OpenSSL */
        $signer->getKeyType();
    }

    public function testTokenCreateHash()
    {
        $this->expectException(\RuntimeException::class);
        $signer = $this->create();
        /* @var $signer OpenSSL */
        $signer->getAlgorithm();
    }

    public function testTokenVerify()
    {
        $signer = $this->create();
        $token = $this->createSampleToken();
        $result = $token->verify($signer, new Key(file_get_contents($this->pathToCertificate())));
        $this->assertTrue($result);
    }

    public function testTokenVerifyFailed()
    {
        $signer = $this->create();
        $token = $this->createSampleToken();
        $result = $token->verify($signer, new Key(file_get_contents($this->pathToInvalidCertificate())));
        $this->assertFalse($result);
    }

    public function testTokenInvalidPublicKey()
    {
        $this->expectException(\RuntimeException::class);
        $signer = $this->create();
        $token = $this->createSampleToken();
        $token->verify($signer, new Key('not a certificate'));
    }
}
