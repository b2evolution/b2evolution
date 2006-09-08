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
		$this->UnitTestCase( 'Param functions test' );
	}


	/**
	 * Remember current GLOBAL scope.
	 */
	function setUp()
	{
		parent::setup();

		$this->old_GLOBALS = serialize($GLOBALS); // simulate cloning
	}


	/**
	 * Restore previous GLOBAL scope.
	 */
	function tearDown()
	{
		$GLOBALS = unserialize($this->old_GLOBALS); // simulate cloning

		parent::tearDown();
	}


	function test_defaults()
	{
		$this->assertIdentical( param( 'test1' ), '' );
	}


	function test_typing()
	{
		// QUESTION: do we expect the first test to fail really?

		$_POST['test1'] = '';
		$this->assertIdentical( param( 'test1', 'array', array() ), array() );
		$this->assertIdentical( param( 'test2', 'array', array() ), array() );
	}


	/**
	 *
	 *
	 * @return
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
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
