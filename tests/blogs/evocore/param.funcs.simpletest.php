<?php
/**
 * Tests for param functions.
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


global $inc_path;

require_once( $inc_path.'_misc/_param.funcs.php' );


/**
 * @package tests
 */
class ParamFuncsTestCase extends EvoUnitTestCase
{
	function ParamFuncsTestCase()
	{
		$this->EvoUnitTestCase( 'Param functions test' );
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
		// QUESTION: dh> do we expect the first test to fail really?

		$_POST['test1'] = '';
		$this->assertIdentical( param( 'test1', 'array', array() ), array() );
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

}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new ParamFuncsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
