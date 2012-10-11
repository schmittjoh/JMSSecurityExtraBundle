Creating Your Own Expression Function
=====================================

Requires
    * JMSSecurityExtraBundle 1.3.*
    * JMSDiExtraBundle 1.2.*

.. versionadded :: 1.3
    @DI\SecurityFunction was added.

Even though the built-in expressions allow you to do a lot already, there are
cases where you want to add a custom expression function.

Let's assume that you want to check that a user has a certain IP, you could do
that using this expression ``container.get("request").getIp() == "127.0.0.1"``.
However, if you duplicate this on several actions, it becomes a lot to write. Also,
what happens if you want to add another IP? You would have to edit all the places
where this expression is used. So instead of the above expression, let's add
another function ``isLocalUser()`` which you can use in your expressions.

You will want to place this file in a folder called Security (as seen in the namespace
in the example below) in your bundle.

You will also need to add your bundle to the `JMSDiExtraBundle configuration <http://jmsyst.com/bundles/JMSDiExtraBundle/master/configuration#configuration-locations>`_ so the
annotations will know where else to look for processing.

.. code-block :: php

    <?php

    namespace Acme\DemoBundle\Security;

    use Symfony\Component\DependencyInjection\ContainerInterface;
    use JMS\DiExtraBundle\Annotation as DI;

    /** @DI\Service */
    class RequestAccessEvaluator
    {
        private $container;

        /**
         * @DI\InjectParams({
         *     "container" = @DI\Inject("service_container"),
         * })
         */
        public function __construct(ContainerInterface $container)
        {
            $this->container = $container;
        }

        /** @DI\SecurityFunction("isLocalUser") */
        public function isLocalUser()
        {
            return $this->container->get('request')->getIp() === '127.0.0.1';
        }
    }
