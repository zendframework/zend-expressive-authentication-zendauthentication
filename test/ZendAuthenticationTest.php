<?php
/**
 * @see https://github.com/zendframework/zend-exprsesive-authentication-zendauthentication
 *     for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license https://github.com/zendframework/zend-exprsesive-authentication-zendauthentication/blob/master/LICENSE.md
 *     New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Authentication\Adapter;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Authentication\Adapter\AbstractAdapter;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Result;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Authentication\UserInterface;
use Zend\Expressive\Authentication\ZendAuthentication\ZendAuthentication;

class ZendAuthenticationTest extends TestCase
{
    /** @var ServerRequestInterface|ObjectProphecy */
    private $request;

    /** @var AuthenticationService|ObjectProphecy */
    private $authService;

    /** @var UserInterface|ObjectProphecy */
    private $authenticatedUser;

    /** @var callable */
    private $responseFactory;

    /** @var callable */
    private $userFactory;

    /** @var UserInterface|ObjectProphecy */
    private $userPrototype;

    protected function setUp()
    {
        $this->request = $this->prophesize(ServerRequestInterface::class);
        $this->authService = $this->prophesize(AuthenticationService::class);
        $this->authenticatedUser = $this->prophesize(UserInterface::class);
        $this->responseFactory = function () {
            return $this->prophesize(ResponseInterface::class)->reveal();
        };
        $this->userPrototype = $this->prophesize(UserInterface::class);
        $this->userFactory = function () {
            return $this->userPrototype->reveal();
        };
    }

    public function testConstructor()
    {
        $zendAuthentication = new ZendAuthentication(
            $this->authService->reveal(),
            [],
            $this->responseFactory,
            $this->userFactory
        );
        $this->assertInstanceOf(AuthenticationInterface::class, $zendAuthentication);
    }

    public function testAuthenticateWithGetMethodAndIdentity()
    {
        $this->request->getMethod()->willReturn('GET');
        $this->authService->hasIdentity()->willReturn(true);
        $this->authService->getIdentity()->willReturn('foo');

        $zendAuthentication = new ZendAuthentication(
            $this->authService->reveal(),
            [],
            $this->responseFactory,
            $this->userFactory
        );
        $result = $zendAuthentication->authenticate($this->request->reveal());
        $this->assertInstanceOf(UserInterface::class, $result);
    }

    public function testAuthenticateWithGetMethodAndNoIdentity()
    {
        $this->request->getMethod()->willReturn('GET');
        $this->authService->hasIdentity()->willReturn(false);

        $zendAuthentication = new ZendAuthentication(
            $this->authService->reveal(),
            [],
            $this->responseFactory,
            $this->userFactory
        );
        $this->assertNull($zendAuthentication->authenticate($this->request->reveal()));
    }

    public function testAuthenticateWithPostMethodAndNoParams()
    {
        $this->request->getMethod()->willReturn('POST');
        $this->request->getParsedBody()->willReturn([]);

        $zendAuthentication = new ZendAuthentication(
            $this->authService->reveal(),
            [],
            $this->responseFactory,
            $this->userFactory
        );
        $this->assertNull($zendAuthentication->authenticate($this->request->reveal()));
    }

    public function testAuthenticateWithPostMethodAndNoValidCredential()
    {
        //not authenticated
        $this->authService->hasIdentity()->willReturn(false);

        $this->request->getMethod()->willReturn('POST');
        $this->request->getParsedBody()->willReturn([
            'username' => 'foo',
            'password' => 'bar',
        ]);
        $adapter = $this->prophesize(AbstractAdapter::class);
        $adapter->setIdentity('foo')->willReturn(null);
        $adapter->setCredential('bar')->willReturn();

        $this->authService
            ->getAdapter()
            ->willReturn($adapter->reveal());
        $result = $this->prophesize(Result::class);
        $result->isValid()->willReturn(false);

        $this->authService
            ->authenticate()
            ->willReturn($result);

        $zendAuthentication = new ZendAuthentication(
            $this->authService->reveal(),
            [],
            $this->responseFactory,
            $this->userFactory
        );
        $this->assertNull($zendAuthentication->authenticate($this->request->reveal()));
    }

    public function testAuthenticateWithPostMethodAndValidCredential()
    {
        //not authenticated
        $this->authService->hasIdentity()->willReturn(false);

        $this->request->getMethod()->willReturn('POST');
        $this->request->getParsedBody()->willReturn([
            'username' => 'foo',
            'password' => 'bar',
        ]);
        $adapter = $this->prophesize(AbstractAdapter::class);
        $adapter->setIdentity('foo')->willReturn(null);
        $adapter->setCredential('bar')->willReturn();

        $this->authService
            ->getAdapter()
            ->willReturn($adapter->reveal());
        $result = $this->prophesize(Result::class);
        $result->isValid()->willReturn(true);
        $result->getIdentity()->willReturn('foo');

        $this->authService
            ->authenticate()
            ->willReturn($result);

        $zendAuthentication = new ZendAuthentication(
            $this->authService->reveal(),
            [],
            $this->responseFactory,
            $this->userFactory
        );
        $result = $zendAuthentication->authenticate($this->request->reveal());
        $this->assertInstanceOf(UserInterface::class, $result);
    }

    public function testAuthenticateWithPostMethodAndNoValidCredentialAndAlreadyAuthenticated()
    {
        $this->authService->hasIdentity()->willReturn(true);
        $this->authService->getIdentity()->willReturn('string');

        $this->request->getMethod()->willReturn('POST');
        $this->request->getParsedBody()->willReturn([
            'username' => 'foo',
            'password' => 'bar',
        ]);
        $adapter = $this->prophesize(AbstractAdapter::class);
        $adapter->setIdentity('foo')->willReturn(null);
        $adapter->setCredential('bar')->willReturn();

        $this->authService
            ->getAdapter()
            ->willReturn($adapter->reveal());
        $result = $this->prophesize(Result::class);
        $result->isValid()->willReturn(false);

        $this->authService
            ->authenticate()
            ->willReturn($result);

        $this->userPrototype->getIdentity()->willReturn('string');

        $zendAuthentication = new ZendAuthentication(
            $this->authService->reveal(),
            [],
            $this->responseFactory,
            $this->userFactory
        );
        $identity = $zendAuthentication->authenticate($this->request->reveal());
        $this->assertInstanceOf(UserInterface::class, $identity);
        $this->assertEquals('string', $identity->getIdentity());
    }

    public function testAuthenticateWithPostMethodAndValidCredentialAndAlreadyAuthenticated()
    {
        $this->authService->hasIdentity()->willReturn(true);
        $this->authService->getIdentity()->willReturn('string');

        $this->request->getMethod()->willReturn('POST');

        $zendAuthentication = new ZendAuthentication(
            $this->authService->reveal(),
            [],
            $this->responseFactory,
            $this->userFactory
        );

        $result = $zendAuthentication->authenticate($this->request->reveal());

        $this->assertInstanceOf(UserInterface::class, $result);
    }
}
