<?php

namespace JMS\SecurityExtraBundle\Tests\Controller\Fixtures;

class SecuredController
{
    /**
     * @extra:Secure(roles="ROLE_FOO")
     */
    public function action(\stdClass $a, array $b, $c, $foo = 'foo')
    {
    }
}