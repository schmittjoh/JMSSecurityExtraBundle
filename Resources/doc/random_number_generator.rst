Secure Random Number Generator
------------------------------
In almost all applications, you need to generate random numbers that cannot be
guessed by a possible attacker. Unfortunately, PHP does not provide capabilities
to do this consistently on all platforms. 

This bundle ships with several seed provider implementations, and will choose
the best provider possible depending on your PHP setup.

You can enable the "security.secure_random" service with the following config::

    jms_security_extra:
        util:
            secure_random: ~

Also make sure to run ``php app/console doctrine:schema:update``, or create an
equivalent migration to import the seed table.
