<?php

namespace JMS\SecurityExtraBundle\Twig;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class SecurityExtension
 * @package JMS\SecurityExtraBundle\Twig
 */
class SecurityExtension extends \Twig_Extension
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /**
     * SecurityExtension constructor.
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            'is_expr_granted' => new \Twig_Function_Method($this, 'isExprGranted'),
        );
    }

    /**
     * @param $expr
     * @param null $object
     * @return bool
     */
    public function isExprGranted($expr, $object = null)
    {
        return $this->authorizationChecker->isGranted(array(new Expression($expr)), $object);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'jms_security_extra';
    }
}
