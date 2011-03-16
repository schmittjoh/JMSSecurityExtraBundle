<?php

namespace JMS\SecurityExtraBundle\Mapping;

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

/**
 * This class contains metadata for the entire service
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ServiceMetadata
{
    private $classes;
    private $methods;

    public function __construct()
    {
        $this->classes = array();
    }

    public function addMetadata(ClassMetadata $metadata)
    {
        $this->classes[$metadata->getReflection()->getName()] = $metadata;
    }

    public function getClasses()
    {
        return $this->classes;
    }

    public function getClassMetadata($class)
    {
        if (!isset($this->classes[$class])) {
            throw new \InvalidArgumentException(sprintf('The class "%s" does not belong to this service.', $class));
        }

        return $this->classes[$class];
    }

    public function addMethod($name, MethodMetadata $metadata)
    {
        $this->methods[$name] = $metadata;
    }

    public function getMethods()
    {
        return $this->methods;
    }

    public function isProxyRequired()
    {
        foreach ($this->classes as $class) {
            if (true === $class->hasMethods()) {
                return true;
            }
        }

        return false;
    }
}