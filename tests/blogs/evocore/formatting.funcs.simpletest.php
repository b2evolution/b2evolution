<?php
/**
 * Tests for miscellaneous functions.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


global $inc_path;

#require_once( $inc_path.'_misc/_formatting.funcs.php' );
require_once( $inc_path.'_misc/_misc.funcs.php' );


/**
 * @package tests
 */
class FormattingFuncsTestCase extends EvoUnitTestCase
{
	function FormattingFuncsTestCase()
	{
		$this->EvoUnitTestCase( 'Miscellaneous functions test' );
	}


	/**
	 * Test {@link balanceTags()}
	 */
	function test_balanceTags()
	{
		$this->assertEqual( balanceTags( '<div><!-- comment --></div>' ), '<div><!-- comment --></div>' );
		$this->assertEqual( balanceTags( '<div><!-- comment -->' ), '<div><!-- comment --></div>' );
		$this->assertEqual( balanceTags( '<!-- comment --></div>' ), '<!-- comment -->' );

		$this->assertEqual( balanceTags( '<div> text </div>' ), '<div> text </div>' );
		$this->assertEqual( balanceTags( '<div> text ' ), '<div> text </div>' );
		$this->assertEqual( balanceTags( ' text </div>' ), ' text ' );
	}

}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new FormattingFuncsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
