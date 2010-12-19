<?php

namespace Bundle\JMS\SecurityExtraBundle\Security\Authentication\Token;

use Symfony\Component\Security\Authentication\Token\Token;

class RunAsUserToken extends Token
{
    protected $originalToken;
    protected $user;
    protected $key;

    public function __construct($key, $user, $credentials, array $roles, $originalToken)
    {
        parent::__construct($roles);

        $this->originalToken = $originalToken;
        $this->credentials = $credentials;
        $this->setUser($user);
        $this->key = $key;
        $this->setAuthenticated(true);
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getOriginalToken()
    {
        return $this->originalToken;
    }
}