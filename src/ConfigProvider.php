<?php
/**
 * @see       https://github.com/zendframework/zend-exprsesive-authentication-zendauthentication for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-exprsesive-authentication-zendauthentication/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Authentication\ZendAuthentication;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'authentication' => $this->getAuthenticationConfig(),
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getAuthenticationConfig() : array
    {
        return [
            'redirect' => '', // URL to which to redirect for invalid credentials
        ];
    }

    public function getDependencies() : array
    {
        return [
            'factories' => [
                ZendAuthentication::class => ZendAuthenticationFactory::class,
            ],
        ];
    }
}
