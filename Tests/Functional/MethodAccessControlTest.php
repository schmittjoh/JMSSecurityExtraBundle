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

    public function testAcl()
    {
        $client = $this->createClient(array('config' => 'acl_enabled.yml'));
        $client->insulate();

        $this->importDatabaseSchema();
        $this->login($client);

        var_dump('ADDING POST');
        $client->request('POST', '/post/add', array('title' => 'Foo'));
        var_dump('POST ADDED');

        $response = $client->getResponse();
        $this->assertEquals('/post/edit/1', $response->headers->get('Location'),
            substr($response, 0, 2000));

        var_dump('EDITING POST');
        $client->request('GET', '/post/edit/1');
        var_dump('POST EDITED');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), substr($response, 0, 2000));
        $this->assertEquals('Foo', $response->getContent());
    }

    public function testRoleHierarchyIsRespected()
    {
    	$client = $this->createClient(array('config' => 'all_voters_disabled.yml'));
        $client->insulate();

        $this->login($client);

        $client->request('GET', '/post/list');

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), substr($response, 0, 2000));
        $this->assertEquals('list', $response->getContent(), substr($response, 0, 2000));
    }
}