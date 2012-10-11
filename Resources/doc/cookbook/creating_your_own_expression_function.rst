Creating Your Own Expression Function
=====================================

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

.. code-block :: php

    <?php

    namespace Security;

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
