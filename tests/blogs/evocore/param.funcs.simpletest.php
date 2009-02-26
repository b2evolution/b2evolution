<?php
/**
 * Tests for param functions.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


global $inc_path;

load_funcs('_core/_param.funcs.php');


/**
 * @package tests
 */
class ParamFuncsTestCase extends EvoUnitTestCase
{
	function __construct()
	{
		parent::__construct( 'Param functions test' );
	}


	/**
	 * Remember current GLOBAL scope.
	 */
	function setUp()
	{
		parent::setup();

		$this->used_globals = array_keys($GLOBALS);
	}


	/**
	 * Restore previous GLOBAL scope, by unsetting the ones created since {@link setUp()}.
	 */
	function tearDown()
	{
		$used_globals = array_keys($GLOBALS);

		foreach( array_diff( $used_globals, $this->used_globals ) as $k )
		{
			unset( $GLOBALS[$k] );
		}

		parent::tearDown();
	}


	function test_defaults()
	{
		$this->assertIdentical( param( 'test1' ), '' );
	}


	function test_typing()
	{

		// The following is somewhat odd behaviour, but ut's a touchy subject, so as long as it works like that,
		// let's leave it like that.
		$_POST['test1'] = '';
		$this->assertEqual( param( 'test1', 'array', array() ), '' );

		$this->assertIdentical( param( 'test2', 'array', array() ), array() );
	}


	/**
	 *
	 */
	function test_param()
	{
		$GLOBALS['arr'] = NULL;

		$arr = param( 'arr', 'array', array() );
		$this->assertTrue( gettype($arr) == 'array' );
	}


	/**
	 * Test {@link url_add_param()}
	 */
	function test_url_add_param()
	{
		$this->assertEqual( url_add_param('foo', 'bar', '&'), 'foo?bar' );
		$this->assertEqual( url_add_param('foo#anchor', 'bar', '&'), 'foo?bar#anchor' );
	}

}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new ParamFuncsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
