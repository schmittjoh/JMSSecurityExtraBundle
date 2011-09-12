<?php

namespace JMS\SecurityExtraBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $tb
            ->root('jms_security_extra')
                ->children()
                    ->booleanNode('secure_all_services')->defaultFalse()->end()
                    ->booleanNode('enable_iddqd_attribute')->defaultFalse()->end()
                    ->scalarNode('cache_dir')->cannotBeEmpty()->defaultValue('%kernel.cache_dir%/jms_security')->end()
                ->end()
            ->end()
        ;

        return $tb;
    }
}