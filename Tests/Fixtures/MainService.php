<?php

namespace JMS\SecurityExtraBundle\Tests\Fixtures;

use JMS\SecurityExtraBundle\Annotation\SecureParam;

class MainService
{
    /**
     * @SecureParam(name="comment", permissions="EDIT")
     */
    public function differentMethodSignature($comment)
    {
        // some secure action
    }
}