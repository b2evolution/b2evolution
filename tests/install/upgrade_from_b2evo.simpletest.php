<?php
/**
 * Tests for upgrading from older b2evo versions to the current version (this one).
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once dirname(__FILE__).'/../config.simpletest.php';


/**
 * Test upgrading to current scheme
 * @package tests
 */
class UpgradeToCurrentTestCase extends InstallUnitTestCase
{
	function UpgradeToCurrentTestCase()
	{
		$this->InstallUnitTestCase( 'Upgrade to current version' );
	}


	function setUp()
	{
		parent::setUp();

		$this->dropTestDbTables();

		// We test with lax sql mode
		$this->old_sql_mode = $this->test_DB->get_var( 'SELECT @@sql_mode' );
		$this->test_DB->query( 'SET sql_mode = ""' );
	}


	function tearDown()
	{
		global $new_db_version;

		$this->assertEqual( $new_db_version, $this->test_DB->get_var('SELECT set_value FROM T_settings WHERE set_name = "db_version"') );
		$this->assertEqual( $this->test_DB->get_var( 'SELECT COUNT(*) FROM T_plugins' ), $this->nr_of_basic_plugins );

		$this->test_DB->query( 'SET sql_mode = "'.$this->old_sql_mode.'"' );

		parent::tearDown();
	}


	/**
	 * Test upgrade from 0.8.2
	 */
	function testUpgradeFrom0_8_2()
	{
		$this->executeQueriesFromFile( TESTSDIR.'install/sql/b2evolution_0_8_2.default.sql' );
		$this->assertTrue( upgrade_b2evo_tables(), 'Upgrade from 0.8.2 successful!' );
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


	/**
	 * Test upgrade from 1.6 (Phoenix Alpha) ("strict" MySQL mode)
	 */
	function testUpgradeFrom1_6_strict()
	{
		$this->createTablesFor1_6();
		$this->test_DB->query( 'SET sql_mode = "TRADITIONAL"' );
		$this->assertTrue( upgrade_b2evo_tables(), 'Upgrade from 1.6 in strict mode successful!' );
	}


	/**
	 * Test upgrade from 1.8 (Summer Beta) ("strict" MySQL mode)
	 */
	function testUpgradeFrom1_8_strict()
	{
		$this->createTablesFor1_8();
		$this->test_DB->query( 'SET sql_mode = "TRADITIONAL"' );
		$this->assertTrue( upgrade_b2evo_tables(), 'Upgrade from 1.8 in strict mode successful!' );
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new UpgradeToCurrentTestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
