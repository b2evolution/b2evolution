<?php
/**
 * The unit testcase class for install tests.
 */

/**
 * The DB class for the internal DB object.
 */
require_once( dirname(__FILE__).'/DbUnitTestCase.class.php' );


global $install_subdir, $basepath;

require_once $basepath.$install_subdir.'_functions_install.php';
require_once $basepath.$install_subdir.'_functions_evoupgrade.php';
load_funcs('_core/model/db/_upgrade.funcs.php');


/**
 * The base class for all /install tests.
 */
class InstallUnitTestCase extends DbUnitTestCase
{
	/**
	 * Number of basic plugins to test for being installed.
	 *
	 * @see install_basic_plugins()
	 */
	var $nr_of_basic_plugins = 10;


	/**
	 * Setup globals according to /install/index.php
	 */
	function setUp()
	{
		global $timestamp, $test_DB;

		$timestamp = time() - 120; // We start dates 2 minutes ago because their dates increase 1 second at a time and we want everything to be visible when the user watches the blogs right after install :P

		$GLOBALS['script_start_time'] = time(); // used by installer

		parent::setUp();
	}

	/**
	 * General after-install checks.
	 */
	function tearDown()
	{
		// Test if item types (which get installed for Phoenix-Alpha) are present:
		// fp> What is this test good for?
		$this->assertEqual(
			$this->test_DB->get_var( 'SELECT ptyp_name
																	FROM T_items__type
																 ORDER BY ptyp_ID' ), 'Post' );

		parent::tearDown();
	}
}

?>
