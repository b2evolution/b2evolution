<?php
/**
 * The unit testcase class for install tests.
 *
 * NOTE: empty due to refactoring.
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
	 * Constructor
	 */
	function InstallUnitTestCase()
	{
		parent::DbUnitTestCase();
	}
}

?>
