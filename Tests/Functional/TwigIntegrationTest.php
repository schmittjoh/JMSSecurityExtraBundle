<?php

namespace JMS\SecurityExtraBundle\Tests\Functional;

use JMS\SecurityExtraBundle\Security\Authorization\Expression\Expression;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class TwigIntegrationTest extends BaseTestCase
{
    public function testIsExprGrantedWithSufficientPermissions()
    {
        $this->createClient(array('config' => 'all_voters_disabled.yml'));

        $context = self::$kernel->getContainer()->get('security.context');
        $context->setToken(new UsernamePasswordToken('foo', 'bar', 'baz', array('FOO')));

        $twig = self::$kernel->getContainer()->get('twig');
        $this->assertEquals('granted', $twig->render('TestBundle::is_expr_granted.html.twig'));
    }

    public function testIsExprGranted()
    {
        $this->createClient(array('config' => 'all_voters_disabled.yml'));

        $context = self::$kernel->getContainer()->get('security.context');
        $context->setToken(new AnonymousToken('foo', 'bar'));

        $twig = self::$kernel->getContainer()->get('twig');
        $this->assertEquals('denied', $twig->render('TestBundle::is_expr_granted.html.twig'));
    }
}