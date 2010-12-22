<?php

namespace Bundle\JMS\SecurityExtraBundle\Tests\Security\Authentication\Token;

use Symfony\Component\Security\Role\Role;
use Bundle\JMS\SecurityExtraBundle\Security\Authentication\Token\RunAsUserToken;

class RunAsUserTokenTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $user = $this->getMock('Symfony\Component\Security\User\AccountInterface');
        $token = $this->getMock('Symfony\Component\Security\Authentication\Token\TokenInterface');

        $runAsToken = new RunAsUserToken('foo', $user, 'secret', array('ROLE_FOO'), $token);
        $this->assertSame($user, $runAsToken->getUser());
        $this->assertSame('secret', $runAsToken->getCredentials());
        $this->assertSame($token, $runAsToken->getOriginalToken());
        $this->assertEquals(array(new Role('ROLE_FOO')), $runAsToken->getRoles());
        $this->assertSame('foo', $runAsToken->getKey());
    }
}