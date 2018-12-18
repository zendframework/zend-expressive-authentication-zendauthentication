<?php
/**
 * @see https://github.com/zendframework/zend-exprsesive-authentication-zendauthentication
 *     for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license https://github.com/zendframework/zend-exprsesive-authentication-zendauthentication/blob/master/LICENSE.md
 *     New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Authentication\ZendAuthentication;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Authentication\AuthenticationService;
use Zend\Expressive\Authentication\Exception;
use Zend\Expressive\Authentication\UserInterface;

use function sprintf;

class ZendAuthenticationFactory
{
    public function __invoke(ContainerInterface $container) : ZendAuthentication
    {
        $auth = $container->has(AuthenticationService::class)
            ? $container->get(AuthenticationService::class)
            : null;

        if (null === $auth) {
            throw new Exception\InvalidConfigException(sprintf(
                "The %s service is missing",
                AuthenticationService::class
            ));
        }

        $config = $container->get('config')['authentication'] ?? [];

        if (! isset($config['redirect'])) {
            throw new Exception\InvalidConfigException(
                'The redirect URL is missing for authentication'
            );
        }

        if (! $container->has(UserInterface::class)) {
            throw new Exception\InvalidConfigException(
                'UserInterface factory service is missing for authentication'
            );
        }

        return new ZendAuthentication(
            $auth,
            $config,
            $container->get(ResponseInterface::class),
            $container->get(UserInterface::class)
        );
    }
}
