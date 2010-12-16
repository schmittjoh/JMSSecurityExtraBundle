<?php

namespace Bundle\JMS\SecurityExtraBundle\Tests\Mapping\Driver;

class FooService implements FooInterface
{
    /**
     * @SecureMethod(roles="ROLE_USER, ROLE_ADMIN, ROLE_SUPERADMIN")
     * @SecureParam(name="param", permissions="VIEW")
     */
    public function foo($param, $anotherParam) { }
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