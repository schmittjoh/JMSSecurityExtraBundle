<?php

namespace JMS\SecurityExtraBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Configuration\Processor;
use Symfony\Component\DependencyInjection\Configuration\Builder\TreeBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class JMSSecurityExtraExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $tb = new TreeBuilder();
        $configTree = $tb->root('security_extra:config', 'array')
                        ->fixXmlConfig('service')
                        ->arrayNode('services')
                            ->beforeNormalization()
                                ->ifTrue(function($v) {
                                    return is_array($v) && is_int(key($v)) && is_string(reset($v));
                                })
                                ->then(function($v) {
                                    return array_flip($v);
                                })
                            ->end()
                            ->useAttributeAsKey('id')
                            ->prototype('array')
                                ->beforeNormalization()
                                    ->ifTrue(function($v) { return true; })
                                    ->then(function($v) { return array(); })
                                ->end()
                            ->end()
                        ->end()
                      ->end()
                      ->buildTree();

        $processor = new Processor();
        $config = $processor->process($configTree, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(array(__DIR__.'/../Resources/config/')));
        $loader->load('services.xml');

        $container->setParameter('security.secured_services', $config['services']);
    }

    public function getAlias()
    {
        return 'jms_security_extra';
    }

    public function getXsdValidationBasePath()
    {
        return __DIR__.'/../Resources/config/schema';
    }

    public function getNamespace()
    {
        return 'http://www.symfony-project.org/schema/dic/security_extra';
    }
}