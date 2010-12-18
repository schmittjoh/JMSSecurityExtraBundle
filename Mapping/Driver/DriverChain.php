<?php

namespace Bundle\JMS\SecurityExtraBundle\Mapping\Driver;

use Bundle\JMS\SecurityExtraBundle\Mapping\ClassMetadata;

use \ReflectionClass;
use \ReflectionMethod;

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
 * This loads all metadata that is applicable for a class, or interface.
 * 
 * It also allows to add other metadata sources apart from annotations.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class DriverChain implements DriverInterface
{
    protected $drivers;

    public function __construct()
    {
        $this->drivers = array(
            new AnnotationDriver(),
        );
    }

    public function loadMetadataForClass(ReflectionClass $class)
    {
        foreach ($this->drivers as $driver) {
            if (null !== $metadata = $driver->loadMetadataForClass($class)) {
                return $metadata;
            }
        }

        return null;
    }
}