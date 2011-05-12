<?php

/*
 * Copyright 2010 Johannes M. Schmitt <schmittjoh@gmail.com>
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

namespace JMS\SecurityExtraBundle\DependencyInjection\Compiler;

use JMS\SecurityExtraBundle\Analysis\ServiceAnalyzer;
use JMS\SecurityExtraBundle\Mapping\ServiceMetadata;
use JMS\SecurityExtraBundle\Mapping\ClassMetadata;
use JMS\SecurityExtraBundle\Generator\ProxyClassGenerator;
use JMS\SecurityExtraBundle\Mapping\Driver\DriverChain;
use \ReflectionClass;
use \ReflectionMethod;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Modifies the container, and sets the proxy classes where needed
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class SecureMethodInvocationsPass implements CompilerPassInterface
{
    private $cacheDir;
    private $generator;
    private $cacheMetadata;

    public function __construct($cacheDir)
    {
        $cacheDir .= '/security/';
        if (!file_exists($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }
        if (false === is_writable($cacheDir)) {
            throw new \RuntimeException('Cannot write to cache folder: '.$cacheDir);
        }
        $this->cacheDir = $cacheDir;

        if (!file_exists($cacheDir.'SecurityProxies/')) {
            @mkdir($cacheDir.'SecurityProxies/', 0777, true);
        }
        if (false === is_writeable($cacheDir.'SecurityProxies/')) {
            throw new \RuntimeException('Cannot write to cache folder: '.$cacheDir.'SecurityProxies/');
        }

        $this->generator = new ProxyClassGenerator();
        $this->createOrLoadCacheMetadata();
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('security.access.method_interceptor')) {
            return;
        }

        $services = $container->findTaggedServiceIds('security.secure_service');
        $secureNotAll = !$container->getParameter('security.extra.secure_all_services');

        $parameterBag = $container->getParameterBag();
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($secureNotAll && !isset($services[$id])) {
                continue;
            }

            if ((null === $class = $definition->getClass()) || !class_exists($class)) {
                if ($secureNotAll) {
                    throw new \RuntimeException(sprintf('Could not find class "%s" for "%s".', $class, $id));
                }

                continue;
            }

            if (null !== $definition->getFactoryMethod() || $definition->isAbstract() || $definition->isSynthetic()) {
                if ($secureNotAll) {
                    throw new \RuntimeException(sprintf('You cannot secure service "%s", because it is either created by a factory, or an abstract/synthetic service.', $id));
                }

                continue;
            }

            $this->processDefinition($container, $id, $definition);
        }

        $this->writeCacheMetadata();
    }

    private function processDefinition(ContainerBuilder $container, $id, Definition $definition)
    {
        if ($this->needsReAssessment($id, $definition)) {
            $analyzer = new ServiceAnalyzer($definition->getClass());
            $analyzer->analyze();

            $files = array();
            foreach ($analyzer->getFiles() as $file) {
                $container->addResource($file = new FileResource($file));
                $files[] = $file;
            }

            $metadata = $analyzer->getMetadata();
            $proxyClass = $path = null;
            if (true === $metadata->isProxyRequired()) {
                list($newClassName, $content) = $this->generator->generate($definition, $metadata);
                file_put_contents($path = $this->cacheDir.'SecurityProxies/'.$newClassName.'.php', $content);
                $definition->setFile($path);
                $definition->setClass($proxyClass = 'SecurityProxies\\'.$newClassName);
                $definition->addMethodCall('jmsSecurityExtraBundle__setMethodSecurityInterceptor', array(new Reference('security.access.method_interceptor')));
            } else if (isset($this->cacheMetadata[$id]['proxy_class'])) {
                @unlink($this->cacheDir.$this->cacheMetadata[$id]['proxy_class'].'.php');
            }

            $this->cacheMetadata[$id] = array(
                'class' => $definition->getClass(),
                'proxy_class' => $proxyClass,
                'proxy_file'  => $path,
            		'analyze_time' => time(),
                'files' => $files,
            );
        } else {
            foreach ($this->cacheMetadata[$id]['files'] as $file) {
                $container->addResource($file);
            }

            if (null !== $proxyClass = $this->cacheMetadata[$id]['proxy_class']) {
                $definition->setFile($this->cacheMetadata[$id]['proxy_file']);
                $definition->setClass($proxyClass);
                $definition->addMethodCall('jmsSecurityExtraBundle__setMethodSecurityInterceptor', array(new Reference('security.access.method_interceptor')));
            }
        }
    }

    private function needsReAssessment($id, Definition $definition)
    {
        if (!isset($this->cacheMetadata[$id])) {
            return true;
        }

        $metadata = $this->cacheMetadata[$id];
        if ($metadata['class'] !== $definition->getClass()) {
            return true;
        }

        $lastAnalyzed = $metadata['analyze_time'];
        foreach ($metadata['files'] as $file) {
            if (false === $file->isFresh($lastAnalyzed)) {
                return true;
            }
        }

        return false;
    }

    private function createOrLoadCacheMetadata()
    {
        if (file_exists($this->cacheDir.'cache.meta')) {
            if (!is_readable($this->cacheDir.'cache.meta')) {
                throw new \RuntimeException('Cannot load security cache meta data from: '.$this->cacheDir.'cache.meta');
            }

            $this->cacheMetadata = unserialize(file_get_contents($this->cacheDir.'cache.meta'));
        } else {
            set_time_limit(0);
            $this->cacheMetadata = array();
        }
    }

    private function writeCacheMetadata()
    {
        if (false === file_put_contents($this->cacheDir.'cache.meta', serialize($this->cacheMetadata))) {
            throw new \RuntimeException('Could not write to cache file: '.$this->cacheDir.'cache.meta');
        }
    }
}