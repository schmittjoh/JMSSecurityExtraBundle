<?php

namespace Bundle\JMS\SecurityExtraBundle\Generator;

use Bundle\JMS\SecurityExtraBundle\Mapping\ServiceMetadata;
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
     * @param ServiceMetadata $metadata
     * @return array<string, string>
     */
    public function generate(Definition $definition, ServiceMetadata $metadata)
    {
        list($className, $proxy) = $this->getClassDefinition($definition);
        foreach ($metadata->getMethods() as $name => $method) {
            $reflection = $method->getReflection();
            $proxy .= $this->getMethodDefinition($reflection);

            $proxy .= '    $methodInvocation = new \Bundle\JMS\SecurityExtraBundle\Security\Authorization\SecureMethodInvocation($this, '.var_export($name, true).', array(';
            for ($i=1,$c=$reflection->getNumberOfParameters(); $i<=$c; $i++) {
                $proxy .= '$param_'.$i.', ';
            }
            if ($c > 0) {
                $proxy = substr($proxy, 0, -2);
            }
            $proxy .= '));
    ';

            $proxy .= '    $runAsToken = $this->jmsSecurityExtraBundle__methodSecurityInterceptor->beforeInvocation($methodInvocation, ';

            $proxy .= var_export($method->getRoles(), true).', ';
            $proxy .= var_export($method->getParamPermissions(), true).', ';
            $proxy .= var_export($runAsRoles = $method->getRunAsRoles(), true).');

    ';

            if (count($runAsRoles) === 0 && count($returnPermissions = $method->getReturnPermissions()) === 0 && false === $method->returnsReference()) {
                $proxy .= '    return '.$this->getMethodCall($reflection).';
    }

    ';
            } else {
                $proxy .= '    $returnValue = '.$this->getMethodCall($reflection).';

    ';

                if (count($runAsRoles) === 0 && count($returnPermissions) === 0) {
                    $proxy .= '    return $returnValue;
    ';
                } else {
                    $proxy .= '    return $this->jmsSecurityExtraBundle__methodSecurityInterceptor->afterInvocation($methodInvocation, $returnValue, $runAsToken);
    ';
                }

                $proxy .= '}

    ';
            }
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

namespace SecurityProxies;

/**
 * This class has been auto-generated. Manual changes will be lost.
 * Last updated at '.date('r').'
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class %s extends \%s
{
    private $jmsSecurityExtraBundle__methodSecurityInterceptor;

    public function jmsSecurityExtraBundle__setMethodSecurityInterceptor(\Bundle\JMS\SecurityExtraBundle\Security\Authorization\MethodSecurityInterceptor $interceptor)
    {
        $this->jmsSecurityExtraBundle__methodSecurityInterceptor = $interceptor;
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
        foreach ($method->getParameters() as $index => $param) {
            $def .= '$param_'.($index+1).', ';
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
        foreach ($method->getParameters() as $index => $param) {
            if (null !== $class = $param->getClass()) {
                $def .= '\\'.$class->getName().' ';
            } else if ($param->isArray()) {
                $def .= 'array ';
            }

            if ($param->isPassedByReference()) {
                $def .= '&';
            }

            $def .= '$param_'.($index+1);

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