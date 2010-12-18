<?php

namespace Bundle\JMS\SecurityExtraBundle\DependencyInjection\Compiler;

use Bundle\JMS\SecurityExtraBundle\Analysis\ServiceAnalyzer;

use Bundle\JMS\SecurityExtraBundle\Mapping\ServiceMetadata;

use Bundle\JMS\SecurityExtraBundle\Mapping\ClassMetadata;
use Bundle\JMS\SecurityExtraBundle\Generator\ProxyClassGenerator;
use Bundle\JMS\SecurityExtraBundle\Mapping\Driver\DriverChain;
use \PHP_Depend;
use \PHP_Depend_Util_Configuration;
use \ReflectionClass;
use \ReflectionMethod;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Resource\FileResource;
use Symfony\Component\Security\SecurityContext;

/*
 * Copyright 2010 Johannes M. Schmitt
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

/**
 * Modifies the container, and sets the proxy classes where needed
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class SecureMethodInvocationsPass implements CompilerPassInterface
{
    protected $cacheDir;
    protected $generator;

    public function __construct($cacheDir)
    {
        $cacheDir .= '/security/';
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        if (false === is_writable($cacheDir)) {
            die ('Cannot write to cache folder: '.$cacheDir);
        }
        $this->cacheDir = $cacheDir;

        $this->generator = new ProxyClassGenerator();
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->getDefinitions() as $definition) {
            $this->processDefinition($container, $definition);
        }
    }

    protected function processDefinition(ContainerBuilder $container, Definition $definition)
    {
        if (null === $class = $definition->getClass()) {
            return;
        }

        $analyzer = new ServiceAnalyzer($definition->getClass(), $this->cacheDir.'meta/');
        $analyzer->analyze();
        $metadata = $analyzer->getMetadata();

        if (true === $metadata->isProxyRequired()) {
            list($newClassName, $content) = $this->generator->generate($definition, $metadata);
            file_put_contents($this->cacheDir.$newClassName.'.php', $content);
            $definition->setClass('Bundle\\JMS\\SecurityExtraBundle\\Proxy\\'.$newClassName);
            $definition->addMethodCall('jmsSecurityExtraBundle__setSecurityContext', array(new Reference('security.context')));
        }
    }
}