This document details all changes from JMSSecurityExtraBundle 1.0.x to 1.1:

- The configuration option "secure_controllers" has been removed. This setting is
  now automatically enabled, but it requires the JMSDiExtraBundle.

- The dependencies of this bundle have changed:
  
    * The metadata library 1.1 version is now required instead of the 1.0 version
      (if you are using the Standard Edition, just change the "version=origin/1.0.x" 
      line from your deps file to "version=1.1.0").
    * The JMSAopBundle is now required. For installation instructions, please see
      https://github.com/schmittjoh/JMSAopBundle
    * The JMSDiExtraBundle is now required if you want to secure your non-service
      controllers (if you only have service controllers, you don't need it). For
      installation instructions, see https://github.com/schmittjoh/JMSDiExtraBundle

- The attribute "IS_IDDQD" has been renamed to "ROLE_IDDQD"

- A powerful expression-based authorization language has been added which works
  in combination with the existing voting system. Since it is much more powerful
  than the built-in voters, and also much faster, you are highly encouraged to
  migrate your existing authorization rules to expressions, and eventually disable 
  the built-in voters entirely.

- The ability to configure method access control (e.g. for controller actions)
  in the DI configuration has been added. Note that for non-service controllers
  the JMSDiExtraBundle is required.
  
