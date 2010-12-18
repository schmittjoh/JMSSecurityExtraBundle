========
Overview
========

This bundle allows you to secure method invocations on your service layer with
annotations (additional drivers like xml, yaml, php, etc. might be added in the
future).

Generally, you can secure all public, or protected methods which are non-static,
and non-final. Private methods cannot be secured this way.

Annotations can also be declared on parent classes, or interfaces. There is a 
restriction however:

If you override a method which has security metadata, right now you always need
to copy over the annotations to your overridden method. In addition, you also
need to add @SatisfiesParentSecurityPolicy, otherwise there will be an exception
when the container is built.

Installation
------------
This bundle requires the pdepend library which is used for some code analysis.

You can simply add it as a submodule::

    git submodule add https://github.com/manuelpichler/pdepend.git src/vendor/pdepend
    
You also need to add the following to your autoload.php::

    require_once __DIR__.'/vendor/pdepend/PHP/Depend/Autoload.php';
    $loader = new PHP_Depend_Autoload();
    $loader->register();
    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.__DIR__.'/vendor/pdepend');

Annotations
-----------

@Secure
~~~~~~~
This annotation lets you define who is allowed to invoke a method::

    <?php
    
    class MyService
    {
        /**
         * @Secure(roles="ROLE_USER, ROLE_FOO, ROLE_ADMIN")
         */
        public function secureMethod() 
        {
            // ...
        }
    }

@SecureParam
~~~~~~~~~~~~
This annotation lets you define restrictions for parameters which are passed to
the method. This is only useful if the parameters are domain objects::

    <?php
    
    class MyService
    {
        /**
         * @SecureParam(name="comment", permissions="EDIT, DELETE")
         * @SecureParam(name="post", permissions="OWNER")
         */
        public function secureMethod($comment, $post)
        {
            // ...
        }
    }

@SecureReturn
~~~~~~~~~~~~~
This annotation lets you define restrictions for the value which is returned by
the method. This is also only useful if the returned value is a domain object::

    <?php
    
    class MyService
    {
        /**
         * @SecureReturn(permissions="VIEW")
         */
        public function secureMethod()
        {
            // ...
            
            return $domainObject;
        }
    }
    

@SatisfiesParentSecurityPolicy
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
This must be defined on a method that overrides a method which has security metadata.
It is there to ensure that you are aware the security of the overridden method cannot
be enforced anymore, and that you must copy over all annotations if you want to keep
them.
