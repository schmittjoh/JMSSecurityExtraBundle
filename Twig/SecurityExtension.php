<?php

namespace JMS\SecurityExtraBundle\Twig;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class SecurityExtension extends \Twig_Extension
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * Constructor.
     *
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function getFunctions()
    {
        return array(
            'is_expr_granted' => new \Twig_Function_Method($this, 'isExprGranted'),
        );
    }

    public function isExprGranted($expr, $object = null)
    {
        return $this->authorizationChecker->isGranted(array(new Expression($expr)), $object);
    }

    public function getName()
    {
        return 'jms_security_extra';
    }
}
