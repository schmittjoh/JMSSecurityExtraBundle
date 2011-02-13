<?php

namespace JMS\SecurityExtraBundle\Tests\Generator;

use JMS\SecurityExtraBundle\Mapping\ServiceMetadata;
use JMS\SecurityExtraBundle\Mapping\MethodMetadata;
use JMS\SecurityExtraBundle\Generator\ProxyClassGenerator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ProxyClassGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTestData
     */
    public function testGenerate($class, $method, array $arguments)
    {
        $generator = new ProxyClassGenerator();
        $metadata = new ServiceMetadata();
        $reflection = new \ReflectionClass($class);

        $methodMetadata = new MethodMetadata($reflection->getMethod($method));
        $methodMetadata->setRoles(array('ROLE_FOO'));
        $methodMetadata->setReturnPermissions(array('PERMISSION_RETURN'));
        $metadata->addMethod($method, $methodMetadata);

        $definition = new Definition();
        $definition->setClass($reflection->getName());

        list($className, $proxy) = $generator->generate($definition, $metadata);
        $tmpFile = tempnam(sys_get_temp_dir(), 'proxy');
        file_put_contents($tmpFile, $proxy);
        require_once $tmpFile;
        unlink($tmpFile);

        $className = 'SecurityProxies\\'.$className;
        $proxyClass = new $className;

        $mock = $this->getMockBuilder('JMS\SecurityExtraBundle\Security\Authorization\Interception\MethodSecurityInterceptor')
                   ->disableOriginalConstructor()
                   ->getMock();
        $mock
            ->expects($this->once())
            ->method('invoke')
            ->will($this->returnValue($return = new \stdClass()))
        ;
        $proxyClass->jmsSecurityExtraBundle__setMethodSecurityInterceptor($mock);

        $this->assertSame($return, call_user_func_array(array($proxyClass, $method), $arguments));
    }

    public function getTestData()
    {
        return array(
            array('JMS\SecurityExtraBundle\Tests\Generator\FooService', 'foo', array('foo')),
            array('JMS\SecurityExtraBundle\Tests\Generator\AnotherService', 'foo', array()),
        );
    }
}

class AnotherService
{
    public function foo()
    {
    }
}

class FooService
{
    public function foo($fooParam)
    {
        return 'foo';
    }
}