<?php
/**
 * Tests for installing the current version itself.
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
class Install_self extends InstallUnitTestCase
{
	function Install_self()
	{
		$this->InstallUnitTestCase( 'Installing myself' );
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
	 * Test installing
	 */
	function testInstall()
	{
		require_once( EVODIR.'blogs/install/_functions_create.php' );

		$GLOBALS['DB'] = $this->DB;

		create_b2evo_tables();
		populate_main_tables();
		install_basic_plugins();

		$this->assertEqual( $this->DB->get_var( 'SELECT COUNT(*) FROM T_plugins' ), $this->nr_of_basic_plugins );
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new UpgradeTo1_6TestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
