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
		$_POST['test1[]'] = '';
		$this->assertEqual( param( 'test1', 'array', array() ), array() );

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
		$_POST['p1'] = '';
		$_POST['p2'] = '';
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


	function test_param_check_serialized_array()
	{
		// Simple array:
		//   $params = array( 'a' => 'b' );
		set_param( 'params', 'a:1:{s:1:"a";s:1:"b";}' );
		$this->assertTrue( param_check_serialized_array( 'params' ) );

		// Simple object:
		//   $params = new stdClass();
		//   $params->a = 'b';
		set_param( 'params', 'O:8:"stdClass":1:{s:1:"a";s:1:"b";}' );
		$this->assertFalse( param_check_serialized_array( 'params' ) );

		// Array contains an object with string key:
		//   $Object = new stdClass();
		//   $Object->a = 'b';
		//   $params = array( 'a' => 'b', 'c' => $Object );
		set_param( 'params', 'a:2:{s:1:"a";s:1:"b";s:1:"c";O:8:"stdClass":1:{s:1:"a";s:1:"b";}}' );
		$this->assertFalse( param_check_serialized_array( 'params' ) );

		// Array contains an object with integer key:
		//   $Object = new stdClass();
		//   $Object->a = 'b';
		//   $params = array( 'a' => 'b', 123 => $Object );
		set_param( 'params', 'a:2:{s:1:"a";s:1:"b";i:123;O:8:"stdClass":1:{s:1:"a";s:1:"b";}}' );
		$this->assertFalse( param_check_serialized_array( 'params' ) );

		// Array contains a string with PART of fake object structure:
		//   $params = array( 'a' => 'b', 'c' => ';O:8:' );
		set_param( 'params', 'a:2:{s:1:"a";s:1:"b";s:1:"c";s:5:";O:8:";}' );
		$this->assertTrue( param_check_serialized_array( 'params' ) );

		// Array contains a string with FULL fake object structure:
		//   $params = array( 'a' => 'b', 'c' => 'a:2:{s:1:"a";s:1:"b";i:123;O:8:"stdClass":1:{s:1:"a";s:1:"b";}}' );
		set_param( 'params', 'a:2:{s:1:"a";s:1:"b";s:1:"c";s:63:"a:2:{s:1:"a";s:1:"b";i:123;O:8:"stdClass":1:{s:1:"a";s:1:"b";}}";}' );
		$this->assertTrue( param_check_serialized_array( 'params' ) );

		// Array contains a string with FULL fake object structure AND object:
		//   $Object = new stdClass();
		//   $Object->a = 'b';
		//   $params = array( 'a' => 'b', 'c' => 'a:2:{s:1:"a";s:1:"b";i:123;O:8:"stdClass":1:{s:1:"a";s:1:"b";}}', 'd' => $Object );
		set_param( 'params', 'a:3:{s:1:"a";s:1:"b";s:1:"c";s:63:"a:2:{s:1:"a";s:1:"b";i:123;O:8:"stdClass":1:{s:1:"a";s:1:"b";}}";s:1:"d";O:8:"stdClass":1:{s:1:"a";s:1:"b";}}' );
		$this->assertFalse( param_check_serialized_array( 'params' ) );

		// String:
		//   $params = 'a';
		set_param( 'params', 's:1:"a";' );
		$this->assertFalse( param_check_serialized_array( 'params' ) );

		// Integer:
		//   $params = 456;
		set_param( 'params', 'i:456;' );
		$this->assertFalse( param_check_serialized_array( 'params' ) );

		// Float:
		//   $params = 123.456;
		set_param( 'params', 'd:123.4560000000000030695446184836328029632568359375;' );
		$this->assertFalse( param_check_serialized_array( 'params' ) );

		// NULL:
		//   $params = NULL;
		set_param( 'params', 'N;' );
		$this->assertFalse( param_check_serialized_array( 'params' ) );
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new ParamFuncsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
