<?php
/**
 * Tests for the {@link Plugins} class
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );

require_once( EVODIR.'/blogs/evocore/_plugins.class.php' );


/**
 * @package tests
 */
class PluginsTestCase extends DbUnitTestCase
{
	function PluginsTestCase()
	{
		$this->DbUnitTestCase( 'Plugins class test' );
	}


	function setUp()
	{
		parent::setUp();

		ob_start();
		$this->create_current_tables( true );
		install_basic_plugins();
		ob_end_clean();

		$this->Plugins = new Plugins();
	}


	function tearDown()
	{
		parent::tearDown();
	}


	function testUninstall()
	{
		global $DB;
		$this->assertTrue( $this->Plugins->uninstall( 1 ) );

		$this->assertFalse( $this->Plugins->get_by_ID( 1 ) );
	}

}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new PluginsTestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}

?>
