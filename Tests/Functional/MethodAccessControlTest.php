<?php

namespace JMS\SecurityExtraBundle\Tests\Functional;

class MethodAccessControlTest extends BaseTestCase
{
    public function testControllerAddActionIsSecure()
    {
        $client = $this->createClient(array('config' => 'method_access_control.yml'));

        $client->request('GET', '/add');
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('http://localhost/login', $response->headers->get('Location'));
    }

    public function testControllerEditActionIsNotSecure()
    {
        $client = $this->createClient(array('config' => 'method_access_control.yml'));

        $client->request('GET', '/edit');
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @expectedException Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException
     */
    public function testUserManagerDeleteIsSecure()
    {
        $this->createClient(array('config' => 'method_access_control.yml'));

        $manager = self::$kernel->getContainer()->get('user_manager');

        $this->assertNotEquals(
        	'JMS\SecurityExtraBundle\Tests\Functional\TestBundle\User\UserManager',
            get_class($manager)
        );
        $manager->delete();
    }
}