<?php

namespace Bundle\JMS\SecurityExtraBundle\Tests\Analysis;

use Bundle\JMS\SecurityExtraBundle\Analysis\ServiceAnalyzer;

class ServiceAnalyzerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedMessage You have overridden a secured method "differentMethodSignature" in "SubService". Please copy over the applicable security metadata, and also add @SatisfiesParentSecurityPolicy.
     */
    public function testAnalyzeThrowsExceptionWhenSecureMethodIsOverridden()
    {
        $service = new ServiceAnalyzer('Bundle\JMS\SecurityExtraBundle\Tests\Fixtures\SubService');
        $service->analyze();
    }

    public function testAnalyzeThrowsNoExceptionWhenAbstractMethodIsNotOverridenInDirectChildClass()
    {
        $service = new ServiceAnalyzer('Bundle\JMS\SecurityExtraBundle\Tests\Fixtures\AbstractMethodNotDirectlyOverwrittenInDirectChildService');
        $service->analyze();

        $methods = $service->getMetadata()->getMethods();
        $this->assertTrue(isset($methods['abstractMethod']));

        $metadata = $methods['abstractMethod'];
        $this->assertEquals(array('VIEW'), $metadata->getReturnPermissions());
    }

    public function testAnalyzeThrowsNoExceptionWhenSatisfiesParentSecurityPolicyIsDefined()
    {
        $service = new ServiceAnalyzer('Bundle\JMS\SecurityExtraBundle\Tests\Fixtures\CorrectSubService');
        $service->analyze();

        $methods = $service->getMetadata()->getMethods();
        $this->assertTrue(isset($methods['differentMethodSignature']));

        $metadata = $methods['differentMethodSignature'];
        $this->assertEquals(array(), $metadata->getRoles());
        $this->assertEquals(array(), $metadata->getParamPermissions());
        $this->assertEquals(array('VIEW'), $metadata->getReturnPermissions());
    }

    public function testAnalyzeWithComplexHierarchy()
    {
        $service = new ServiceAnalyzer('Bundle\JMS\SecurityExtraBundle\Tests\Fixtures\ComplexService');
        $service->analyze();

        $methods = $service->getMetadata()->getMethods();
        $this->assertTrue(isset($methods['delete'], $methods['retrieve'], $methods['abstractMethod']));

        $metadata = $methods['delete'];
        $this->assertEquals(array(0 => array('MASTER', 'EDIT'), 2 => array('OWNER')), $metadata->getParamPermissions());
        $this->assertEquals(array(), $metadata->getReturnPermissions());
        $this->assertEquals(array(), $metadata->getRoles());

        $metadata = $methods['retrieve'];
        $this->assertEquals(array('VIEW', 'UNDELETE'), $metadata->getReturnPermissions());
        $this->assertEquals(array(), $metadata->getParamPermissions());
        $this->assertEquals(array(), $metadata->getRoles());

        $metadata = $methods['abstractMethod'];
        $this->assertEquals(array('ROLE_FOO', 'IS_AUTHENTICATED_FULLY'), $metadata->getRoles());
        $this->assertEquals(array(1 => array('FOO')), $metadata->getParamPermissions());
        $this->assertEquals(array('WOW'), $metadata->getReturnPermissions());
    }

    public function testAnalyze()
    {
        $service = new ServiceAnalyzer('Bundle\JMS\SecurityExtraBundle\Tests\Fixtures\MainService');
        $service->analyze();

        $methods = $service->getMetadata()->getMethods();
        $this->assertTrue(isset($methods['differentMethodSignature']));

        $metadata = $methods['differentMethodSignature'];
        $this->assertEquals(array(array('EDIT')), $metadata->getParamPermissions());
        $this->assertEquals(array(), $metadata->getReturnPermissions());
        $this->assertEquals(array(), $metadata->getRoles());
        $this->assertFalse($metadata->isDeclaredOnInterface());
    }
}