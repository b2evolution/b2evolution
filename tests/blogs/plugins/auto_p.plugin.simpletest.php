<?php
/**
 * Tests for the {@link auto_p_plugin AutoP Plugin}.
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


require_once $plugins_path.'_auto_p.plugin.php';


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

		// Fake DB entry:
		$this->Plugins->index_ID_rows[1] = array(
			'plug_ID' => 1,
			'plug_priority' => 50,
			'plug_classname' => 'auto_p_plugin',
			'plug_code' => 'fake',
			'plug_apply_rendering' => 'always',
			'plug_status' => 'enabled',
			'plug_version' => '0' /* TODO: should be the same as from classfile */ );
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

		$this->Plugin->RenderItemAsHtml( $params );



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
		$this->assertWantedPattern( '~^<p>foo</p><code>asdf</code><p>bar</p>$~',
			$this->render_wo_space( "foo\n<code>asdf</code>bar" ) );

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

		$this->assertWantedPattern( '~^<table><tr><td><p>foo</p></td></tr></table>$~',
			$this->render_wo_space( "<table><tr><td>\n\nfoo\n\n</td></tr></table>" ) );

		$this->assertEqual( '',
			$this->render_wo_space( "\n\n\n\n\n" ) );

		$this->assertEqual( '<p>foo<ahref="">Link</a>bar</p>',
			$this->render_wo_space( "foo <a href=\"\">Link</a> bar" ) );

		// ignore PRE blocks:
		$this->assertEqual( "<p>one paragraph</p><pre>first line\n\nsecond line</pre><p>2nd para</p>",
			$this->render( "one paragraph<pre>first line\n\nsecond line</pre>2nd para" ) );

		// multiple paragraphs in one block:
		$this->assertEqual( "<div><p>one paragraph</p><p>2nd para</p></div>",
			$this->render( "<div>one paragraph\n\n2nd para</div>" ) );

		// HR:
		$this->assertEqual( "<p>one</p><hr /><p>two</p>",
			$this->render( "one<hr />two" ) );

		// P in P:
		$this->assertEqual( "<p>one\n\ntwo</p><p>three</p>",
			$this->render( "<p>one\n\ntwo</p><p>three</p>" ) );

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
