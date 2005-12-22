<?php
/**
 * The unit testcase class for install tests.
 */

/**
 * The DB class for the internal DB object.
 */
require_once( dirname(__FILE__).'/DbUnitTestCase.class.php' );

/**
 * The base class for all /install tests.
 */
class InstallUnitTestCase extends DbUnitTestCase
{
	/**
	 * Number of basic plugins to test for being installed.
	 */
	var $nr_of_basic_plugins = 6;
}

?>
