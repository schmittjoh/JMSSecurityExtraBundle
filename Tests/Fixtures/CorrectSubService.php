<?php

namespace JMS\SecurityExtraBundle\Tests\Fixtures;

class CorrectSubService extends MainService
{
    /**
     * @extra:SatisfiesParentSecurityPolicy
     * @extra:SecureReturn(permissions="VIEW")
     */
    public function differentMethodSignature($comment)
    {
        return parent::differentMethodSignature($comment);
    }
}