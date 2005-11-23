<?php
/**
 * Tests for upgrading from older b2evo versions to the current version (this one).
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../config.simpletest.php' );


require_once( EVODIR.'blogs/install/_functions_install.php' );
require_once( EVODIR.'blogs/install/_functions_create.php' );


/**
 *
 */
class UpgradeTo1_6TestCase extends InstallUnitTestCase
{
	function UpgradeTo1_6TestCase()
	{
		$this->InstallUnitTestCase( 'Upgrade to version 1.6 tests' );
	}


	function setUp()
	{
		$this->dropTestDbTables();
	}


	function tearDown()
	{
		global $new_db_version;

		$this->assertEqual( $new_db_version, $this->DB->get_var('SELECT set_value FROM T_settings WHERE set_name = "db_version"') );
		$this->dropTestDbTables();
	}


	/**
	 * Test upgrade from 0.9.0.11
	 */
	function testUpgradeFrom0_9_0_11()
	{
		$this->createTablesFor0_9_0_11();

		require_once( EVODIR.'blogs/install/_functions_evoupgrade.php' );

		$GLOBALS['DB'] = $this->DB;
		$this->assertTrue( upgrade_b2evo_tables(), 'Upgrade from 0.9.0.11 successful!' );
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new UpgradeTo1_6TestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
