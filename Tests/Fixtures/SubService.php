<?php

namespace JMS\SecurityExtraBundle\Tests\Fixtures;

class SubService extends MainService
{
    public function differentMethodSignature($commentId)
    {
        $comment = 'asdgasdf'.$commentId;
        
        return MainService::differentMethodSignature($comment);
    }
}