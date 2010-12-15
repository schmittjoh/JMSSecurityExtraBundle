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