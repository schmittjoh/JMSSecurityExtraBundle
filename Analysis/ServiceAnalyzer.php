<?php

namespace Bundle\JMS\SecurityExtraBundle\Analysis;

use Bundle\JMS\SecurityExtraBundle\Mapping\ClassMetadata;
use Bundle\JMS\SecurityExtraBundle\Mapping\ServiceMetadata;
use Bundle\JMS\SecurityExtraBundle\Mapping\Driver\DriverChain;
use \PHP_Depend;
use \PHP_Depend_Util_Configuration;
use \ReflectionClass;

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
 * Analyzes a service class including parent classes. The gather information
 * is then used to built a proxy class if necessary.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ServiceAnalyzer
{
    protected $reflection;
    protected $files;
    protected $driverChain;
    protected $analyzed;
    protected $hierarchy;
    protected $metadata;
    
    public function __construct($class)
    {
        $this->reflection = new ReflectionClass($class);
        $this->files = array();
        $this->driverChain = new DriverChain();
        $this->analyzed = false;
    }
    
    public function analyze()
    {
        $this->hierarchy = $this->buildHierarchy($this->reflection);
        $this->metadata = $this->collectServiceMetadata($this->hierarchy);
        
        if ($this->metadata->isProxyRequired()) {
            $this->validateConfiguration();
            $this->validateControlFlow();
        }
    }
    
    public function getFiles()
    {
        if (!$this->ananlyzed) {
            throw new \LogicException('Data not yet available, run analyze() first.');
        }
        
        return $this->files;
    }

    public function getMetadata()
    {
        return $this->metadata;
    }
    
    protected function validateConfiguration()
    {
        $secureMethods = array();
        foreach ($this->metadata->getClasses() as $class) {
            foreach ($class->getMethods() as $name => $method) {
                if (!isset($secureMethods[$name])) {
                    $this->metadata->addMethod($name, $method);
                    $secureMethods[$name] = $method;
                } else {
                    if (false === $secureMethods[$name]->satisfiesParentSecurityPolicy()) {
                        throw new \RuntimeException(sprintf('Unresolved security metadata conflict for method "%s". Please copy the respective annotations, and add @SatisfiesParentSecurityPolicy to the child method.', $name));
                    }
                }
            }
        }
    }
    
    /**
     * We only due a very lightweight control flow analysis. If we stumble upon
     * something suspicous, we will simply break, and require additional metadata
     * to resolve the situation.
     * 
     * @throws \RuntimeException
     * @return void
     */
    protected function validateControlFlow()
    {
        $config = new \stdClass;
        $config->cache = new \stdClass;
        $config->cache->driver = 'memory';
        
        $pdepend = new PHP_Depend(new PHP_Depend_Util_Configuration($config));
        foreach ($this->files as $file) {
            $pdepend->addFile($file);
        }

        $rootClassName = $this->reflection->getName();
        $rootClass = null;
        foreach ($pdepend->analyze() as $package) {
            foreach ($package->getClasses() as $class) {
                if ($rootClassName === $class->getPackageName().'\\'.$class->getName()) {
                    $rootClass = $class;
                    break 2;
                }
            }
        }

        if (null === $rootClass) {
            throw new \RuntimeException('Could not find root class: '.$rootClassName);
        }

        $secureMethods = $this->metadata->getMethods();
        do {
            foreach ($rootClass->getMethods() as $method) {
                if (!isset($secureMethods[$method->getName()])) {
                    continue;
                }
    
                if ($secureMethods[$method->getName()]->getReflection()->getDeclaringClass()->getName() !== $rootClass->getPackageName().'\\'.$rootClass->getName()) {
                    throw new \RuntimeException(sprintf('You have overridden a secured method "%s" in "%s". Please copy over the applicable security metadata, and also add @SatisfiesParentSecurityPolicy.', $method->getName(), $rootClass->getName()));
                }
                
                unset($secureMethods[$method->getName()]);
            }
        } while (count($secureMethods) > 0 && null !== $rootClass = $rootClass->getParentClass());
    }

    protected function buildHierarchy(ReflectionClass $reflection)
    {
        $result = array($reflection);
        
        foreach ($reflection->getInterfaces() as $interface) {
            $result = array_merge($result, $this->buildHierarchy($interface));
        }

        if (false !== $parent = $reflection->getParentClass()) {
            $result = array_merge($result, $this->buildHierarchy($parent));
        }
        
        return $result;
    }

    protected function collectServiceMetadata(array $hierarchy)
    {
        $metadata = new ServiceMetadata();
        $classMetadata = null;
        foreach ($hierarchy as $class) {
            $this->files[] = $class->getFileName();
            
            if (null === $classMetadata) {
                $classMetadata = new ClassMetadata($class);
            }

            if (null !== $aMetadata = $this->driverChain->loadMetadataForClass($class)) {
                if ($class->isInterface()) {
                    $classMetadata->merge($aMetadata);
                } else {
                    $metadata->addMetadata($classMetadata);
                    $classMetadata = $aMetadata;
                }
            }
        }
        $metadata->addMetadata($classMetadata);
        
        return $metadata;
    }    
}