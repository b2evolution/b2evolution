<?php
/**
* Tests for upgrading to b2 0.9.2.
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
class UpgradeTo092TestCase extends InstallUnitTestCase
{
	function UpgradeTo092TestCase()
	{
		$this->InstallUnitTestCase( 'Upgrade to 0.9.2 tests' );
	}


	function setUp()
	{
		$this->dropTestDbTables();
	}


	function tearDown()
	{
		// $this->assertEqual( '8070', $this->DB->get_var('SELECT set_value FROM T_settings WHERE set_name = "db_version"') );
	}


	/**
	 * Test upgrade from 0.9.0.11
	 */
	function testUpgradeFrom0_9_0_11()
	{
		$this->createTablesFor0_9_0_11();

		require_once( EVODIR.'blogs/install/_functions_evoupgrade.php' );

		$this->assertTrue( upgrade_b2evo_tables(), 'Upgrade from 0.9.0.11 to 0.9.2 successful!' );
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new UpgradeTo092TestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
