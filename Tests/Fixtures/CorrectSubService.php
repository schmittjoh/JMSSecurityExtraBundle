<?php

namespace Bundle\JMS\SecurityExtraBundle\Tests\Fixtures;

class CorrectSubService extends MainService
{
    /**
     * @SatisfiesParentSecurityPolicy
     * @SecureReturn(permissions="VIEW")
     */
    public function differentMethodSignature($comment)
    {
        return parent::differentMethodSignature($comment);
    }
}