<?php

namespace JMS\SecurityExtraBundle\Tests\Analysis;

use JMS\SecurityExtraBundle\Analysis\ServiceAnalyzer;

class ServiceAnalyzerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedMessage You have overridden a secured method "differentMethodSignature" in "SubService". Please copy over the applicable security metadata, and also add @SatisfiesParentSecurityPolicy.
     */
    public function testAnalyzeThrowsExceptionWhenSecureMethodIsOverridden()
    {
        $service = new ServiceAnalyzer('JMS\SecurityExtraBundle\Tests\Fixtures\SubService');
        $service->analyze();
    }

    public function testAnalyzeThrowsNoExceptionWhenAbstractMethodIsNotOverridenInDirectChildClass()
    {
        $service = new ServiceAnalyzer('JMS\SecurityExtraBundle\Tests\Fixtures\AbstractMethodNotDirectlyOverwrittenInDirectChildService');
        $service->analyze();

        $methods = $service->getMetadata()->methodMetadata;
        $this->assertTrue(isset($methods['abstractMethod']));

        $metadata = $methods['abstractMethod'];
        $this->assertEquals(array('VIEW'), $metadata->returnPermissions);
    }

    public function testAnalyzeThrowsNoExceptionWhenSatisfiesParentSecurityPolicyIsDefined()
    {
        $service = new ServiceAnalyzer('JMS\SecurityExtraBundle\Tests\Fixtures\CorrectSubService');
        $service->analyze();

        $methods = $service->getMetadata()->methodMetadata;
        $this->assertTrue(isset($methods['differentMethodSignature']));

        $metadata = $methods['differentMethodSignature'];
        $this->assertEquals(array(), $metadata->roles);
        $this->assertEquals(array(), $metadata->paramPermissions);
        $this->assertEquals(array('VIEW'), $metadata->returnPermissions);
    }

    public function testAnalyzeWithComplexHierarchy()
    {
        $service = new ServiceAnalyzer('JMS\SecurityExtraBundle\Tests\Fixtures\ComplexService');
        $service->analyze();

        $methods = $service->getMetadata()->methodMetadata;
        $this->assertTrue(isset($methods['delete'], $methods['retrieve'], $methods['abstractMethod']));

        $metadata = $methods['delete'];
        $this->assertEquals(array(0 => array('MASTER', 'EDIT'), 2 => array('OWNER')), $metadata->paramPermissions);
        $this->assertEquals(array(), $metadata->returnPermissions);
        $this->assertEquals(array(), $metadata->roles);

        $metadata = $methods['retrieve'];
        $this->assertEquals(array('VIEW', 'UNDELETE'), $metadata->returnPermissions);
        $this->assertEquals(array(), $metadata->paramPermissions);
        $this->assertEquals(array(), $metadata->roles);

        $metadata = $methods['abstractMethod'];
        $this->assertEquals(array('ROLE_FOO', 'IS_AUTHENTICATED_FULLY'), $metadata->roles);
        $this->assertEquals(array(1 => array('FOO')), $metadata->paramPermissions);
        $this->assertEquals(array('WOW'), $metadata->returnPermissions);
    }

    public function testAnalyze()
    {
        $service = new ServiceAnalyzer('JMS\SecurityExtraBundle\Tests\Fixtures\MainService');
        $service->analyze();

        $methods = $service->getMetadata()->methodMetadata;
        $this->assertTrue(isset($methods['differentMethodSignature']));

        $metadata = $methods['differentMethodSignature'];
        $this->assertEquals(array(array('EDIT')), $metadata->paramPermissions);
        $this->assertEquals(array(), $metadata->returnPermissions);
        $this->assertEquals(array(), $metadata->roles);
        $this->assertFalse($metadata->isDeclaredOnInterface());
    }
}