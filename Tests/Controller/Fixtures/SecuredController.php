<?php

namespace JMS\SecurityExtraBundle\Tests\Controller\Fixtures;

use JMS\SecurityExtraBundle\Annotation\Secure;

class SecuredController
{
    /**
     * @Secure(roles="ROLE_FOO")
     */
    public function action(\stdClass $a, array $b, $c, $foo = 'foo')
    {
    }
}