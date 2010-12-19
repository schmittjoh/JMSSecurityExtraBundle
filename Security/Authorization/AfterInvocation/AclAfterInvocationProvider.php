<?php

namespace Bundle\JMS\SecurityExtraBundle\Security\Authorization\AfterInvocation;

use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Exception\AccessDeniedException;

class AclAfterInvocationProvider implements AfterInvocationProviderInterface
{
    protected $aclProvider;
    protected $oidRetrievalStrategy;
    protected $sidRetrievalStrategy;
    protected $permissionMap;
    protected $logger;

    public function __construct(AclProviderInterface $aclProvider, ObjectIdentityRetrievalStrategyInterface $oidRetrievalStrategy, SecurityIdentityRetrievalStrategyInterface $sidRetrievalStrategy, PermissionMapInterface $permissionMap, LoggerInterface $logger = null)
    {
        $this->aclProvider = $aclProvider;
        $this->oidRetrievalStrategy = $oidRetrievalStrategy;
        $this->sidRetrievalStrategy = $sidRetrievalStrategy;
        $this->permissionMap = $permissionMap;
        $this->logger = $logger;
    }

    public function decide(TokenInterface $token, $secureObject, array $attributes, $returnedObject)
    {
        if (null === $returnedObject) {
            if (null !== $this->logger) {
                $this->logger->debug('Returned object was null, skipping security check.');
            }
        }

        if (null === $oid = $this->oidRetrievalStrategy->getObjectIdentity($returnedObject)) {
            if (null !== $this->logger) {
                $this->logger->debug('Returned object was no domain object, skipping security check.');
            }

            return $returnObject;
        }

        $sids = $this->sidRetrievalStrategy->getSecurityIdentities($token);

        try {
            $acl = $this->aclProvider->findAcl($oid, $sids);
        } catch (AclNotFoundException $noAcl) {
            throw new AccessDeniedException('No applicable ACL found for domain object.');
        }

        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            try {
                if ($acl->isGranted($this->permissionMap->getMasks($attribute), $sids, false)) {

                    return $returnedObject;
                } else {
                    if (null !== $this->logger) {
                        $this->logger->debug('Token has been denied access for returned object.');
                    }
                }
            } catch (NoAceFoundException $noAce) {
                if (null !== $this->logger) {
                    $this->logger->debug('No applicable ACE found for the given Token, denying access.');
                }
            }

            throw new AccessDeniedException('ACL has denied access for attribute: '.$attribute);
        }

        // no attribute was supported
        return $returnedObject;
    }

    public function supportsAttribute($attribute)
    {
        return $this->permissionMap->contains($attribute);
    }

    public function supportsClass($className)
    {
        return true;
    }
}