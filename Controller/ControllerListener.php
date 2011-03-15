<?php

namespace JMS\SecurityExtraBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use JMS\SecurityExtraBundle\Mapping\Driver\AnnotationConverter;
use JMS\SecurityExtraBundle\Mapping\MethodMetadata;
use JMS\SecurityExtraBundle\Security\Authorization\Interception\MethodInvocation;
use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Mapping\Driver\AnnotationReader;
use Symfony\Component\EventDispatcher\EventInterface;

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

    public function filter(EventInterface $event, $controller)
    {
        if (!is_array($controller)) {
            return $controller;
        }

        $method = new MethodInvocation($controller[0], $controller[1], $controller[0]);
        if (!$annotations = $this->reader->getMethodAnnotations($method)) {
            return $controller;
        }

        $time = microtime(true);
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

        return eval($closureCode);
    }
}