<?php

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