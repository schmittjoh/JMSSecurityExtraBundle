<?php

namespace JMS\SecurityExtraBundle\Tests\Metadata\Driver;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
use JMS\SecurityExtraBundle\Metadata\MethodMetadata;
use JMS\SecurityExtraBundle\Metadata\Driver\ConfigDriver;

/**
 * @group driver
 */
class ConfigDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadMetadata()
    {
        $driver = new ConfigDriver(array(), array(
            'CrudController::.*Action$' => 'hasRole("FOO")',
        ));

        $metadata = $driver->loadMetadataForClass($this->getClass('Controller\\CrudController'));

        $this->assertEquals(5, count($metadata->methodMetadata));

        $metadata = $metadata->methodMetadata;
        foreach (array('addAction', 'deleteAction', 'editAction', 'showAction', 'newAction') as $action) {
            $this->assertArrayHasKey($action, $metadata);
            $this->assertEquals(array(new Expression('hasRole("FOO")')), $metadata[$action]->roles);
        }
    }

    public function testLoadMetadataControllerNotation()
    {
        $driver = new ConfigDriver(array(
            'AcmeFooBundle' => 'JMS\SecurityExtraBundle\Tests\Metadata\Driver\Fixtures\AcmeFooBundle',
        ), array(
            '^AcmeFooBundle:.*:delete.*$' => 'hasRole("ROLE_ADMIN")',
        ));

        $metadata = $driver->loadMetadataForClass($this->getClass('Controller\\CrudController'));

        $this->assertEquals(1, count($metadata->methodMetadata));
        $this->assertArrayHasKey('deleteAction', $metadata->methodMetadata);
        $this->assertEquals(array(new Expression('hasRole("ROLE_ADMIN")')), $metadata->methodMetadata['deleteAction']->roles);
    }

    public function testLoadMetadataWithoutConfig()
    {
        $driver = new ConfigDriver(array(), array());
        $this->assertNull($driver->loadMetadataForClass($this->getClass('Controller\\CrudController')));
    }

    /**
     * @dataProvider advancedConfigProvider
     */
    public function testLoadAdvancedMetadata($config, $securedClass, $securedMethods)
    {
        $driver = new ConfigDriver(array(), $config);

        $reflection = new \ReflectionClass(
            'JMS\SecurityExtraBundle\Tests\Mapping\Driver\\'.$securedClass
        );

        $metadata = $driver->loadMetadataForClass($reflection);

        $this->assertEquals(1, count($metadata->methodMetadata));

        foreach ($config as $configEntry) {
            foreach ($configEntry as $key => $content) {
                switch ($key) {
                    case 'pattern'      :
                        break 2;
                    case 'pre_authorize':
                        $assert = "assertPreAuthorize";
                        break;
                    case 'secure'       :
                        $assert = "assertSecure";
                        break;
                    case 'secure_param' :
                        $assert ="assertSecureParam";
                        break;
                    case 'secure_return':
                        $assert = "assertSecureReturn";
                        break;
                    case 'run_as'       :
                        $assert = "assertRunAs";
                        break;
                    case 'satisfies_parent_security_policy':
                        $assert = "assertSatisfiesParentSecurity";
                        break;
                    default             :
                        $this->fail("Unknown configuration key found: ". $key);
                        break;
                }
    
                foreach ($metadata->methodMetadata as $name => $metadata) {
                    $this->assertEquals($name, current($securedMethods));
                    $this->{$assert}($metadata, current($securedMethods), $content );
    
                    next($securedMethods);
                }
            }
        }
    }

    protected function assertPreAuthorize($loadedMethod, $config)
    {
        $expression = new Expression(current($config));

        $this->assertEquals(
            array($expression), $loadedMethod->roles,
            sprintf("Expected expression %s got %s", $expression, $loadedMethod->roles)
        );
    }

    protected function assertSecure($loadedMethod, $config)
    {
        $this->assertPreAuthorize($loadedMethod, $config);
    }

    protected function assertSecureParam($loadedMethod, $config)
    {
        $expectedPermission = $loadedMethod->paramPermissions[$config['name']];

        $this->assertEquals(
            $expectedPermissions, $config['permission'],
            sprintf("Expected parameter permission %s got %s", $expectedPermission, $config['permission'])
        );
    }

    protected function assertSecureReturn($loadedMethod, $config)
    {
        $this->assertEquals(
            $loadedMethod->returnPermissions, $config,
            sprintf("Expected return permission %s got %s". $loadedMethod->returnPermission, $config)
        );
    }

    public function advancedConfigProvider()
    {
        return array(
            array(
                'config' => array('FooService::foo'=> array(
                    'pattern'       => 'FooService::foo',
                    'secure'        => array(
                        'roles' => array(
                            'ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPERADMIN'
                        ),
                    ),
                    'secure_param'   => array('name' => 'param', 'permissions' => array('VIEW')),
                )),
                'securedClass' => 'FooService',
                'securedMethods' => array('foo'),
            ),
            array(
                'config' => array('FooService::shortNotation' => array(
                    'pattern'       => 'FooService::shortNotation',
                    'secure'        => array(
                        'roles' => array('ROLE_FOO', 'ROLE_BAR')
                    ),
                )),
                'securedClass' => 'FooService',
                'securedMethods' => array('shortNotation'),
            ),
            array(
                'config' => array('FooService::bar' => array(
                    'pattern'       => 'FooService::bar',
                    'secure'        => array('roles' => 'ROLE_FOO, ROLE_BAR'),
                    'secure_param'   => array(
                        'name' => 'param', 'permissions' => array('OWNER')
                    ),
                    'secure_return'  => array('permissions' => 'MASTER'),
                )),
                'securedClass' => 'FooService',
                'securedMethods' => array('bar'),
            ),
            array(
                'config' => array('FooSecureService::foo' => array(
                    'pattern'       => 'FooSecureService::foo',
                    'secure_param'   => array(
                        'name' => 'anotherParam', 'permissions' => array('EDIT')
                    ),
                )),
                'securedClass' => 'FooSecureService',
                'securedMethods' => array('foo'),
            ),
            array(
                'config' => array('FooMultipleSecureService::foo' => array(
                    'pattern'       => 'FooMultipleSecureService::foo',
                    'secure_param'   => array(
                        'name' => 'param', 'permissions' => array('VIEW')
                    ),
                    'secure_param'   => array(
                        'name' => 'anotherParam', 'permissions' => array('EDIT')
                    ),
                )),
                'securedClass' => 'FooMultipleSecureService',
                'securedMethods' => array('foo'),
            ),
        );
    }

    private function getClass($name)
    {
        return new \ReflectionClass('JMS\SecurityExtraBundle\Tests\Metadata\Driver\Fixtures\\'.$name);
    }
}
