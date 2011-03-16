<?php

namespace JMS\SecurityExtraBundle\Tests\Controller;

use JMS\SecurityExtraBundle\Tests\Controller\Fixtures\SecuredController;
use JMS\SecurityExtraBundle\Tests\Controller\Fixtures\UnsecuredController;
use JMS\SecurityExtraBundle\Controller\ControllerListener;

class ControllerListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getIgnoredControllers
     */
    public function testFilterIgnoresNonArrayControllers($controller)
    {
        list($listener,) = $this->getListener();

        $this->assertSame($controller, $listener->filter($this->getEvent(), $controller));
    }

    public function getIgnoredControllers()
    {
        return array(
            array('foo'),
            array(function() { }),
        );
    }

    public function testFilterIgnoredUnsecuredController()
    {
        list($listener,) = $this->getListener();

        $controller = array(new UnsecuredController(), 'action');
        $this->assertSame($controller, $listener->filter($this->getEvent(), $controller));
    }

    public function testFilter()
    {
        list($listener, $container) = $this->getListener();

        $interceptor = $this->getMockBuilder('JMS\SecurityExtraBundle\Security\Authorization\Interception\MethodSecurityInterceptor')
                            ->disableOriginalConstructor()
                            ->getMock();

        $interceptor
            ->expects($this->once())
            ->method('invoke')
            ->will($this->returnCallback(function() { return func_get_args(); }))
        ;

        $container
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('security.access.method_interceptor'))
            ->will($this->returnValue($interceptor))
        ;

        $controller = array($realController = new SecuredController(), 'action');
        $newController = $listener->filter($this->getEvent(), $controller);

        $this->assertInstanceOf('\Closure', $newController);
        $ref = new \ReflectionFunction($newController);
        $params = $ref->getParameters();
        $this->assertSame('a', $params[0]->getName());
        $this->assertSame('stdClass', $params[0]->getClass()->getName());
        $this->assertSame('b', $params[1]->getName());
        $this->assertTrue($params[1]->isArray());
        $this->assertSame('c', $params[2]->getName());
        $this->assertSame('foo', $params[3]->getName());
        $this->assertSame('foo', $params[3]->getDefaultValue());

        $passedArgs = array(new \stdClass(), array('foo'), 'foo');
        list($methodInvocation, $metadata) = call_user_func_array($newController, $passedArgs);

        $passedArgs[] = 'foo';
        $this->assertSame($passedArgs, $methodInvocation->getArguments());
        $this->assertSame($realController, $methodInvocation->getThis());
        $this->assertSame('action', $methodInvocation->getName());

        $expected = array(
            'roles' => array('ROLE_FOO'),
            'run_as_roles' => array(),
            'param_permissions' => array(),
            'return_permissions' => array(),
        );
        $this->assertSame($expected, $metadata);
    }

    protected function getEvent()
    {
        return $this->getMock('Symfony\Component\EventDispatcher\EventInterface');
    }

    protected function getListener()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $listener = new ControllerListener($container);

        return array($listener, $container);
    }
}