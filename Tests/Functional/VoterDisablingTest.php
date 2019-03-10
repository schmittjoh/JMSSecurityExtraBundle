<?php

namespace JMS\SecurityExtraBundle\Tests\Functional;

use Symfony\Component\HttpKernel\Kernel;

class VoterDisablingTest extends BaseTestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testDisableAllVoters()
    {
        if(Kernel::MAJOR_VERSION >= 3) {
            return $this->markTestSkipped('Voter tests do not work on Symfony 3 and higher');
        }
        $client = $this->createClient(array('config' => 'all_voters_disabled.yml'));
        $client->insulate();

        $adm = self::$kernel->getContainer()->get('security.access.decision_manager');

        $this->assertEquals(1, count($voters = $this->getVoters($adm)));
        $this->assertInstanceOf('JMS\SecurityExtraBundle\Security\Authorization\Expression\LazyLoadingExpressionVoter', $voters[0]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testDefault()
    {
        if(Kernel::MAJOR_VERSION >= 3) {
            return $this->markTestSkipped('Voter tests do not work on Symfony 3 and higher');
        }
        $client = $this->createClient(array('config' => 'default.yml'));
        $client->insulate();

        $adm = self::$kernel->getContainer()->get('security.access.decision_manager');

        $this->assertEquals(2, count($voters = $this->getVoters($adm)));
        $this->assertInstanceOf('Symfony\Component\Security\Core\Authorization\Voter\RoleVoter', $voters[1]);
        $this->assertInstanceOf('Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter', $voters[0]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSomeVotersDisabled()
    {
        if(Kernel::MAJOR_VERSION >= 3) {
            return $this->markTestSkipped('Voter tests do not work on Symfony 3 and higher');
        }
        $client = $this->createClient(array('config' => 'some_voters_disabled.yml'));
        $client->insulate();

        $adm = self::$kernel->getContainer()->get('security.access.decision_manager');

        $this->assertEquals(1, count($voters = $this->getVoters($adm)));
        $this->assertInstanceOf('Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter', $voters[0]);
    }

    private function getVoters($manager)
    {
        if (method_exists($manager, 'getVoters')) {
            return $manager->getVoters();
        }

        $ref = new \ReflectionProperty($manager, 'delegate');
        $ref->setAccessible(true);
        $delegate = $ref->getValue($manager);

        $ref = new \ReflectionProperty($delegate, 'voters');
        $ref->setAccessible(true);

        return $ref->getValue($delegate);
    }
}
