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
		$this->assertIdentical( param( 'test1', 'string', NULL ), '' ); // set from first call

		$this->assertIdentical( param( 'test2', 'string', NULL ), NULL );
	}


	function test_typing()
	{
		// The following is somewhat odd behaviour, but it's a touchy subject, so as long as it works like that,
		// let's leave it like that.
		$_POST['test1'] = '';
		$this->assertEqual( param( 'test1', 'array', array() ), '' );

		$this->assertIdentical( param( 'test2', 'array', array() ), array() );
	}


	function test_param()
	{
		$GLOBALS['arr'] = NULL;

		$arr = param( 'arr', 'array', array() );
		$this->assertTrue( gettype($arr) == 'array' );
	}


	function test_param_check_passwords()
	{
		set_param('p1', '0');
		set_param('p2', '0');
		$this->assertTrue( param_check_passwords('p1', 'p2', true, 0) );

		set_param('p1', '000000');
		set_param('p2', '000000 ');
		$this->assertFalse( param_check_passwords('p1', 'p2') );

		set_param('p1', '1');
		set_param('p2', '2');
		$this->assertFalse( param_check_passwords('p1', 'p2') );
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new ParamFuncsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
