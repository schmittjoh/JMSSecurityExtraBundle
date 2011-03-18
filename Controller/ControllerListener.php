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

namespace JMS\SecurityExtraBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\SecurityExtraBundle\Mapping\Driver\AnnotationConverter;
use JMS\SecurityExtraBundle\Mapping\MethodMetadata;
use JMS\SecurityExtraBundle\Security\Authorization\Interception\MethodInvocation;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Mapping\Driver\AnnotationReader;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * This listener allows you to use all method annotations on non-service controller actions.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ControllerListener
{
    private $reader;
    private $converter;
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->reader = new AnnotationReader();
        $this->converter = new AnnotationConverter();
    }

    public function onCoreController(FilterControllerEvent $event)
    {
        if (!is_array($controller = $event->getController())) {
            return;
        }

        $method = new MethodInvocation($controller[0], $controller[1], $controller[0]);
        if (!$annotations = $this->reader->getMethodAnnotations($method)) {
            return;
        }

        $closureCode = 'return function(';
        $params = $paramNames = array();
        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            $paramNames[] = '$'.$name;

            $parameter = '';
            if (null !== $class = $param->getClass()) {
                $parameter .= '\\'.$class->getName().' ';
            } else if ($param->isArray()) {
                $parameter .= 'array ';
            }

            $parameter .= '$'.$name;
            if ($param->isDefaultValueAvailable()) {
                $parameter .= ' = '.var_export($param->getDefaultValue(), true);
            }

            $params[] = $parameter;
        }
        $params = implode(', ', $params);
        $closureCode .= $params.') ';

        $jmsSecurityExtra__metadata = $this->converter->convertMethodAnnotations($method, $annotations)->getAsArray();
        $jmsSecurityExtra__interceptor = $this->container->get('security.access.method_interceptor');
        $jmsSecurityExtra__method = $method;

        $closureCode .= 'use ($jmsSecurityExtra__metadata, $jmsSecurityExtra__interceptor, $jmsSecurityExtra__method) {';
        $closureCode .= '$jmsSecurityExtra__method->setArguments(array('.implode(', ', $paramNames).'));';
        $closureCode .= 'return $jmsSecurityExtra__interceptor->invoke($jmsSecurityExtra__method, $jmsSecurityExtra__metadata);';
        $closureCode .= '};';

        $event->setController(eval($closureCode));
    }
}
