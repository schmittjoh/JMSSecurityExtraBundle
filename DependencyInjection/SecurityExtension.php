<?php

/*
 * Copyright 2011 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\SecurityExtraBundle\DependencyInjection;

use Symfony\Bundle\SecurityBundle\DependencyInjection\FactoryConfiguration;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension as BaseSecurityExtension;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\FileLocator;

/**
 * Enhances the default security extension.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class SecurityExtension extends BaseSecurityExtension
{
    private $parentRef;

    public function __construct()
    {
        $this->parentRef = new \ReflectionClass('Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension');
    }

    public function getAlias()
    {
        return 'security';
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        if (!array_filter($configs)) {
            return;
        }

        if (false === $this->parentRef->hasProperty('userProviderFactories')) {
            // first assemble the factories
            $factoriesConfig = new FactoryConfiguration();
            $config = $this->processConfiguration($factoriesConfig, $configs);
            $factories = $this->invokeParent('createListenerFactories', array($container, $config));

            // normalize and merge the actual configuration
            $mainConfig = new MainConfiguration($factories);
        } else {
            // normalize and merge the actual configuration
            $mainConfig = new MainConfiguration($this->getField('factories'), $this->getField('userProviderFactories'));
        }

        $config = $this->processConfiguration($mainConfig, $configs);

        // load services
        $loader = new XmlFileLoader($container, new FileLocator(
            dirname($this->parentRef->getFilename()).'/../Resources/config'));
        $loader->load('security.xml');
        $loader->load('security_listeners.xml');
        $loader->load('security_rememberme.xml');
        $loader->load('templating_php.xml');
        $loader->load('templating_twig.xml');
        $loader->load('collectors.xml');

        // set some global scalars
        $container->setParameter('security.access.denied_url', $config['access_denied_url']);
        $container->setParameter('security.authentication.manager.erase_credentials', $config['erase_credentials']);
        $container->setParameter('security.authentication.session_strategy.strategy', $config['session_fixation_strategy']);
        $container
            ->getDefinition('security.access.decision_manager')
            ->addArgument($config['access_decision_manager']['strategy'])
            ->addArgument($config['access_decision_manager']['allow_if_all_abstain'])
            ->addArgument($config['access_decision_manager']['allow_if_equal_granted_denied'])
        ;
        $container->setParameter('security.access.always_authenticate_before_granting', $config['always_authenticate_before_granting']);
        $container->setParameter('security.authentication.hide_user_not_found', $config['hide_user_not_found']);

        $this->invokeParent('createFirewalls', array($config, $container));
        $this->createAuthorization($config, $container);
        $this->invokeParent('createRoleHierarchy', array($config, $container));

        if (isset($config['util']['secure_random'])) {
            $this->invokeParent('configureSecureRandom', array($config['util']['secure_random'], $container));
        }

        if ($config['encoders']) {
            $this->invokeParent('createEncoders', array($config['encoders'], $container));
        }

        // load ACL
        if (isset($config['acl'])) {
            $this->invokeParent('aclLoad', array($config['acl'], $container));
        }

        // add some required classes for compilation
        $this->addClassesToCompile(array(
            'Symfony\\Component\\Security\\Http\\Firewall',
            'Symfony\\Component\\Security\\Http\\FirewallMapInterface',
            'Symfony\\Component\\Security\\Core\\SecurityContext',
            'Symfony\\Component\\Security\\Core\\SecurityContextInterface',
            'Symfony\\Component\\Security\\Core\\User\\UserProviderInterface',
            'Symfony\\Component\\Security\\Core\\Authentication\\AuthenticationProviderManager',
            'Symfony\\Component\\Security\\Core\\Authentication\\AuthenticationManagerInterface',
            'Symfony\\Component\\Security\\Core\\Authorization\\AccessDecisionManager',
            'Symfony\\Component\\Security\\Core\\Authorization\\AccessDecisionManagerInterface',
            'Symfony\\Component\\Security\\Core\\Authorization\\Voter\\VoterInterface',

            'Symfony\\Bundle\\SecurityBundle\\Security\\FirewallMap',
            'Symfony\\Bundle\\SecurityBundle\\Security\\FirewallContext',

            'Symfony\\Component\\HttpFoundation\\RequestMatcher',
            'Symfony\\Component\\HttpFoundation\\RequestMatcherInterface',
        ));
    }

    private function createAuthorization($config, ContainerBuilder $container)
    {
        if (!$config['access_control']) {
            return;
        }

        $this->addClassesToCompile(array(
            'Symfony\\Component\\Security\\Http\\AccessMap',
        ));

        foreach ($config['access_control'] as $access) {
            $matcher = $this->invokeParent('createRequestMatcher', array(
                $container,
                $access['path'],
                $access['host'],
                count($access['methods']) === 0 ? null : $access['methods'],
                $access['ip']
            ));

            if (isset($access['roles'])) {
                $attributes = $access['roles'];
            } else {
                $def = new DefinitionDecorator('security.expressions.expression');
                $def->addArgument($access['access']);
                $container->setDefinition($exprId = 'security.expressions.expression.'.sha1($access['access']), $def);

                $attributes = array(new Reference($exprId));
            }

            $container->getDefinition('security.access_map')
                      ->addMethodCall('add', array($matcher, $attributes, $access['requires_channel']));
        }
    }

    private function getField($field)
    {
        $field = $this->parentRef->getProperty($field);
        $field->setAccessible(true);

        return $field->getValue($this);
    }

    private function invokeParent($method, array $args = array())
    {
        $method = $this->parentRef->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($this, $args);
    }
}