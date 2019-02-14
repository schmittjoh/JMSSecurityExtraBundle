<?php
/**
 * This file allows the test suite to be run on PHPUnit version >=6 by aliasing the old PHPUnit classes to their
 * namespaced equivalents
 */
if (!class_exists('\PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');
}