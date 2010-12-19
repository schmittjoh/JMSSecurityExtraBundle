<?php

namespace Bundle\JMS\SecurityExtraBundle\Security\Authorization;

use Symfony\Component\Security\Authentication\Token\TokenInterface;

interface RunAsManagerInterface
{
    function buildRunAs(TokenInterface $token, $secureObject, array $attributes);
    function supportsAttribute($attribute);
    function supportsObject($object);
}