<?php

namespace JMS\SecurityExtraBundle\Tests\Fixtures;

interface AMNDOIDCS_Interface
{
    /**
     * @extra:SecureReturn(permissions="VIEW")
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