==========================
Securing Method Invocation
==========================

This bundle allows you to secure method invocation on your service layer with
annotations::

    <?php
    
    class MyService
    {
        /**
         * @Secure(roles="ROLE_USER, ROLE_FOO")
         * @SecureParam(name="comment", permissions="EDIT")
         */
        public function secureMethod(Comment $comment)
        {
            // ...
        }
    }

In the above example, the logged-in user must have "EDIT" permission for $comment, and also have either the role "ROLE_USER", or "ROLE_FOO". 

You can also accomplish this without annotations, but it requires a bit more code::

    <?php

    class MyService
    {
        protected $securityContext;

        public function __construct(SecurityContext $context)
        {
            $this->securityContext = $context;
        }
        
        public function secureMethod(Comment $comment)
        {
            if (false === $this->securityContext->vote(array('ROLE_USER', 'ROLE_FOO'))
                || false === $this->securityContext->vote(array('EDIT'), $comment)) {
                throw new AccessDeniedException();
            }
        }
    }