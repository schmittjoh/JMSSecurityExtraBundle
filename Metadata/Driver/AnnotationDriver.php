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

namespace JMS\SecurityExtraBundle\Metadata\Driver;

use Doctrine\Common\Annotations\Reader;
use JMS\SecurityExtraBundle\Annotation\RunAs;
use JMS\SecurityExtraBundle\Annotation\SatisfiesParentSecurityPolicy;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use JMS\SecurityExtraBundle\Annotation\SecureReturn;
use JMS\SecurityExtraBundle\Metadata\ClassMetadata;
use JMS\SecurityExtraBundle\Metadata\MethodMetadata;
use Metadata\Driver\DriverInterface;
use \ReflectionClass;
use \ReflectionMethod;

/**
 * Loads security annotations and converts them to metadata
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class AnnotationDriver implements DriverInterface
{
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function loadMetadataForClass(ReflectionClass $reflection)
    {
        $metadata = new ClassMetadata($reflection->getName());

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
            // check if the method was defined on this class
            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }

            $annotations = $this->reader->getMethodAnnotations($method);

            if ($annotations && null !== $methodMetadata = $this->convertMethodAnnotations($method, $annotations)) {
                $metadata->addMethodMetadata($methodMetadata);
            }
        }

        return $metadata;
    }

    private function convertMethodAnnotations(\ReflectionMethod $method, array $annotations)
    {
        $parameters = array();
        foreach ($method->getParameters() as $index => $parameter) {
            $parameters[$parameter->getName()] = $index;
        }

        $methodMetadata = new MethodMetadata($method->getDeclaringClass()->getName(), $method->getName());
        $hasSecurityMetadata = false;
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Secure) {
                $methodMetadata->roles = $annotation->roles;
                $hasSecurityMetadata = true;
            } else if ($annotation instanceof SecureParam) {
                if (!isset($parameters[$annotation->name])) {
                    throw new \InvalidArgumentException(sprintf('The parameter "%s" does not exist for method "%s".', $annotation->name, $method->getName()));
                }

                $methodMetadata->addParamPermissions($parameters[$annotation->name], $annotation->permissions);
                $hasSecurityMetadata = true;
            } else if ($annotation instanceof SecureReturn) {
                $methodMetadata->returnPermissions = $annotation->permissions;
                $hasSecurityMetadata = true;
            } else if ($annotation instanceof SatisfiesParentSecurityPolicy) {
                $methodMetadata->satisfiesParentSecurityPolicy = true;
                $hasSecurityMetadata = true;
            } else if ($annotation instanceof RunAs) {
                $methodMetadata->runAsRoles = $annotation->roles;
                $hasSecurityMetadata = true;
            }
        }

        return $hasSecurityMetadata ? $methodMetadata : null;
    }
}