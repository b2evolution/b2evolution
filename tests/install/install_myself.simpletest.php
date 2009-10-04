<?php
/**
 * Tests for installing the current version itself.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once dirname(__FILE__).'/../config.simpletest.php';


/**
 * Test if we can install ourself.
 *
 * @package tests
 */
class InstallSelfTestCase extends EvoInstallUnitTestCase
{
	function __construct()
	{
		parent::__construct( 'Installing myself' );
	}


	function tearDown()
	{
		global $new_db_version;

		$this->assertEqual( $new_db_version, $this->test_DB->get_var('SELECT set_value FROM T_settings WHERE set_name = "db_version"') );

		parent::tearDown();
	}


	/**
	 * Test installing
	 */
	function testInstall()
	{
		// NOTE: this is the same as with install action "newdb":
		install_newdb();
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new InstallSelfTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
