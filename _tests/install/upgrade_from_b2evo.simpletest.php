<?php
/**
 * Tests for upgrading from older b2evo versions to the current version (this one).
 * @package tests
 * @todo Rename to upgrade.simpletest.php (SVN keeps history.. *hint*)
 */

/**
 * SimpleTest config
 */
require_once dirname(__FILE__).'/../config.simpletest.php';

global $conf_path;

// from /conf/_upgrade.php:
global $oldtableposts, $oldtableusers, $oldtablesettings, $oldtablecategories, $oldtablecomments;
global $fileupload_allowedtypes, $fileupload_maxk, $stats_autoprune;
// for /conf/_upgrade.php:
global $basepath, $baseurl, $current_charset;

require_once $conf_path.'_upgrade.php';

// Force UTF-8 charset for install/upgrade procedures
$current_charset = 'utf-8';

/**
 * Test upgrading to current scheme
 * @package tests
 */
class UpgradeToCurrentTestCase extends EvoInstallUnitTestCase
{
	function __construct()
	{
		parent::__construct( 'Upgrade to current version' );
	}


	function setUp()
	{
		parent::setUp();

		// We test with lax sql mode
		$this->old_sql_mode = $this->test_DB->get_var( 'SELECT @@sql_mode' );
		$this->test_DB->query( 'SET sql_mode = ""' );
	}


	function tearDown()
	{
		global $new_db_version;

		$this->assertEqual( $new_db_version, $this->test_DB->get_var('SELECT set_value FROM T_settings WHERE set_name = "db_version"') );

		// Test that columns are not present anymore:
		$this->assertEqual(
			array(),
			array_intersect( array('blog_pingb2evonet', 'blog_pingtechnorati', 'blog_pingweblogs', 'blog_pingblodotgs'),
				$this->test_DB->get_col( 'SHOW COLUMNS FROM T_blogs' ) ) );

		$this->test_DB->query( 'SET sql_mode = "'.$this->old_sql_mode.'"' );

		parent::tearDown();
	}


	/**
	 * Test upgrade from 0.9.0.11
	 */
	function testUpgradeFrom0_9_0_11()
	{
		echo '<h2>Upgrading from v0.9.0.11</h2>';
		$this->createTablesFor0_9_0_11();
		$this->assertTrue( upgrade_b2evo_tables(), 'Upgrade from 0.9.0.11 successful!' );
	}


	/**
	 * Test upgrade from 1.6 (Phoenix Alpha)
	 */
	function testUpgradeFrom1_6()
	{
		echo '<hr /><h2>Upgrading from v1.6</h2>';
		$this->createTablesFor1_6();
		$this->assertTrue( upgrade_b2evo_tables(), 'Upgrade from 1.6 successful!' );
	}


	/**
	 * Test upgrade from 1.6 (Phoenix Alpha) ("strict" MySQL mode)
	 */
	function testUpgradeFrom1_6_strict()
	{
		echo '<hr /><h2>Upgrading from v1.6 (strict)</h2>';
		$this->createTablesFor1_6();
		$this->test_DB->query( 'SET sql_mode = "TRADITIONAL"' );
		$this->assertTrue( upgrade_b2evo_tables(), 'Upgrade from 1.6 in strict mode successful!' );
	}


	/**
	 * Test upgrade from 1.8 (Summer Beta) ("strict" MySQL mode)
	 */
	function testUpgradeFrom1_8_strict()
	{
		echo '<hr /><h2>Upgrading from v1.8</h2>';
		$this->createTablesFor1_8();
		$this->test_DB->query( 'SET sql_mode = "TRADITIONAL"' );
		$this->assertTrue( upgrade_b2evo_tables(), 'Upgrade from 1.8 in strict mode successful!' );
	}


	/**
	 * Test upgrade from 2.4 ("strict" MySQL mode)
	 */
	function testUpgradeFrom2_4_strict()
	{
		echo '<hr /><h2>Upgrading from v2.4</h2>';
		$this->executeQueriesFromFile( TESTSDIR.'install/sql/b2evolution_v-2-4.welcome-slugs.sql' );
		$this->test_DB->query( 'SET sql_mode = "TRADITIONAL"' );
		$this->assertTrue( upgrade_b2evo_tables(), 'Upgrade from 2.4 in strict mode successful!' );
	}


	/**
	 * Test upgrade from 3.3 ("strict" MySQL mode)
	 */
	function testUpgradeFrom3_3_strict()
	{
		echo '<h2>Upgrading from v3.3</h2>';
		$this->executeQueriesFromFile( TESTSDIR.'install/sql/b2evolution_v3.3.sql' );
		$this->test_DB->query( 'SET sql_mode = "TRADITIONAL"' );
		$this->assertTrue( upgrade_b2evo_tables(), 'Upgrade from 3.3 in strict mode successful!' );
	}


	/**
	 * Test upgrade from 4.0 ("strict" MySQL mode)
	 */
	function testUpgradeFrom4_0_strict()
	{
		echo '<h2>Upgrading from v4.0</h2>';
		$this->executeQueriesFromFile( TESTSDIR.'install/sql/b2evolution_v4.0.sql' );
		$this->test_DB->query( 'SET sql_mode = "TRADITIONAL"' );
		$this->assertTrue( upgrade_b2evo_tables(), 'Upgrade from 4.0 in strict mode successful!' );
	}


	/**
	 * Test upgrade from 4.1 ("strict" MySQL mode)
	 */
	function testUpgradeFrom4_1_strict()
	{
		echo '<h2>Upgrading from v4.1</h2>';
		$this->executeQueriesFromFile( TESTSDIR.'install/sql/b2evolution_v4.1.sql' );
		$this->test_DB->query( 'SET sql_mode = "TRADITIONAL"' );
		$this->assertTrue( upgrade_b2evo_tables(), 'Upgrade from 4.1 in strict mode successful!' );
	}

}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new UpgradeToCurrentTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>