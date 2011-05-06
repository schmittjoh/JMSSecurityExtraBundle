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

namespace JMS\SecurityExtraBundle\Metadata;

use Metadata\ClassHierarchyMetadata;

/**
 * This class contains metadata for the entire service
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ServiceMetadata extends ClassHierarchyMetadata
{
    public $methodMetadata = array();

    public function addMethodMetadata(MethodMetadata $metadata)
    {
        $this->methodMetadata[$metadata->name] = $metadata;
    }

    public function isProxyRequired()
    {
        foreach ($this->classMetadata as $metadata) {
            if ($metadata->methodMetadata) {
                return true;
            }
        }

        return false;
    }
}