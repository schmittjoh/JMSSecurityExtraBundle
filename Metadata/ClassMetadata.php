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

use Metadata\ClassMetadata as BaseMetadata;

/**
 * Contains class metadata information
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ClassMetadata extends BaseMetadata
{
    public function merge(ClassMetadata $metadata)
    {
        if (false === $metadata->reflection->isInterface()) {
            throw new \InvalidArgumentException('You can only merge metadata from interfaces.');
        }
        if (false === $this->reflection->implementsInterface($metadata->reflection->getName())) {
            throw new \InvalidArgumentException(sprintf('"%s" does not implement "%s".', $this->reflection->getName(), $metadata->reflection->getName()));
        }

        foreach ($metadata->methodMetadata as $name => $method) {
            if (!isset($this->methodMetadata[$name])) {
                $this->methodMetadata[$name] = new MethodMetadata($metadata->name, $name);
            }

            $this->methodMetadata[$name]->merge($method);
        }
    }
}