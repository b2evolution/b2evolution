<?php
/**
 * Tests for image functions.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


load_funcs('files/model/_image.funcs.php');


/**
 * @package tests
 */
class ImageFuncsTestCase extends EvoUnitTestCase
{
	function __construct()
	{
		parent::__construct( 'Image functions test' );
	}


	/**
	 * Test {@link crop_to_constraint()}
	 */
	function test_crop_to_constraint()
	{
		// 50x50 => 20x20
		$this->assertEqual( crop_to_constraint(50, 50, 20, 20), array(0, 0, 50, 50) );
	}


	/**
	 * Test {@link scale_to_constraint()}
	 */
	function test_scale_to_constraint()
	{
		$this->assertEqual( scale_to_constraint(50, 50, 20, 20), array(20, 20) );
		$this->assertEqual( scale_to_constraint(50, 100, 100, 10), array(5, 10) );

		// Tests with NULL
		$this->assertEqual( scale_to_constraint(50, 70, 50, NULL), array(50, 70) );
		$this->assertEqual( scale_to_constraint(50, 70, 25, NULL), array(25, 35) );
		$this->assertEqual( scale_to_constraint(50, 70, NULL, 35), array(25, 35) );
		$this->assertEqual( scale_to_constraint(50, 70, NULL, NULL), array(50, 70) );
	}
}

if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new ImageFuncsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>