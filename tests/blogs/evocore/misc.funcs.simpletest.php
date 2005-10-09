<?php
/**
 * Tests for miscellaneous functions.
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );

require_once( EVODIR.'blogs/evocore/_misc.funcs.php' );


/**
 *
 */
class MiscFuncsTestCase extends UnitTestCase
{
	function MiscFuncsTestCase()
	{
		$this->UnitTestCase( 'Miscellaneous functions test' );
	}


	function setUp()
	{
		parent::setup();
	}


	function tearDown()
	{
		parent::tearDown();
	}


	function testMake_clickable()
	{
		foreach( array(
				'http://b2evolution.net' => '<a href="http://b2evolution.net">http://b2evolution.net</a>',
				'http://www.logitech.com/index.cfm/products/detailsharmony/US/EN,CRID=2084,CONTENTID=8747' => '<a href="http://www.logitech.com/index.cfm/products/detailsharmony/US/EN,CRID=2084,CONTENTID=8747">http://www.logitech.com/index.cfm/products/detailsharmony/US/EN,CRID=2084,CONTENTID=8747</a>',
				'Please look at http://this.com, and tell me what you think.' => 'Please look at <a href="http://this.com">http://this.com</a>, and tell me what you think.',
				'https://paypal.com' => '<a href="https://paypal.com">https://paypal.com</a>',

				// aim:

				// icq:
				'wanna chat? icq:878787.' => 'wanna chat? <a href="http://wwp.icq.com/scripts/search.dll?to=878787">878787</a>.',
									) as $lText => $lExpexted )
		{
			$this->assertEqual( make_clickable($lText), $lExpexted );
		}
	}


	function testFormat_to_post_handles_lt_gt_in_code_and_pre_blocks()
	{
		$this->assertEqual(
			format_to_post("Here is some code:\n<code>if( 1 < 2 || 2 > 1 ) echo 'fine';</code>"),
			"Here is some code:\n<code>if( 1 &lt; 2 || 2 &gt; 1 ) echo 'fine';</code>" );

		$this->assertEqual(
			format_to_post("Here is some pre-formatted text:\n<pre>if( 1 < 2 || 2 > 1 ) echo 'fine';</pre>"),
			"Here is some pre-formatted text:\n<pre>if( 1 &lt; 2 || 2 &gt; 1 ) echo 'fine';</pre>" );
	}


}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new MiscFuncsTestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
