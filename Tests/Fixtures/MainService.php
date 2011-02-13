<?php

namespace JMS\SecurityExtraBundle\Tests\Fixtures;

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