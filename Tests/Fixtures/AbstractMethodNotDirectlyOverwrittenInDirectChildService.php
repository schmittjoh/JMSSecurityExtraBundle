<?php

namespace JMS\SecurityExtraBundle\Tests\Fixtures;

use JMS\SecurityExtraBundle\Annotation\SecureReturn;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use JMS\SecurityExtraBundle\Annotation\Secure;

interface AMNDOIDCS_Interface
{
    /**
     * @SecureReturn(permissions="VIEW")
     */
    function abstractMethod();
}

abstract class AMNDOIDCS_DirectChild implements AMNDOIDCS_Interface
{
}

class AbstractMethodNotDirectlyOverwrittenInDirectChildService extends AMNDOIDCS_DirectChild
{
    /**
     * Some comment
     */
    public function abstractMethod()
    {
    }
}