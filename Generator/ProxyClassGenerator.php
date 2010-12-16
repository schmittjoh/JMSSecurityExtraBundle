<?php

namespace Bundle\JMS\SecurityExtraBundle\Generator;

use Bundle\JMS\SecurityExtraBundle\Mapping\ClassMetadata;
use Symfony\Component\DependencyInjection\Definition;
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
 * Generates the proxy class which has security checks built-in according to
 * the given metadata information.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 */
class ProxyClassGenerator
{
    protected $classCount;

    /**
     * Generates the proxy class
     *
     * @param Definition $definition
     * @param ClassMetadata $metadata
     * @return array<string, string>
     */
    public function generate(Definition $definition, ClassMetadata $metadata)
    {
        list($className, $proxy) = $this->getClassDefinition($definition);
        foreach ($metadata->getMethods() as $method) {
            $proxy .= $this->getMethodDefinition($method->getReflection());

            if (count($roles = $method->getRoles()) > 0) {
                    $proxy .= '    if (!$this->jmsSecurityExtraBundle__securityContext'
                             .'->vote('.var_export($roles, true).')) {
            throw new \Symfony\Component\Security\Exception\AccessDeniedException();
        }

    ';
            }

            foreach ($method->getParamPermissions() as $name => $permissions) {
                $proxy .= '    if (!$this->jmsSecurityExtraBundle__securityContext->vote('
                          .var_export($permissions, true).', $'.$name.')) {
            throw new \Symfony\Component\Security\Exception\AccessDeniedException();
        }

    ';
            }

            $proxy .= '    $result = '.$this->getMethodCall($method->getReflection()).';

    ';

            if (count($permissions = $method->getReturnPermissions()) > 0) {
                $proxy .= '    if (!$this->jmsSecurityExtraBundle__securityContext->vote('
                         .var_export($permissions, true).', $result)) {
            throw new \Symfony\Component\Security\Exception\AccessDeniedException();
        }

    ';
            }

            $proxy .= '    return $result;
    }

    ';
        }

        return array($className, substr($proxy, 0, -5).'}');
    }

    protected function getClassDefinition(Definition $definition)
    {
        $baseClass = $definition->getClass();
        if (false !== $pos = strrpos($baseClass, '\\')) {
            $className = substr($baseClass, $pos + 1);
        } else {
            $className = $baseClass;
        }

        if (isset($this->classCount[$className])) {
            $className .= '_'.(++$this->classCount[$className]);
        } else {
            $this->classCount[$className] = 1;
        }

        return array($className, sprintf('<?php

namespace Bundle\JMS\SecurityExtraBundle\Proxy;

/**
 * This class has been auto-generated. Manual changes will be lost.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class %s extends \%s
{
    protected $jmsSecurityExtraBundle__securityContext;

    public function jmsSecurityExtraBundle__setSecurityContext(\Symfony\Component\Security\SecurityContext $context)
    {
        $this->jmsSecurityExtraBundle__securityContext = $context;
    }

    ', $className, $baseClass));
    }

    protected function getMethodCall(ReflectionMethod $method)
    {
        $def = '';

        if ($method->returnsReference()) {
            $def .= '&';
        }

        $def .= 'parent::'.$method->getName().'(';
        foreach ($method->getParameters() as $param) {
            $def .= '$'.$param->getName().', ';
        }
        
        return substr($def, 0, -2). ')';
    }

    protected function getMethodDefinition(ReflectionMethod $method)
    {
        $def = '';
        if ($method->isProtected()) {
            $def .= 'protected ';
        } else {
            $def .= 'public ';
        }

        if ($method->isStatic()) {
            $def .= 'static ';
        }

        if ($method->returnsReference()) {
            $def .= '&';
        }

        $def .= 'function '.$method->getName().'(';
        foreach ($method->getParameters() as $param) {
            if (null !== $class = $param->getClass()) {
                $def .= '\\'.$class->getName().' ';
            } else if ($param->isArray()) {
                $def .= 'array ';
            }

            if ($param->isPassedByReference()) {
                $def .= '&';
            }

            $def .= '$'.$param->getName();

            if ($param->isOptional()) {
                $def .= ' = '.var_export($param->getDefaultValue(), true);
            }

            $def .= ', ';
        }
        $def = substr($def, 0, -2).')
    {
    ';

        return $def;
    }
}