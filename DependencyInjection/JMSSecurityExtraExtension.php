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

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * JMSSecurityExtraExtension.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class JMSSecurityExtraExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $config = $processor->process($this->getConfigTree(), $configs);

        $loader = new XmlFileLoader($container, new FileLocator(array(__DIR__.'/../Resources/config/')));
        $loader->load('services.xml');

        $container->setParameter('security.extra.secure_all_services', $config['secure_all_services']);

        if (!$config['secure_controllers']) {
            $container->remove('security.extra.controller_listener');

            $this->addClassesToCompile(array(
                'JMS\\SecurityExtraBundle\\Security\\Authorization\\Interception\\MethodInvocation',
                'JMS\\SecurityExtraBundle\\Security\\Authorization\\Interception\\MethodSecurityInterceptor',

                'JMS\\SecurityExtraBundle\\Security\\Authorization\\AfterInvocation\\AfterInvocationManager',
                'JMS\\SecurityExtraBundle\\Security\\Authorization\\AfterInvocation\\AfterInvocationManagerInterface',
                'JMS\\SecurityExtraBundle\\Security\\Authorization\\AfterInvocation\\AfterInvocationProviderInterface',

                'JMS\\SecurityExtraBundle\\Security\\Authorization\\RunAsManager',
                'JMS\\SecurityExtraBundle\\Security\\Authorization\\RunAsManagerInterface',
            ));
        } else {
            $this->addClassesToCompile(array(
                'JMS\\SecurityExtraBundle\\Controller\\ControllerListener',

                'JMS\\SecurityExtraBundle\\Mapping\\Driver\\AnnotationParser',
                'JMS\\SecurityExtraBundle\\Mapping\\Driver\\AnnotationConverter',

                'JMS\\SecurityExtraBundle\\Security\\Authorization\\Interception\\MethodInvocation',
            ));
        }

        if ($config['enable_iddqd_attribute']) {
            $container
                ->getDefinition('security.extra.iddqd_voter')
                ->addTag('security.voter')
            ;

            // FIXME: Also add an iddqd after invocation provider
        }
    }

    private function getConfigTree()
    {
        $tb = new TreeBuilder();

        return $tb
            ->root('jms_security_extra')
                ->children()
                  ->booleanNode('secure_controllers')->defaultTrue()->end()
                  ->booleanNode('secure_all_services')->defaultFalse()->end()
                  ->booleanNode('enable_iddqd_attribute')->defaultFalse()->end()
                ->end()
            ->end()
            ->buildTree();
    }
}