<?php

namespace JMS\SecurityExtraBundle\Tests\Mapping\Driver;

class FooService implements FooInterface
{
    /**
     * @extra:Secure(roles="ROLE_USER, ROLE_ADMIN, ROLE_SUPERADMIN")
     * @extra:SecureParam(name="param", permissions="VIEW")
     */
    public function foo($param, $anotherParam) { }

    /**
     * @extra:Secure("ROLE_FOO, ROLE_BAR")
     */
    public function shortNotation() { }
}
interface FooInterface
{
    /**
     * @extra:SecureParam(name="param", permissions="OWNER")
     * @extra:SecureParam(name="anotherParam", permissions="EDIT")
     * @extra:SecureReturn(permissions="MASTER")
     */
    function foo($param, $anotherParam);
}