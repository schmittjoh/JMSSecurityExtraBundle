<?php

namespace Bundle\JMS\SecurityExtraBundle\Tests\Fixtures;

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