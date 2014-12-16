<?php
/**
 * Tests for the {@link wikilinks_plugin Wiki links plugin}.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


/**
 * @package tests
 */
class WikilinksPluginTestCase extends EvoPluginUnitTestCase
{
	function __construct()
	{
		parent::__construct( 'Wiki links plugin test' );
	}


	function setUp()
	{
		parent::setup();
		$this->Plugin = $this->get_Plugin('wikilinks_plugin');
	}


	function testRegularLinks()
	{
		foreach( array(
				'[[http://url]]' => '<a href="http://url">http://url</a>',
				'[[http://url text]]' => '<a href="http://url">text</a>',
				'((http://url))' => '<a href="http://url">http://url</a>',
				'((http://url text))' => '<a href="http://url">text</a>',

				'[[http://url,]]' => '<a href="http://url,">http://url,</a>',
				'[[http://url text,]]' => '<a href="http://url">text,</a>',
				'[[http://url, text,]]' => '<a href="http://url,">text,</a>',
									) as $lText => $lExpexted )
		{
			$params = array( 'data' => $lText, 'format' => 'htmlbody' );
			$this->Plugin->RenderMessageAsHtml( $params );
			$this->assertEqual( $params['data'], $lExpexted );
		}
	}


}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new WikilinksPluginTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
