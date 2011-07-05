<?php

namespace JMS\SecurityExtraBundle\Tests\Fixtures;

use JMS\SecurityExtraBundle\Annotation\Secure;
use JMS\SecurityExtraBundle\Tests\Fixtures\Annotation\NonSecurityAnnotation;
use JMS\SecurityExtraBundle\Annotation\SecureParam;

class MainService
{
    /**
     * This Method has no relevant security annotations
     * @NonSecurityAnnotation
     */
    public function foo()
    {
    }

    /**
     * @SecureParam(name="comment", permissions="EDIT")
     */
    public function differentMethodSignature($comment)
    {
        // some secure action
    }
}