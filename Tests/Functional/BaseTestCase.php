<?php

namespace JMS\SecurityExtraBundle\Tests\Functional;

use Symfony\Component\HttpKernel\Util\Filesystem;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BaseTestCase extends WebTestCase
{
    static protected function createKernel(array $options = array())
    {
        return new AppKernel(
            isset($options['config']) ? $options['config'] : 'default.yml'
        );
    }

    protected function setUp()
    {
        parent::setUp();

        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/JMSSecurityExtraBundle');
    }

    protected function tearDown()
    {
        parent::tearDown();

        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/JMSSecurityExtraBundle');
    }
}