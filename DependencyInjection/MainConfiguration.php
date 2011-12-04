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

use Symfony\Component\HttpKernel\Kernel;

use Symfony\Bundle\SecurityBundle\DependencyInjection\MainConfiguration as BaseConfiguration;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * This class contains the configuration information for the following tags:
 *
 *   * security.config
 *   * security.acl
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class MainConfiguration extends BaseConfiguration
{
    private $parentRef;

    /**
     * Constructor.
     *
     * @param array $factories
     */
    public function __construct(array $factories)
    {
        parent::__construct($factories);

        $this->parentRef = new \ReflectionClass('Symfony\Bundle\SecurityBundle\DependencyInjection\MainConfiguration');
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $rootNode = $tb->root('security');

        $rootNode
            ->children()
                ->scalarNode('access_denied_url')->defaultNull()->end()
                ->scalarNode('session_fixation_strategy')->cannotBeEmpty()->defaultValue('migrate')->end()
                ->booleanNode('hide_user_not_found')->defaultTrue()->end()
                ->booleanNode('always_authenticate_before_granting')->defaultFalse()->end()
                ->booleanNode('erase_credentials')->defaultTrue()->end()
                ->arrayNode('access_decision_manager')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('strategy')->defaultValue('affirmative')->end()
                        ->booleanNode('allow_if_all_abstain')->defaultFalse()->end()
                        ->booleanNode('allow_if_equal_granted_denied')->defaultTrue()->end()
                    ->end()
                ->end()
            ->end()

            // add a faux-entry for factories, so that no validation error is thrown
            ->fixXmlConfig('factory', 'factories')
            ->children()
                ->arrayNode('factories')->ignoreExtraKeys()->end()
            ->end()
        ;

        $this->invokeParent('addAclSection', array($rootNode));
        $this->invokeParent('addEncodersSection', array($rootNode));
        $this->invokeParent('addProvidersSection', array($rootNode));
        $this->invokeParent('addFirewallsSection', array($rootNode, $this->getField('factories')));
        $this->addAccessControlSection($rootNode);
        $this->invokeParent('addRoleHierarchySection', array($rootNode));

        if ($this->parentRef->hasMethod('addUtilSection')) {
            $this->invokeParent('addUtilSection', array($rootNode));
        }

        return $tb;
    }

    private function addAccessControlSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->fixXmlConfig('rule', 'access_control')
            ->children()
                ->arrayNode('access_control')
                    ->cannotBeOverwritten()
                    ->prototype('array')
                        ->fixXmlConfig('role')
                        ->validate()
                            ->always(function($v) {
                                if (!empty($v['roles']) && isset($v['access'])) {
                                    throw new \Exception('"roles", and "access" cannot be set at the same time.');
                                }

                                if (empty($v['roles'])) {
                                    unset($v['roles']);
                                }

                                return $v;
                            })
                        ->end()
                        ->children()
                            ->scalarNode('requires_channel')->defaultNull()->end()
                            ->scalarNode('path')->defaultNull()->end()
                            ->scalarNode('host')->defaultNull()->end()
                            ->scalarNode('ip')->defaultNull()->end()
                            ->arrayNode('methods')
                                ->beforeNormalization()->ifString()->then(function($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('roles')
                                ->beforeNormalization()->ifString()->then(function($v) { return preg_split('/\s*,\s*/', $v); })->end()
                                ->prototype('scalar')->end()
                            ->end()
                            ->scalarNode('access')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
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
