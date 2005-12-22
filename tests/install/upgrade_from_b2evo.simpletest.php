<?php
/**
 * Tests for upgrading from older b2evo versions to the current version (this one).
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../config.simpletest.php' );


require_once( EVODIR.'blogs/install/_functions_evoupgrade.php' );


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
		parent::setUp();

		$this->old_sql_mode = $this->DB->get_var( 'SELECT @@sql_mode' );
		$this->DB->query( 'SET sql_mode = ""' );
	}


	function tearDown()
	{
		global $new_db_version;

		$this->assertEqual( $new_db_version, $this->DB->get_var('SELECT set_value FROM T_settings WHERE set_name = "db_version"') );
		$this->assertEqual( $this->DB->get_var( 'SELECT COUNT(*) FROM T_plugins' ), $this->nr_of_basic_plugins );

		$this->DB->query( 'SET sql_mode = "'.$this->old_sql_mode.'"' );

		parent::tearDown();
	}


	/**
	 * Test upgrade from 0.9.0.11
	 */
	function testUpgradeFrom0_9_0_11()
	{
		$this->createTablesFor0_9_0_11();
		$this->assertTrue( upgrade_b2evo_tables(), 'Upgrade from 0.9.0.11 successful!' );
	}


	/**
	 * Test upgrade from 1.6 (Phoenix Alpha)
	 */
	function testUpgradeFrom1_6()
	{
		$this->createTablesFor1_6();
		$this->assertTrue( upgrade_b2evo_tables(), 'Upgrade from 1.6 successful!' );
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new UpgradeTo1_6TestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
