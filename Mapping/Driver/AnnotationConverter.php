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

namespace JMS\SecurityExtraBundle\Mapping\Driver;

use JMS\SecurityExtraBundle\Annotation\RunAs;
use JMS\SecurityExtraBundle\Annotation\SatisfiesParentSecurityPolicy;
use JMS\SecurityExtraBundle\Annotation\SecureReturn;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Mapping\MethodMetadata;

/**
 * Converts annotations to method metadata
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class AnnotationConverter
{
    public function convertMethodAnnotations(\ReflectionMethod $method, array $annotations)
    {
        $parameters = array();
        foreach ($method->getParameters() as $index => $parameter) {
            $parameters[$parameter->getName()] = $index;
        }

        $methodMetadata = new MethodMetadata($method);
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Secure) {
                $methodMetadata->setRoles($annotation->getRoles());
            } else if ($annotation instanceof SecureParam) {
                if (!isset($parameters[$annotation->getName()])) {
                    throw new \InvalidArgumentException(sprintf('The parameter "%s" does not exist for method "%s".', $annotation->getName(), $method->getName()));
                }

                $methodMetadata->addParamPermissions($parameters[$annotation->getName()], $annotation->getPermissions());
            } else if ($annotation instanceof SecureReturn) {
                $methodMetadata->addReturnPermissions($annotation->getPermissions());
            } else if ($annotation instanceof SatisfiesParentSecurityPolicy) {
                $methodMetadata->setSatisfiesParentSecurityPolicy();
            } else if ($annotation instanceof RunAs) {
                $methodMetadata->setRunAsRoles($annotation->getRoles());
            }
        }

        return $methodMetadata;
    }
}