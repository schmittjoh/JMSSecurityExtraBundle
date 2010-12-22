<?php

namespace Bundle\JMS\SecurityExtraBundle\Tests\Security\Authentication\Provider;

use Bundle\JMS\SecurityExtraBundle\Security\Authentication\Token\RunAsUserToken;

use Bundle\JMS\SecurityExtraBundle\Security\Authentication\Provider\RunAsAuthenticationProvider;

class RunAsAuthenticationProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testAuthenticateReturnsNullIfTokenISUnsupported()
    {
        $provider = new RunAsAuthenticationProvider('foo');
        $token = $this->GetMock('Symfony\Component\Security\Authentication\Token\TokenInterface');

        $this->assertNull($provider->authenticate($token));
    }

    /**
     * @expectedException Symfony\Component\Security\Exception\BadCredentialsException
     */
    public function testAuthenticateThrowsExceptionWhenKeysDontMatch()
    {
        $provider = new RunAsAuthenticationProvider('foo');
        $token = $this->getSupportedToken();
        $token
            ->expects($this->once())
            ->method('getKey')
            ->will($this->returnValue('moo'))
        ;

        $provider->authenticate($token);
    }

    public function testAuthenticate()
    {
        $provider = new RunAsAuthenticationProvider('foo');
        $token = $this->getSupportedToken();
        $token
            ->expects($this->once())
            ->method('getKey')
            ->will($this->returnValue('foo'))
        ;

        $this->assertSame($token, $provider->authenticate($token));
    }

    public function testSupportsDoesNotAcceptInvalidToken()
    {
        $provider = new RunAsAuthenticationProvider('foo');
        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\TokenInterface');

        $this->assertFalse($provider->supports($token));
    }

    public function testSupports()
    {
        $provider = new RunAsAuthenticationProvider('foo');

        $token = $this->getSupportedToken();
        $this->assertTrue($provider->supports($token));
    }

    protected function getSupportedToken()
    {
        return $this->getMockBuilder('Bundle\JMS\SecurityExtraBundle\Security\Authentication\Token\RunAsUserToken')
                ->disableOriginalConstructor()
                ->getMock();
    }
}