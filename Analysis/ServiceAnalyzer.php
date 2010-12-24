<?php

namespace Bundle\JMS\SecurityExtraBundle\Analysis;

use Bundle\JMS\SecurityExtraBundle\Mapping\MethodMetadata;

use Bundle\JMS\SecurityExtraBundle\Mapping\ClassMetadata;
use Bundle\JMS\SecurityExtraBundle\Mapping\ServiceMetadata;
use Bundle\JMS\SecurityExtraBundle\Mapping\Driver\DriverChain;
use \PHP_Depend;
use \PHP_Depend_Code_Class;
use \PHP_Depend_Code_AbstractClassOrInterface;
use \PHP_Depend_Code_Interface;
use \PHP_Depend_Util_Configuration;
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
    protected $reflection;
    protected $files;
    protected $driver;
    protected $pdepend;
    protected $analyzed;
    protected $hierarchy;
    protected $metadata;
    protected $cacheDir;

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
        $this->analyzeCode();
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

    protected function buildClassHierarchy()
    {
        $rootClassName = $this->reflection->getName();
        $rootClass = null;

        foreach ($this->pdepend->getPackages() as $package) {
            foreach ($package->getClasses() as $class) {
                if ($rootClassName === $this->getFullyQualifiedClassname($class)) {
                    $rootClass = $class;
                    break 2;
                }
            }
        }

        if (null === $rootClass) {
            throw new \RuntimeException('Could not locate root class: '.$rootClassName);
        }

        $addedInterfaces = array();
        do {
            $this->hierarchy[] = $rootClass;

            foreach ($rootClass->getDependencies() as $interface) {
                if (!$interface instanceof PHP_Depend_Code_Interface) {
                    continue;
                }

                if (!isset($addedInterfaces[$interface->getUUID()])) {
                    $this->hierarchy[] = $interface;
                    $addedInterfaces[$interface->getUUID()] = true;
                }
            }
        } while (null !== $rootClass = $rootClass->getParentClass());
    }

    protected function analyzeCode()
    {
        $config = new \stdClass;
        $config->cache = new \stdClass;

        if (null === $this->cacheDir) {
            $config->cache->driver = 'memory';
        } else {
            $config->cache->driver = 'file';
            $config->cache->location = $this->cacheDir;
        }

        $this->pdepend = new PHP_Depend(new PHP_Depend_Util_Configuration($config));
        $this->pdepend->setWithoutAnnotations();

        foreach ($this->files as $file) {
            $this->pdepend->addFile($file);
        }

        $this->pdepend->analyze();
    }

    protected function collectFiles()
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

    protected function normalizeMetadata()
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
                } else if (false === $secureMethods[$name]->satisfiesParentSecurityPolicy()) {
                    throw new \RuntimeException(sprintf('Unresolved security metadata conflict for method "%s::%s" in "%s". Please copy the respective annotations, and add @SatisfiesParentSecurityPolicy to the child method.', $secureMethods[$name]->getReflection()->getDeclaringClass()->getName(), $name, $secureMethods[$name]->getReflection()->getDeclaringClass()->getFileName()));
                }
            }
        }

        foreach ($secureMethods as $name => $method) {
            if ($method->getReflection()->isAbstract()) {
                $previous = null;
                $abstractClass = $method->getReflection()->getDeclaringClass()->getName();
                foreach ($this->hierarchy as $class) {
                    if ($abstractClass === $fqcn = $this->getFullyQualifiedClassname($class)) {
                        $reflectionClass = new ReflectionClass($previous);
                        $methodMetadata = new MethodMetadata($reflectionClass->getMethod($name));
                        $methodMetadata->merge($method);
                        $this->metadata->addMethod($name, $methodMetadata);

                        continue 2;
                    }

                    if ($class instanceof PHP_Depend_Code_Class) {
                        $previous = $fqcn;
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
    protected function analyzeControlFlow()
    {
        $secureMethods = $this->metadata->getMethods();
        $rootClass = $this->hierarchy[0];

        while (true) {
            foreach ($rootClass->getMethods() as $method) {
                if (!isset($secureMethods[$name = $method->getName()])) {
                    continue;
                }

                if ($secureMethods[$name]->getReflection()->getDeclaringClass()->getName() !== $this->getFullyQualifiedClassname($rootClass)) {
                    throw new \RuntimeException(sprintf(
                        'You have overridden a secured method "%s::%s" in "%s". '
                       .'Please copy over the applicable security metadata, and '
                       .'also add @SatisfiesParentSecurityPolicy.',
                        $secureMethods[$name]->getReflection()->getDeclaringClass()->getName(),
                        $name,
                        $this->getFullyQualifiedClassname($rootClass)
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

    protected function getFullyQualifiedClassname(PHP_Depend_Code_AbstractClassOrInterface $class)
    {
        $name = $class->getPackageName().'\\'.$class->getName();
        if (false === class_exists($name, false) && false === interface_exists($name, false)) {
            return $class->getName();
        } else {
            return $name;
        }
    }

    protected function collectServiceMetadata()
    {
        $this->metadata = new ServiceMetadata();
        $classMetadata = null;
        foreach ($this->hierarchy as $class) {
            $reflectionClass = new \ReflectionClass($this->getFullyQualifiedClassname($class));

            if (null === $classMetadata) {
                $classMetadata = new ClassMetadata($reflectionClass);
                $classMetadata->setPdependClass($class);
            }

            if (null !== $aMetadata = $this->driver->loadMetadataForClass($reflectionClass)) {
                if ($reflectionClass->isInterface()) {
                    $classMetadata->merge($aMetadata);
                } else {
                    $this->metadata->addMetadata($classMetadata);

                    $classMetadata = $aMetadata;
                    $classMetadata->setPdependClass($class);
                }
            }
        }
        $this->metadata->addMetadata($classMetadata);
    }
}