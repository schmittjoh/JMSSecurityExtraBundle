<?php

namespace JMS\SecurityExtraBundle\Analysis;

use JMS\SecurityExtraBundle\Mapping\MethodMetadata;
use JMS\SecurityExtraBundle\Mapping\ClassMetadata;
use JMS\SecurityExtraBundle\Mapping\ServiceMetadata;
use JMS\SecurityExtraBundle\Mapping\Driver\DriverChain;
use \ReflectionClass;

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

/**
 * Analyzes a service class including parent classes. The gathered information
 * is then used to built a proxy class if necessary.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ServiceAnalyzer
{
    private $reflection;
    private $files;
    private $driver;
    private $pdepend;
    private $analyzed;
    private $hierarchy;
    private $metadata;
    private $cacheDir;

    public function __construct($class, $cacheDir = null)
    {
        $this->reflection = new ReflectionClass($class);
        $this->files = array();
        $this->hierarchy = array();
        $this->driver = new DriverChain();
        $this->analyzed = false;
        $this->cacheDir = $cacheDir;
    }

    public function analyze()
    {
        if (true === $this->analyzed) {
            return;
        }

        $this->collectFiles();
        $this->buildClassHierarchy();
        $this->collectServiceMetadata();

        if ($this->metadata->isProxyRequired()) {
            $this->normalizeMetadata();
            $this->analyzeControlFlow();
        }

        $this->analyzed = true;
    }

    public function getFiles()
    {
        if (!$this->analyzed) {
            throw new \LogicException('Data not yet available, run analyze() first.');
        }

        return $this->files;
    }

    public function getMetadata()
    {
        if (!$this->analyzed) {
            throw new \LogicException('Data not yet available, run analyze() first.');
        }

        return $this->metadata;
    }

    private function buildClassHierarchy()
    {
        $hierarchy = array();
        $class = $this->reflection;

        // add classes
        while (false !== $class) {
            $hierarchy[] = $class;
            $class = $class->getParentClass();
        }

        // add interfaces
        $addedInterfaces = array();
        $newHierarchy = array();

        foreach (array_reverse($hierarchy) as $class) {
            foreach ($class->getInterfaces() as $interface) {
                if (isset($addedInterfaces[$interface->getName()])) {
                    continue;
                }
                $addedInterfaces[$interface->getName()] = true;

                $newHierarchy[] = $interface;
            }

            $newHierarchy[] = $class;
        }

        $this->hierarchy = array_reverse($newHierarchy);
    }

    private function collectFiles()
    {
        $this->files[] = $this->reflection->getFileName();

        foreach ($this->reflection->getInterfaces() as $interface) {
            if (false !== $filename = $interface->getFileName()) {
                $this->files[] = $filename;
            }
        }

        $parent = $this->reflection;
        while (false !== $parent = $parent->getParentClass()) {
            if (false !== $filename = $parent->getFileName()) {
                $this->files[] = $filename;
            }
        }
    }

    private function normalizeMetadata()
    {
        $secureMethods = array();
        foreach ($this->metadata->getClasses() as $class) {
            if ($class->getReflection()->isFinal()) {
                throw new \RuntimeException('Final classes cannot be secured.');
            }

            foreach ($class->getMethods() as $name => $method) {
                if ($method->getReflection()->isStatic() || $method->getReflection()->isFinal()) {
                    throw new \RuntimeException('Annotations cannot be defined on final, or static methods.');
                }

                if (!isset($secureMethods[$name])) {
                    $this->metadata->addMethod($name, $method);
                    $secureMethods[$name] = $method;
                } else if ($method->getReflection()->isAbstract()) {
                    $secureMethods[$name]->merge($method);
                } else if (false === $secureMethods[$name]->satisfiesParentSecurityPolicy()
                           && $method->getReflection()->getDeclaringClass()->getName() !== $secureMethods[$name]->getReflection()->getDeclaringClass()->getName()) {
                    throw new \RuntimeException(sprintf('Unresolved security metadata conflict for method "%s::%s" in "%s". Please copy the respective annotations, and add @SatisfiesParentSecurityPolicy to the child method.', $secureMethods[$name]->getReflection()->getDeclaringClass()->getName(), $name, $secureMethods[$name]->getReflection()->getDeclaringClass()->getFileName()));
                }
            }
        }

        foreach ($secureMethods as $name => $method) {
            if ($method->getReflection()->isAbstract()) {
                $previous = null;
                $abstractClass = $method->getReflection()->getDeclaringClass()->getName();
                foreach ($this->hierarchy as $refClass) {
                    if ($abstractClass === $fqcn = $refClass->getName()) {
                        $methodMetadata = new MethodMetadata($previous->getMethod($name));
                        $methodMetadata->merge($method);
                        $this->metadata->addMethod($name, $methodMetadata);

                        continue 2;
                    }

                    if (!$refClass->isInterface() && $this->hasMethod($refClass, $name)) {
                        $previous = $refClass;
                    }
                }
            }
        }
    }

    /**
     * We only perform a very lightweight control flow analysis. If we stumble upon
     * something suspicous, we will simply break, and require additional metadata
     * to resolve the situation.
     *
     * @throws \RuntimeException
     * @return void
     */
    private function analyzeControlFlow()
    {
        $secureMethods = $this->metadata->getMethods();
        $rootClass = $this->hierarchy[0];

        while (true) {
            foreach ($rootClass->getMethods() as $method) {
                if (!$this->hasMethod($rootClass, $method->getName())) {
                    continue;
                }

                if (!isset($secureMethods[$name = $method->getName()])) {
                    continue;
                }

                if ($secureMethods[$name]->getReflection()->getDeclaringClass()->getName() !== $rootClass->getName()) {
                    throw new \RuntimeException(sprintf(
                        'You have overridden a secured method "%s::%s" in "%s". '
                       .'Please copy over the applicable security metadata, and '
                       .'also add @SatisfiesParentSecurityPolicy.',
                        $secureMethods[$name]->getReflection()->getDeclaringClass()->getName(),
                        $name,
                        $rootClass->getName()
                    ));
                }

                unset($secureMethods[$method->getName()]);
            }

            if (null === $rootClass = $rootClass->getParentClass()) {
                break;
            }

            if (0 === count($secureMethods)) {
                break;
            }
        }
    }

    private function collectServiceMetadata()
    {
        $this->metadata = new ServiceMetadata();
        $classMetadata = null;
        foreach ($this->hierarchy as $reflectionClass) {
            if (null === $classMetadata) {
                $classMetadata = new ClassMetadata($reflectionClass);
            }

            if (null !== $aMetadata = $this->driver->loadMetadataForClass($reflectionClass)) {
                if ($reflectionClass->isInterface()) {
                    $classMetadata->merge($aMetadata);
                } else {
                    $this->metadata->addMetadata($classMetadata);

                    $classMetadata = $aMetadata;
                }
            }
        }
        $this->metadata->addMetadata($classMetadata);
    }

    private function hasMethod(\ReflectionClass $class, $name)
    {
        if (!$class->hasMethod($name)) {
            return false;
        }

        return $class->getName() === $class->getMethod($name)->getDeclaringClass()->getName();
    }
}