<?php

namespace JMS\SecurityExtraBundle\Tests\Functional;

use JMS\SecurityExtraBundle\Security\Authorization\RememberingAccessDecisionManager;
use Symfony\Bundle\AclBundle\AclBundle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\Authorization\TraceableAccessDecisionManager;

class VoterDisablingTest extends BaseTestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testDisableAllVoters()
    {
        $client = $this->createClient(array('config' => 'all_voters_disabled.yml'));
        $client->insulate();

        /**
         * @var \Symfony\Component\Security\Core\Authorization\AccessDecisionManager $adm
         */
        $adm = self::$kernel->getContainer()->get('security.access.decision_manager');

        $this->assertEquals(1, count($voters = $this->getVoters($adm)));
        $this->assertInstanceOf('JMS\SecurityExtraBundle\Security\Authorization\Expression\LazyLoadingExpressionVoter', $voters[0]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDefault()
    {
        $client = $this->createClient(array('config' => 'default.yml'));
        $client->insulate();

        $adm = self::$kernel->getContainer()->get('security.access.decision_manager');

        if (Kernel::VERSION_ID >= 30400) {
            $this->assertEquals(3, count($voters = $this->getVoters($adm)));

            $this->assertInstanceOf('JMS\SecurityExtraBundle\Security\Acl\Voter\AclVoter', $voters[0]); // @todo ?? REMOVE ??
            $this->assertInstanceOf('Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter', $voters[1]);
            $this->assertInstanceOf('Symfony\Component\Security\Core\Authorization\Voter\RoleVoter', $voters[2]);
        } else {
            $this->assertEquals(2, count($voters = $this->getVoters($adm)));
            $this->assertInstanceOf('Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter', $voters[0]);
            $this->assertInstanceOf('Symfony\Component\Security\Core\Authorization\Voter\RoleVoter', $voters[1]);
        }
        
        // @todo
        $this->markTestIncomplete('The ACLBundle is now always loaded, causing a AclVoter to be present as well... need fixing');
    }

    /**
     * @runInSeparateProcess
     */
    public function testSomeVotersDisabled()
    {
        if (class_exists(AclBundle::class)) {
            $client = $this->createClient(array('config' => 'some_voters_disabled.yml'));
        } else {
            $client = $this->createClient(array('config' => 'some_voters_disabled_below_symfony_4.yml'));
        }
        $client->insulate();

        $adm = self::$kernel->getContainer()->get('security.access.decision_manager');

        $this->assertEquals(1, count($voters = $this->getVoters($adm)));
        $this->assertInstanceOf('Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter', $voters[0]);
    }

    private function getVoters($manager)
    {
        //if (method_exists($manager, 'getVoters')) {
        //    return $manager->getVoters();
        //}

        try {
            $ref = new \ReflectionProperty($manager, 'manager');
            $ref->setAccessible(true);
            /**
             * @var RememberingAccessDecisionManager $remembering
             */
            $remembering = $ref->getValue($manager);

            $ref = new \ReflectionProperty($remembering, 'delegate');
            $ref->setAccessible(true);
            $delegate = $ref->getValue($remembering);
        } catch(\ReflectionException $exception) {
            $ref = new \ReflectionProperty($manager, 'delegate');
            $ref->setAccessible(true);
            $delegate = $ref->getValue($manager);
        }

        $ref = new \ReflectionProperty($delegate, 'voters');
        $ref->setAccessible(true);

        $voters = $ref->getValue($delegate);

        if ($voters instanceof \Traversable) {
            return iterator_to_array($voters);
        }

        return $voters;
    }
}
