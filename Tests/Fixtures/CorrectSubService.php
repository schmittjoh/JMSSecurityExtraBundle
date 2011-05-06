<?php

namespace JMS\SecurityExtraBundle\Tests\Fixtures;

use JMS\SecurityExtraBundle\Annotation\SatisfiesParentSecurityPolicy;
use JMS\SecurityExtraBundle\Annotation\SecureReturn;

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