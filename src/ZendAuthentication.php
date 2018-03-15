<?php
/**
 * @see https://github.com/zendframework/zend-exprsesive-authentication-zendauthentication
 *     for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license https://github.com/zendframework/zend-exprsesive-authentication-zendauthentication/blob/master/LICENSE.md
 *     New BSD License
 */

namespace Zend\Expressive\Authentication\ZendAuthentication;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Authentication\AuthenticationService;
use Zend\Expressive\Authentication\AuthenticationInterface;
use Zend\Expressive\Authentication\UserInterface;
use Zend\Expressive\Authentication\UserRepository\UserTrait;

use function strtoupper;

class ZendAuthentication implements AuthenticationInterface
{
    use UserTrait;

    /**
     * @var AuthenticationService
     */
    protected $auth;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var callable
     */
    protected $responseFactory;

    public function __construct(
        AuthenticationService $auth,
        array $config,
        callable $responseFactory
    ) {
        $this->auth = $auth;
        $this->config = $config;

        // Ensures type safety of the composed factory
        $this->responseFactory = function () use ($responseFactory) : ResponseInterface {
            return $responseFactory();
        };
    }

    public function authenticate(ServerRequestInterface $request) : ?UserInterface
    {
        if ('POST' === strtoupper($request->getMethod())) {
            return $this->initiateAuthentication($request);
        }

        return $this->auth->hasIdentity()
            ? $this->generateUser($this->auth->getIdentity(), [])
            : null;
    }

    public function unauthorizedResponse(ServerRequestInterface $request) : ResponseInterface
    {
        return ($this->responseFactory)()
            ->withHeader(
                'Location',
                $this->config['redirect']
            )
            ->withStatus(301);
    }

    private function initiateAuthentication(ServerRequestInterface $request) : ?UserInterface
    {
        $params = $request->getParsedBody();
        $username = $this->config['username'] ?? 'username';
        $password = $this->config['password'] ?? 'password';

        if (! isset($params[$username]) || ! isset($params[$password])) {
            return null;
        }

        $this->auth->getAdapter()->setIdentity($params[$username]);
        $this->auth->getAdapter()->setCredential($params[$password]);

        $result = $this->auth->authenticate();
        if (! $result->isValid()) {
            return null;
        }

        // @todo the role is missing
        return $this->generateUser($result->getIdentity(), []);
    }
}
