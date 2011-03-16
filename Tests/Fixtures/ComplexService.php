<?php

namespace JMS\SecurityExtraBundle\Tests\Fixtures;

interface E {
    /**
     * @extra:SecureReturn(permissions="VIEW,UNDELETE")
     */
    function retrieve();
}
interface F {
    /**
     * @extra:SecureParam(name="secure", permissions="OWNER")
     * @extra:SecureParam(name="foo", permissions="MASTER, EDIT")
     */
    function delete($foo, $asdf, $secure);
}
interface C { }
interface D extends F {}
interface B extends C, E { }
abstract class G implements F, E {
    /**
     * @extra:Secure(roles="ROLE_FOO, IS_AUTHENTICATED_FULLY")
     * @extra:SecureParam(name="secure", permissions="FOO")
     * @extra:SecureReturn(permissions="WOW")
     */
    abstract function abstractMethod($foo, $secure);
}
class A extends G implements C, B, D {
    public function retrieve() { }
    public function delete($one, $two, $three) { }
    public function abstractMethod($asdf, $wohoo) { }
}
class ComplexService extends A implements C { }
