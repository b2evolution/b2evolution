<?php
/**
 * Tests for misc. external functions / libraries.
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../config.simpletest.php' );


/**
 * Includes
 */
require_once $inc_path.'_misc/ext/_idna_convert.class.php';


/**
 * Testcase for external libraries, shipped with b2evo.
 *
 * @package tests
 */
class ExtLibsTestCase extends EvoUnitTestCase
{
	function ExtLibsTestCase()
	{
		$this->UnitTestCase( 'ExtLibs functions test' );
	}


	function setUp()
	{
		parent::setup();
	}


	function tearDown()
	{
		parent::tearDown();
	}


	/**
	 * Tests {@link idna_convert::encode()}
	 */
	function test_IDNA_decode()
	{
		if( ! function_exists('utf8_encode') )
		{
			$this->fail( 'utf8_encode() not available, cannot test.' );
			return;
		}
		$IDNA = new Net_IDNA_php4();
		$this->assertEqual( $IDNA->encode( utf8_encode('läu.de') ), 'xn--lu-via.de' );
	}

}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new ExtLibsTestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
