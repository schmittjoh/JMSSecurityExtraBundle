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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $tb
            ->root('jms_security_extra')
                ->validate()
                    ->always(function($v) {
                        if ($v['method_access_control'] && !$v['expressions']) {
                            throw new \Exception('You need to enable expressions if you want to configure method access via the DI config.');
                        }

                        return $v;
                    })
                ->end()
                ->fixXmlConfig('iddqd_alias', 'iddqd_aliases')
                ->children()
                    ->arrayNode('iddqd_aliases')
                        ->performNoDeepMerging()
                        ->beforeNormalization()->ifString()->then(function($v) { return array('value' => $v); })->end()
                        ->beforeNormalization()
                            ->ifTrue(function($v) { return is_array($v) && isset($v['value']); })
                            ->then(function($v) { return preg_split('/\s*,\s*/', $v['value']); })
                        ->end()
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
                ->children()
                    ->booleanNode('secure_all_services')->defaultFalse()->end()
                    ->booleanNode('enable_iddqd_attribute')->defaultFalse()->end()
                    ->arrayNode('iddqd_ignore_roles')
                        ->defaultValue(array('ROLE_PREVIOUS_ADMIN'))
                        ->prototype('scalar')
                        ->end()
                    ->end()
                    ->scalarNode('cache_dir')->cannotBeEmpty()->defaultValue('%kernel.cache_dir%/jms_security')->end()
                    ->booleanNode('expressions')->defaultFalse()->end()
                    ->arrayNode('voters')
                        ->addDefaultsIfNotSet()
                        ->canBeUnset()
                        ->children()
                            ->booleanNode('disable_authenticated')->defaultFalse()->end()
                            ->booleanNode('disable_role')->defaultFalse()->end()
                            ->booleanNode('disable_acl')->defaultFalse()->end()
                        ->end()
                    ->end()
                    ->arrayNode('method_access_control')
                        ->beforeNormalization()
                            ->always(function ($node) {
                                /** This is a backward compatibility layer */
                                if ($node) {
                                    foreach ($node as $key => $value) {
                                        if (is_string($value)) {
                                            $node[$key] = array(
                                                'pre_authorize' => $value
                                            );
                                        }
                                    }
                                }
 
                                return $node;
                            })
                        ->end()
                        ->useAttributeAsKey('pattern')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('pattern')->end()
                                ->scalarNode('pre_authorize')->cannotBeEmpty()->end()
                                ->arrayNode('secure')
                                    ->fixXmlConfig('role')
                                    ->children()
                                        ->arrayNode('roles')->prototype('scalar')->end()
                                    ->end()
                                ->arrayNode('secure_param')
                                    ->fixXmlConfig('permission')
                                    ->children()
                                        ->scalarNode('name')->end()
                                        ->arrayNode('permissions')->prototype('scalar')->end()
                                    ->end()
                                ->arrayNode('secure_return')
                                    ->fixXmlConfig('permission')
                                    ->children()
                                        ->arrayNode('permissions')->prototype('scalar')->end()
                                    ->end()
                                ->arrayNode('run_as')
                                    ->fixXmlConfig('role')
                                    ->children()
                                        ->arrayNode('roles')->prototype('scalar')->end()
                                    ->end()
                                ->booleanNode('satisfies_parent_security_policy')->end()
                            ->end()
                        ->end()
                    ->arrayNode('util')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('secure_random')
                                ->children()
                                    ->scalarNode('connection')->cannotBeEmpty()->end()
                                    ->scalarNode('table_name')->defaultValue('seed_table')->cannotBeEmpty()->end()
                                    ->scalarNode('seed_provider')->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $tb;
    }
}
