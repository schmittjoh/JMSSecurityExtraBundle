<?php

namespace Bundle\JMS\SecurityExtraBundle\Security\Authorization\AfterInvocation;

use Symfony\Component\Security\Authentication\Token\TokenInterface;

interface AfterInvocationProviderInterface
{
    function decide(TokenInterface $token, $secureObject, array $attributes, $returnedObject);
    function supportsAttribute($attribute);
    function supportsClass($className);
}