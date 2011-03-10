<?php

namespace JMS\SecurityExtraBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class JMSSecurityExtraExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $tb = new TreeBuilder();
        $configTree = $tb
            ->root('security_extra:config', 'array')
                ->booleanNode('secure_all')->defaultFalse()->end()
            ->end()
            ->buildTree();

        $processor = new Processor();
        $config = $processor->process($configTree, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(array(__DIR__.'/../Resources/config/')));
        $loader->load('services.xml');

        $container->setParameter('security.extra.secure_all', $config['secure_all']);
    }
}