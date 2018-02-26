<?php
/**
 * @see https://github.com/zendframework/zend-exprsesive-authentication-zendauthentication
 *     for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license https://github.com/zendframework/zend-exprsesive-authentication-zendauthentication/blob/master/LICENSE.md
 *     New BSD License
 */

namespace ZendTest\Expressive\Authentication\ZendAuthentication;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Authentication\AuthenticationService;
use Zend\Expressive\Authentication\Exception\InvalidConfigException;
use Zend\Expressive\Authentication\ZendAuthentication\ZendAuthentication;
use Zend\Expressive\Authentication\ZendAuthentication\ZendAuthenticationFactory;

class ZendAuthenticationFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var ZendAuthentication */
    private $factory;

    /** @var AuthenticationService|ObjectProphecy */
    private $authService;

    /** @var callable */
    private $responseFactory;

    protected function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->factory = new ZendAuthenticationFactory();
        $this->authService = $this->prophesize(AuthenticationService::class);
        $this->responseFactory = function () {
            return $this->prophesize(ResponseInterface::class)->reveal();
        };
    }

    public function testInvokeWithEmptyContainer()
    {
        $this->expectException(InvalidConfigException::class);
        ($this->factory)($this->container->reveal());
    }

    public function testInvokeWithContainerEmptyConfig()
    {
        $this->container
            ->has(AuthenticationService::class)
            ->willReturn(true);
        $this->container
            ->get(AuthenticationService::class)
            ->willReturn($this->authService->reveal());
        $this->container
            ->has(ResponseInterface::class)
            ->willReturn(true);
        $this->container
            ->get(ResponseInterface::class)
            ->willReturn($this->responseFactory);
        $this->container
            ->get('config')
            ->willReturn([]);

        $this->expectException(InvalidConfigException::class);
        ($this->factory)($this->container->reveal());
    }

    public function testInvokeWithContainerAndConfig()
    {
        $this->container
            ->has(AuthenticationService::class)
            ->willReturn(true);
        $this->container
            ->get(AuthenticationService::class)
            ->willReturn($this->authService->reveal());
        $this->container
            ->has(ResponseInterface::class)
            ->willReturn(true);
        $this->container
            ->get(ResponseInterface::class)
            ->willReturn($this->responseFactory);
        $this->container
            ->get('config')
            ->willReturn([
                'authentication' => ['redirect' => '/login'],
            ]);

        $zendAuthentication = ($this->factory)($this->container->reveal());
        $this->assertInstanceOf(ZendAuthentication::class, $zendAuthentication);
    }
}
