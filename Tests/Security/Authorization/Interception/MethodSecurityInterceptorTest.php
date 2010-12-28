<?php

namespace Bundle\JMS\SecurityExtraBundle\Tests\Security\Authorization\Interception;

use Bundle\JMS\SecurityExtraBundle\Security\Authentication\Token\RunAsUserToken;

use Symfony\Component\Security\Exception\AuthenticationException;

use Bundle\JMS\SecurityExtraBundle\Security\Authorization\Interception\MethodSecurityInterceptor;

use Bundle\JMS\SecurityExtraBundle\Security\Authorization\Interception\SecureMethodInvocation;

class MethodSecurityInterceptorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Symfony\Component\Security\Exception\AuthenticationCredentialsNotFoundException
     */
    public function testInvokeThrowsExceptionWhenSecurityContextHasNoToken()
    {
        list($interceptor, $securityContext,,,,) = $this->getInterceptor();

        $securityContext
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue(null))
        ;

        $interceptor->invoke($this->getInvocation(), $this->getMetadata());
    }

    /**
     * @expectedException Symfony\Component\Security\Exception\AuthenticationException
     */
    public function testInvokeAuthenticatesTokenIfItIsNotYetAuthenticated()
    {
        list($interceptor, $securityContext, $authManager,,,) = $this->getInterceptor();

        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\TokenInterface');
        $token
            ->expects($this->once())
            ->method('isAuthenticated')
            ->will($this->returnValue(false))
        ;

        $securityContext
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token))
        ;

        $authManager
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->throwException(new AuthenticationException('Could not authenticate.')))
        ;

        $interceptor->invoke($this->getInvocation(), $this->getMetadata());
    }

    /**
     * @expectedException Symfony\Component\Security\Exception\AuthenticationException
     */
    public function testInvokeAuthenticatesTokenIfAlwaysAuthenticateIsTrue()
    {
        list($interceptor, $securityContext, $authManager,,,) = $this->getInterceptor();

        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\TokenInterface');
        $token
            ->expects($this->once())
            ->method('isAuthenticated')
            ->will($this->returnValue(true))
        ;

        $securityContext
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token))
        ;

        $authManager
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->throwException(new AuthenticationException('Could not authenticate.')))
        ;

        $interceptor->setAlwaysAuthenticate(true);
        $interceptor->invoke($this->getInvocation(), $this->getMetadata());
    }

    /**
     * @expectedException Symfony\Component\Security\Exception\AccessDeniedException
     */
    public function testInvokeCallsADMForRolesAndThrowsExceptionWhenInsufficientPriviledges()
    {
        list($interceptor, $context, $authManager, $adm,,) = $this->getInterceptor();

        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\TokenInterface');
        $token
            ->expects($this->once())
            ->method('isAuthenticated')
            ->will($this->returnValue(false))
        ;

        $context
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token))
        ;
        $context
            ->expects($this->once())
            ->method('setToken')
            ->with($this->equalTo($token))
        ;

        $authManager
            ->expects($this->once())
            ->method('authenticate')
            ->will($this->returnValue($token))
        ;

        $method = $this->getInvocation();
        $adm
            ->expects($this->once())
            ->method('decide')
            ->with($this->equalTo($token), $this->equalTo(array('ROLE_FOO')), $this->equalTo($method))
            ->will($this->returnValue(false))
        ;

        $interceptor->invoke($method, $this->getMetadata(array('ROLE_FOO')));
    }

    /**
     * @expectedException Symfony\Component\Security\Exception\AccessDeniedException
     */
    public function testInvokeCallsADMForEachParamPermissionsAndThrowsExceptionOnInsufficientPermissions()
    {
        list($interceptor, $context,, $adm,,) = $this->getInterceptor();

        $context
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token = $this->getToken()))
        ;

        $metadata = $this->getMetadata();
        $metadata['param_permissions'] = array(
            $p0 = array('ROLE_FOO', 'ROLE_ASDF'),
            $p1 = array('ROLE_MOO'),
        );
        $method = $this->getInvocation();
        $adm
            ->expects($this->at(0))
            ->method('decide')
            ->with($this->equalTo($token), $this->equalTo($p0), $this->equalTo(new \stdClass()))
            ->will($this->returnValue(true))
        ;
        $adm
            ->expects($this->at(1))
            ->method('decide')
            ->with($this->equalTo($token), $this->equalTo($p1), $this->equalTo(new \stdClass()))
            ->will($this->returnValue(false))
        ;

        $interceptor->invoke($method, $metadata);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testInvokehandlesExceptionsFromWithintheInvokedMethodGracefully()
    {
        $method = $this->getInvocation('throwException');
        list($interceptor, $context,,,, $runAsManager) = $this->getInterceptor();

        $token = $this->getToken();
        $context
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token))
        ;

        $metadata = $this->getMetadata();
        $metadata['run_as_roles'] = array('ROLE_FOO');
        $runAsToken = new RunAsUserToken('asdf', 'user', 'foo', array('ROLE_FOO'), $token);
        $runAsManager
            ->expects($this->once())
            ->method('buildRunAs')
            ->will($this->returnValue($runAsToken))
        ;

        $context
            ->expects($this->exactly(2))
            ->method('setToken')
        ;

        $interceptor->invoke($method, $metadata);
    }

    protected function getToken($isAuthenticated = true)
    {
        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\TokenInterface');
        $token
            ->expects($this->once())
            ->method('isAuthenticated')
            ->will($this->returnValue($isAuthenticated))
        ;

        return $token;
    }

    protected function getMetadata(array $roles = array(), array $runAsRoles = array(),
        array $paramPermissions = array(), array $returnPermissions = array())
    {
        return array(
            'roles' => $roles,
            'run_as_roles' => $runAsRoles,
            'param_permissions' => $paramPermissions,
            'return_permissions' => $returnPermissions,
        );
    }

    protected function getInterceptor()
    {
        $securityContext = $this->getMockBuilder('Symfony\Component\Security\SecurityContext')
                            ->disableOriginalConstructor()
                            ->getMock();

        $authenticationManager = $this->getMock('Symfony\Component\Security\Authentication\AuthenticationManagerInterface');
        $accessDecisionManager = $this->getMock('Symfony\Component\Security\Authorization\AccessDecisionManagerInterface');
        $afterInvocationManager = $this->getMock('Bundle\JMS\SecurityExtraBundle\Security\Authorization\AfterInvocation\AfterInvocationManagerInterface');
        $runAsManager = $this->getMock('Bundle\JMS\SecurityExtraBundle\Security\Authorization\RunAsManagerInterface');

        return array(
            new MethodSecurityInterceptor($securityContext, $authenticationManager, $accessDecisionManager, $afterInvocationManager, $runAsManager),
            $securityContext,
            $authenticationManager,
            $accessDecisionManager,
            $afterInvocationManager,
            $runAsManager,
        );
    }

    protected function getInvocation($method = 'foo', $arguments = array())
    {
        if ('foo' === $method && 0 === count($arguments)) {
            $arguments = array(new \stdClass(), new \stdClass());
        }

        return new SecureMethodInvocation(new SecurityProxy(), $method, $arguments);
    }
}

class SecureService
{
    public function foo($param, $other)
    {
        return $param;
    }

    public function throwException()
    {
        throw new \RuntimeException;
    }
}

class SecurityProxy extends SecureService
{
    public function foo($p1, $p2)
    {
        return parent::foo($p1, $p2);
    }

    public function throwException()
    {
        parent::throwException();
    }
}