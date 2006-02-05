<?php
/**
 * Tests for the {@link wikilinks_plugin Wiki links plugin}.
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );

require_once( EVODIR.'blogs/plugins/_wikilinks.plugin.php' );


/**
 * @package tests
 */
class WikilinksPluginTestCase extends UnitTestCase
{
	function WikilinksPluginTestCase()
	{
		$this->UnitTestCase( 'Wiki links plugin test' );
	}


	function setUp()
	{
		parent::setup();
		$this->Plugin = new wikilinks_plugin();
	}


	function tearDown()
	{
		parent::tearDown();
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
			$this->Plugin->RenderItemAsHtml( $params );
			$this->assertEqual( $params['data'], $lExpexted );
		}
	}


}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new WikilinksPluginTestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
