<?php

namespace Ekapusta\OAuth2Esia\Tests;

use Ekapusta\OAuth2Esia\EsiaService;
use Ekapusta\OAuth2Esia\Provider\EsiaProvider;
use Ekapusta\OAuth2Esia\Token\EsiaAccessToken;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use PHPUnit\Framework\TestCase;

class EsiaServiceTest extends TestCase
{
    private $provider;
    private $service;

    protected function setUp(): void
    {
        $this->provider = $this->getMockBuilder(EsiaProvider::class)->disableOriginalConstructor()->setMethods([
            'getRandomState',
            'getAuthorizationUrl',
            'getAccessToken',
            'getResourceOwner',
        ])->getMock();

        $this->service = new EsiaService($this->provider);
    }

    public function testGenerateState()
    {
        $this->provider->expects($this->once())->method('getRandomState')->willReturn('state');

        $this->assertEquals('state', $this->service->generateState());
    }

    public function testGetAuthorizationUrl()
    {
        $this->provider->expects($this->once())->method('getAuthorizationUrl')->willReturn('url');

        $this->assertEquals('url', $this->service->getAuthorizationUrl('state'));
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Generated and passed states must be same: 1 != 2
     */
    public function testGetResourceOwnerFails()
    {
        $this->service->getResourceOwner('1', '2', '3');
    }

    public function testGetResourceOwnerSucceed()
    {
        $accessToken = $this->getMockBuilder(EsiaAccessToken::class)->disableOriginalConstructor()->getMock();
        $this->provider->expects($this->once())->method('getAccessToken')->willReturn($accessToken);

        $resourceOwner = new GenericResourceOwner(['name' => 'Иван'], 123);
        $this->provider->expects($this->once())->method('getResourceOwner')->willReturn($resourceOwner);

        $ownerData = $this->service->getResourceOwner('state', 'state', 'code');
        $this->assertEquals(['name' => 'Иван'], $ownerData);
    }
}
