<?php

namespace Bundle\JMS\SecurityExtraBundle\Tests\Generator;

use Bundle\JMS\SecurityExtraBundle\Mapping\MethodMetadata;
use Bundle\JMS\SecurityExtraBundle\Mapping\ClassMetadata;
use Bundle\JMS\SecurityExtraBundle\Generator\ProxyClassGenerator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Security\Exception\AccessDeniedException;

class ProxyClassGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerate()
    {
        $generator = new ProxyClassGenerator();
        $reflection = new \ReflectionClass('Bundle\JMS\SecurityExtraBundle\Tests\Generator\FooService');
        $metadata = new ClassMetadata($reflection);

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

        $className = 'Bundle\\JMS\\SecurityExtraBundle\\Proxy\\'.$className;
        $proxyClass = new $className;

        $mock = $this->getMock('Symfony\Component\Security\SecurityContext', array('vote'), array(), '', false);
        $mock
            ->expects($this->at(0))
            ->method('vote')
            ->with($this->equalTo(array('ROLE_FOO')))
            ->will($this->returnValue(false))
        ;
        $proxyClass->jmsSecurityExtraBundle__setSecurityContext($mock);

        try {
            $proxyClass->foo('asdf');
            $this->fail('Proxy-Class was not secured.');
        } catch (AccessDeniedException $denied) { }

        $mock = $this->getMock('Symfony\Component\Security\SecurityContext', array('vote'), array(), '', false);
        $mock
            ->expects($this->at(0))
            ->method('vote')
            ->will($this->returnValue(true))
        ;
        $mock
            ->expects($this->at(1))
            ->method('vote')
            ->with($this->equalTo(array('PERMISSION_FOO')), $this->equalTo('fooParam'))
            ->will($this->returnValue(false))
        ;
        $proxyClass->jmsSecurityExtraBundle__setSecurityContext($mock);

        try {
            $proxyClass->foo('fooParam');
            $this->fail('Proxy-Class was not secured.');
        } catch (AccessDeniedException $denied) { }

        $mock = $this->getMock('Symfony\Component\Security\SecurityContext', array('vote'), array(), '', false);
        $mock
            ->expects($this->at(0))
            ->method('vote')
            ->will($this->returnValue(true))
        ;
        $mock
            ->expects($this->at(1))
            ->method('vote')
            ->will($this->returnValue(true))
        ;
        $mock
            ->expects($this->at(2))
            ->method('vote')
            ->with($this->equalTo(array('PERMISSION_RETURN')))
            ->will($this->returnValue(false))
        ;
        $proxyClass->jmsSecurityExtraBundle__setSecurityContext($mock);

        try {
            $proxyClass->foo('foo');
            $this->fail('ProxyClass was not properly secured.');
        } catch (AccessDeniedException $denied) {}

        $mock = $this->getMock('Symfony\Component\Security\SecurityContext', array('vote'), array(), '', false);
        $mock
            ->expects($this->at(0))
            ->method('vote')
            ->will($this->returnValue(true))
        ;
        $mock
            ->expects($this->at(1))
            ->method('vote')
            ->with($this->equalTo(array('PERMISSION_FOO')), $this->equalTo('fooParam'))
            ->will($this->returnValue(false))
        ;
        $proxyClass->jmsSecurityExtraBundle__setSecurityContext($mock);

        try {
            $proxyClass->foo('fooParam');
            $this->fail('Proxy-Class was not secured.');
        } catch (AccessDeniedException $denied) { }

        $mock = $this->getMock('Symfony\Component\Security\SecurityContext', array('vote'), array(), '', false);
        $mock
            ->expects($this->any())
            ->method('vote')
            ->will($this->returnValue(true))
        ;
        $proxyClass->jmsSecurityExtraBundle__setSecurityContext($mock);
        $this->assertEquals('foo', $proxyClass->foo('fooParam'));
    }
}

class FooService
{
    public function foo($fooParam)
    {
        return 'foo';
    }
}