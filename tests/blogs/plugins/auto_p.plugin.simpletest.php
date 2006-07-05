<?php
/**
 * Tests for the {@link auto_p_plugin AutoP Plugin}.
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


require_once $GLOBALS['plugins_path'].'_auto_p.plugin.php';


/**
 * @package tests
 */
class AutoPPluginTestCase extends UnitTestCase
{
	function AutoPPluginTestCase()
	{
		$this->UnitTestCase( 'Auto-P plugin test' );
	}


	function setUp()
	{
		parent::setup();
		$this->Plugins = & new Plugins_no_DB();


		$GLOBALS['DB'] = new MockDB( $this );

		$real_Plugin = new auto_p_plugin();

		// Fake DB entry:
		$this->Plugins->index_ID_rows[1] = array(
			'plug_ID' => 1,
			'plug_priority' => 50,
			'plug_classname' => 'auto_p_plugin',
			'plug_code' => 'fake',
			'plug_apply_rendering' => 'always',
			'plug_status' => 'enabled',
			'plug_version' => $real_Plugin->version );
		$this->Plugins->register( 'auto_p_plugin', /* fake DB entry: */ 1 );
		$this->Plugin = & $this->Plugins->get_next();
	}


	function tearDown()
	{
		parent::tearDown();
	}


	/**
	 * Helper method. Calls RenderItemAsHtml and removes all whitespace in the result.
	 * @return string
	 */
	function render_wo_space( $data )
	{
		$data = $this->render( $data );

		return preg_replace( '~\s*~', '', $data );
	}


	/**
	 * Helper method. Calls RenderItemAsHtml.
	 * @return string
	 */
	function render( $data )
	{
		$params = array(
				'data' => $data,
				'format' => 'htmlbody',
			);

		#echo '<hr margin="2ex 0" />';

		#$before = $params['data'];

		$this->Plugin->RenderItemAsHtml( $params );

		#pre_dump( $before, $params['data'] );

		return $params['data'];
	}


	/**
	 * Test rendering of the Auto-P plugin.
	 */
	function test_render()
	{
		$this->assertWantedPattern( '~^<p>asdf</p>$~',
			$this->render_wo_space( 'asdf' ) );

		$this->assertWantedPattern( '~^<p>foo<br/>bar</p>$~',
			$this->render_wo_space( "foo\nbar" ) );

		$this->assertWantedPattern( '~^<p>foo</p><p>bar</p>\s*$~',
			$this->render_wo_space( "foo\n\nbar" ) );

		// a table:
		$this->assertEqual( "<p>foo</p><table>\n<tr>\n\n<td>foo</td>\n\n</tr>\n\n</table>\n",
			$this->render( "foo<table>\n<tr>\n\n<td>foo</td>\n\n</tr>\n\n</table>\n" ) );

		// <blockquote> is special:
		$this->assertWantedPattern( '~^<blockquote><p>asdf</p></blockquote>$~',
			$this->render_wo_space( '<blockquote>asdf</blockquote>' ) );

		$this->assertWantedPattern( '~^<p>foo</p><blockquote><p>asdf</p></blockquote>$~',
			$this->render_wo_space( 'foo<blockquote>asdf</blockquote>' ) );

		$this->assertWantedPattern( '~^<p>foo</p><blockquote><p>asdf</p></blockquote>$~',
			$this->render_wo_space( 'foo<blockquote>asdf</blockquote>' ) );

		$this->assertWantedPattern( '~^<p>foo</p><blockquote><p>asdf</p></blockquote>$~',
			$this->render_wo_space( "foo<blockquote>asdf\n</blockquote>" ) );

		$this->assertWantedPattern( '~^<p>foo</p><blockquote><p>asdf_two_newlines_follow</p></blockquote>$~',
			$this->render_wo_space( "foo<blockquote>asdf_two_newlines_follow\n\n</blockquote>" ) );

		$this->assertWantedPattern( '~^<p>foo</p><blockquote><p>asdf<br/>twolines<br/>follow</p></blockquote>$~',
			$this->render_wo_space( "foo<blockquote>asdf\ntwolines\nfollow</blockquote>" ) );

		// non-block in between:
		$this->assertEqual( "<p>foo<br />\n<code>asdf</code>bar</p>",
			$this->render( "foo\n<code>asdf</code>bar" ) );

		// block element in between:
		$this->assertWantedPattern( '~^<p>foo</p><div>asdf</div><p>bar</p>$~',
			$this->render_wo_space( "foo\n<div>asdf</div>bar" ) );
		// block element in between (without newline):
		$this->assertWantedPattern( '~^<p>foo</p><div>asdf</div><p>bar</p>$~',
			$this->render_wo_space( "foo<div>asdf</div>bar" ) );

		// Valid table:
		$this->assertWantedPattern( '~^<table><tr><td>foo</td></tr></table>$~',
			$this->render_wo_space( "<table><tr><td>foo</td></tr></table>" ) );

		// Invalid table:
		$this->assertWantedPattern( '~^<table><tr><td>foo</td></td></table>$~',
			$this->render_wo_space( "<table><tr><td>foo</td></td></table>" ) );

		$this->assertEqual( "<table><tr><td>\n<p></p>\n<p>foo\n</p>\n</td></tr></table>",
			$this->render( "<table><tr><td>\n\nfoo\n\n</td></tr></table>" ) );

		$this->assertEqual( '',
			$this->render_wo_space( "\n\n\n\n\n" ) );

		$this->assertEqual( '<p>foo<ahref="">Link</a>bar</p>',
			$this->render_wo_space( "foo <a href=\"\">Link</a> bar" ) );

		// ignore PRE blocks:
		$this->assertEqual( "<p>one paragraph</p><pre>first line\n\nsecond line</pre><p>2nd para</p>",
			$this->render( "one paragraph<pre>first line\n\nsecond line</pre>2nd para" ) );

		// multiple paragraphs in one block:
		$this->assertEqual( "<div><p>one paragraph</p>\n\n<p>2nd para</p></div>",
			$this->render( "<div>one paragraph\n\n2nd para</div>" ) );

		// HR:
		$this->assertEqual( "<p>one</p><hr /><p>two</p>",
			$this->render( "one<hr />two" ) );

		// P in P:
		$this->assertEqual( "<p>one\n\ntwo</p><p>three</p>",
			$this->render( "<p>one\n\ntwo</p><p>three</p>" ) );

		$this->assertEqual( "<p>para</p>\n\n<p></p>\n\n<div><p><img /></p>\n\n<p></p>\n<p>para2a<br />\npara2b\n</p>\n</div><div><img /></div>",
			$this->render( "para\n\n\n\n<div><img />\n\n\npara2a\npara2b\n\n</div><div><img /></div>" ) );

		$this->assertEqual( "<p>para1</p>\n\n<p>para2 <a href=x>X</a>!</p>\n\n<div class=x><img ... /></div>",
			$this->render( "para1\n\npara2 <a href=x>X</a>!\n\n<div class=x><img ... /></div>" ) );

		$this->assertEqual( "<div>\npara1\n</div>",
			$this->render( "<div>\npara1\n</div>" ) );

		$this->assertEqual( "<div>\nFOO\n\n</div>",
			$this->render( "<div>\nFOO\n\n</div>" ) );

		$this->assertEqual( "<div><img /></div>\n<p></p>\n<p>Text</p>",
			$this->render( "<div><img /></div>\n\nText" ) );

		$this->assertEqual( "<p></p>\n<p>foo</p>",
			$this->render( "\nfoo" ) );

		$this->assertEqual( "\n<p></p>\n\n<p></p>\n\n<p></p>\n<p>foo</p>",
			$this->render( "\n\n\n\n\n\nfoo" ) );

	}

}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone

	$Timer = new Timer();
	$Debuglog = new Log();

	$test = new AutoPPluginTestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
