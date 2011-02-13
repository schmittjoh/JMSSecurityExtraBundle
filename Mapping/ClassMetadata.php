<?php

namespace JMS\SecurityExtraBundle\Mapping;

use \PHP_Depend_Code_Class;

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
 * Contains class metadata information
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ClassMetadata
{
    protected $reflection;
    protected $pdependClass;
    protected $methods;

    public function __construct(\ReflectionClass $class)
    {
        $this->reflection = $class;
        $this->methods = array();
    }

    public function addMethod($name, MethodMetadata $method)
    {
        $this->methods[$name] = $method;
    }

    public function getMethods()
    {
        return $this->methods;
    }

    public function getMethod($name)
    {
        return $this->methods[$name];
    }

    public function getPdependClass()
    {
        return $this->pdependClass;
    }

    public function getReflection()
    {
        return $this->reflection;
    }

    public function hasMethod($name)
    {
        return isset($this->methods[$name]);
    }
    
    public function hasMethods()
    {
        return count($this->methods) > 0;
    }

    public function merge(ClassMetadata $metadata)
    {
        $reflection = $metadata->getReflection();

        if (false === $reflection->isInterface()) {
            throw new \InvalidArgumentException('You can only merge metadata from interfaces.');
        }
        if (false === $this->reflection->implementsInterface($reflection->getName())) {
            throw new \InvalidArgumentException(sprintf('"%s" does not implement "%s".', $this->reflection->getName(), $reflection->getName()));
        }

        foreach ($metadata->getMethods() as $name => $method) {
            if (!isset($this->methods[$name])) {
                $this->methods[$name] = new MethodMetadata($this->reflection->getMethod($name));
            }

            $this->methods[$name]->merge($method);
        }
    }

    public function setPdependClass(PHP_Depend_Code_Class $class)
    {
        $this->pdependClass = $class;
    }
}