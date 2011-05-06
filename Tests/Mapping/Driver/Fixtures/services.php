<?php

namespace JMS\SecurityExtraBundle\Tests\Mapping\Driver;

use JMS\SecurityExtraBundle\Annotation\SecureReturn;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use JMS\SecurityExtraBundle\Annotation\Secure;

class FooService implements FooInterface
{
    /**
     * @Secure(roles="ROLE_USER, ROLE_ADMIN, ROLE_SUPERADMIN")
     * @SecureParam(name="param", permissions="VIEW")
     */
    public function foo($param, $anotherParam) { }

    /**
     * @Secure("ROLE_FOO, ROLE_BAR")
     */
    public function shortNotation() { }
}
interface FooInterface
{
    /**
     * @SecureParam(name="param", permissions="OWNER")
     * @SecureParam(name="anotherParam", permissions="EDIT")
     * @SecureReturn(permissions="MASTER")
     */
    function foo($param, $anotherParam);
}