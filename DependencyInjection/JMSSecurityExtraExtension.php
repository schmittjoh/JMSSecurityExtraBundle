<?php

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
        }
    }

    private function getConfigTree()
    {
        $tb = new TreeBuilder();

        return $tb
            ->root('jms_security_extra', 'array')
                ->booleanNode('secure_controllers')->defaultTrue()->end()
                ->booleanNode('secure_all_services')->defaultFalse()->end()
            ->end()
            ->buildTree();
    }
}