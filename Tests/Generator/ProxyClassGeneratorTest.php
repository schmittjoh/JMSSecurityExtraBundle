<?php

namespace Bundle\JMS\SecurityExtraBundle\Tests\Generator;

use Bundle\JMS\SecurityExtraBundle\Mapping\ServiceMetadata;
use Bundle\JMS\SecurityExtraBundle\Mapping\MethodMetadata;
use Bundle\JMS\SecurityExtraBundle\Generator\ProxyClassGenerator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Security\Exception\AccessDeniedException;

class ProxyClassGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerate()
    {
        $generator = new ProxyClassGenerator();
        $metadata = new ServiceMetadata();
        $reflection = new \ReflectionClass('Bundle\JMS\SecurityExtraBundle\Tests\Generator\FooService');

        $methodMetadata = new MethodMetadata($reflection->getMethod('foo'));
        $methodMetadata->setRoles(array('ROLE_FOO'));
        $methodMetadata->addParamPermissions('fooParam', array('PERMISSION_FOO'));
        $methodMetadata->setReturnPermissions(array('PERMISSION_RETURN'));
        $metadata->addMethod('foo', $methodMetadata);

        $definition = new Definition();
        $definition->setClass($reflection->getName());

        list($className, $proxy) = $generator->generate($definition, $metadata);
        $tmpFile = tempnam(sys_get_temp_dir(), 'proxy');
        file_put_contents($tmpFile, $proxy);
        require_once $tmpFile;
        unlink($tmpFile);

        $className = 'SecurityProxies\\'.$className;
        $proxyClass = new $className;

        $mock = $this->getMockBuilder('Bundle\JMS\SecurityExtraBundle\Security\Authorization\Interception\MethodSecurityInterceptor')
                   ->disableOriginalConstructor()
                   ->getMock();
        $mock
            ->expects($this->once())
            ->method('invoke')
            ->will($this->returnValue($return = new \stdClass()))
        ;
        $proxyClass->jmsSecurityExtraBundle__setMethodSecurityInterceptor($mock);

        $this->assertSame($return, $proxyClass->foo('foo'));
    }
}

class FooService
{
    public function foo($fooParam)
    {
        return 'foo';
    }
}