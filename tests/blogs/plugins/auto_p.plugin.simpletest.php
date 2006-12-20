<?php
/**
 * Tests for the {@link auto_p_plugin AutoP Plugin}.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );

require_once $GLOBALS['plugins_path'].'_auto_p.plugin.php';


/**
 * @package tests
 */
class AutoPPluginTestCase extends PluginUnitTestCase
{
	function AutoPPluginTestCase()
	{
		$this->PluginUnitTestCase( 'Auto-P plugin test' );
	}


	function setUp()
	{
		parent::setup();

		$this->Plugin = & $this->get_fake_Plugin('auto_p_plugin');
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
	 *
	 * @return string
	 */
	function _render( $data )
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
	 * @return string
	 */
	function render( $data, $p_in_block = true )
	{
		$this->Plugin->Settings->set('add_p_in_block', $p_in_block);
		return $this->_render($data);
	}


	/**
	 * @return string
	 */
	function render_no_p_in_blocks( $data )
	{
		return $this->render($data, false);
	}


	/**
	 * Test special tags ("<!--more-->", "<!--nextpage-->" and "<!--noteaser-->")
	 */
	function test_special_tags()
	{
		$this->assertEqual( "<p>foo</p><!--more--><p>bar</p>",
			$this->render( 'foo<!--more-->bar' ) );

		$this->assertEqual( "<p>foo</p><!--more--><!--noteaser--><p>bar</p>",
			$this->render( 'foo<!--more--><!--noteaser-->bar' ) );

		$this->assertEqual( "<p>foo</p><!--nextpage--><p>bar</p>",
			$this->render( 'foo<!--nextpage-->bar' ) );

		$this->assertEqual( "<p>foo</p><!--noteaser--><p>bar</p>",
			$this->render( 'foo<!--noteaser-->bar' ) );
	}


	/**
	 * Test rendering of the Auto-P plugin.
	 */
	function test_render()
	{
		$this->assertEqual( "<p>asdf</p>",
			$this->render( 'asdf' ) );

		$this->assertEqual( "<p>foo<br />\nbar</p>",
			$this->render( "foo\nbar" ) );

		$this->assertEqual( "<p>foo</p>\n\n<p>bar</p>",
			$this->render( "foo\n\nbar" ) );

		// a table:
		$this->assertEqual( "<p>foo</p><table>\n<tr>\n\n<td>foo</td>\n\n</tr>\n\n</table>\n",
			$this->render( "foo<table>\n<tr>\n\n<td>foo</td>\n\n</tr>\n\n</table>\n" ) );

		$this->assertEqual( "<table><tr><td><p>FOO<br />\nbar</p>\n\n</td></tr></table>",
			$this->render( "<table><tr><td>FOO\nbar\n\n</td></tr></table>" ) );

		$this->assertEqual( "<table><tr><td>FOO<br />\nbar\n\n</td></tr></table>",
			$this->render_no_p_in_blocks( "<table><tr><td>FOO\nbar\n\n</td></tr></table>" ) );

		// <blockquote> is special:
		$this->assertEqual( "<blockquote><p>asdf</p></blockquote>",
			$this->render( "<blockquote>asdf</blockquote>" ) );

		$this->assertWantedPattern( '~^<p>foo</p><blockquote><p>asdf</p></blockquote>$~',
			$this->render_wo_space( 'foo<blockquote>asdf</blockquote>' ) );

		$this->assertWantedPattern( '~^<p>foo</p><blockquote><p>asdf</p></blockquote>$~',
			$this->render_wo_space( 'foo<blockquote>asdf</blockquote>' ) );

		$this->assertWantedPattern( '~^<p>foo</p><blockquote><p>asdf</p></blockquote>$~',
			$this->render_wo_space( "foo<blockquote>asdf\n</blockquote>" ) );

		$this->assertEqual( "<p>foo</p><blockquote><p>asdf_two_newlines_follow</p>\n\n</blockquote>",
			$this->render( "foo<blockquote>asdf_two_newlines_follow\n\n</blockquote>" ) );

		$this->assertEqual( "<p>foo</p><blockquote><p>asdf<br />\ntwolines<br />\nfollow</p></blockquote>",
			$this->render( "foo<blockquote>asdf\ntwolines\nfollow</blockquote>" ) );

		// non-block in between:
		$this->assertEqual( "<p>foo<br />\n<code>asdf</code>bar</p>",
			$this->render( "foo\n<code>asdf</code>bar" ) );

		// block element in between:
		$this->assertEqual( "<p>foo</p>\n<div>asdf</div><p>bar</p>",
			$this->render( "foo\n<div>asdf</div>bar" ) );

		// block element in between (without newline):
		$this->assertWantedPattern( '~^<p>foo</p><div>asdf</div><p>bar</p>$~',
			$this->render_wo_space( "foo<div>asdf</div>bar" ) );

		// Valid table:
		$this->assertWantedPattern( '~^<table><tr><td>foo</td></tr></table>$~',
			$this->render_wo_space( "<table><tr><td>foo</td></tr></table>" ) );

		// Invalid table:
		$this->assertEqual( "<table><tr><td>foo</td></td></table>",
			$this->render( "<table><tr><td>foo</td></td></table>" ) );

		$this->assertEqual( "<table><tr><td>\n\n\n<p>foo</p>\n\n\n</td></tr></table>",
			$this->render( "<table><tr><td>\n\n\nfoo\n\n\n</td></tr></table>" ) );

		$this->assertEqual( "\n\n\n\n\n",
			$this->render( "\n\n\n\n\n" ) );

		$this->assertEqual( '<p>foo <a href="">Link</a> bar</p>',
			$this->render( 'foo <a href="">Link</a> bar' ) );

		// ignore PRE blocks:
		$this->assertEqual( "<p>one paragraph</p><pre>first line\n\nsecond line</pre><p>2nd para</p>",
			$this->render( "one paragraph<pre>first line\n\nsecond line</pre>2nd para" ) );

		// multiple paragraphs in one block:
		$this->assertEqual( "<div><p>one paragraph</p>\n\n<p>2nd para</p></div>",
			$this->render( "<div>one paragraph\n\n2nd para</div>" ) );

		// HR:
		$this->assertEqual( "<p>one</p><hr /><p>two</p>",
			$this->render( "one<hr />two" ) );

		// Newline handling:
		$this->assertEqual( "<p>T</p>\n\n",
			$this->render( "T\n\n" ) );

		$this->assertEqual( "<p>T</p>\n\n\n",
			$this->render( "T\n\n\n" ) );

		$this->assertEqual( "<p>T</p>\n\n<p>T2</p>\n\n",
			$this->render( "T\n\nT2\n\n" ) );

		$this->assertEqual( "<div><p>one</p>\n\n</div>",
			$this->render( "<div>one\n\n</div>" ) );

		$this->assertEqual( "<div>one\n\n</div>",
			$this->render_no_p_in_blocks( "<div>one\n\n</div>" ) );

		$this->assertEqual( "<div><p>one</p>\n\n\n</div>",
			$this->render( "<div>one\n\n\n</div>" ) );

		$this->assertEqual( "<div>\n<p>FOO</p>\n\n</div>",
			$this->render( "<div>\nFOO\n\n</div>" ) );

		$this->assertEqual( "<div>\n\n<p>FOO</p>\n\n</div>",
			$this->render( "<div>\n\nFOO\n\n</div>" ) );

		$this->assertEqual( "<div>\n\n<p>FOO</p>\n\n\n</div>",
			$this->render( "<div>\n\nFOO\n\n\n</div>" ) );

		$this->assertEqual( "<div>\n\n<p>FOO</p>\n\n\n\n</div>",
			$this->render( "<div>\n\nFOO\n\n\n\n</div>" ) );

		$this->assertEqual( "<div>\n\n<p>FOO</p>\n\n\n\n\n</div>",
			$this->render( "<div>\n\nFOO\n\n\n\n\n</div>" ) );


		// no P in P:
		$this->assertEqual( "<p>one<br />\n<br />\ntwo</p><p>three</p>",
			$this->render( "<p>one\n\ntwo</p><p>three</p>" ) );

		$this->assertEqual( "<p>one<br />\n<br />\ntwo</p><p>three</p>",
			$this->render( "<p>one\n\ntwo</p><p>three</p>" ) );

		$this->assertEqual( "<p>para</p>\n\n\n\n<div><p><img /></p>\n\n\n<p>para2a<br />\npara2b</p>\n\n</div><div><img /></div>",
			$this->render( "para\n\n\n\n<div><img />\n\n\npara2a\npara2b\n\n</div><div><img /></div>" ) );

		$this->assertEqual( "<p>para1</p>\n\n<p>para2 <a href=x>X</a>!</p>\n\n<div class=x><img ... /></div>",
			$this->render( "para1\n\npara2 <a href=x>X</a>!\n\n<div class=x><img ... /></div>" ) );

		$this->assertEqual( "<div>\npara1\n</div>",
			$this->render( "<div>\npara1\n</div>" ) );

		$this->assertEqual( "<div><img /></div>\n\n<p>Text</p>",
			$this->render( "<div><img /></div>\n\nText" ) );

		$this->assertEqual( "\n<p>foo</p>",
			$this->render( "\nfoo" ) );

		$this->assertEqual( "\n\n<p>foo</p>",
			$this->render( "\n\nfoo" ) );

		$this->assertEqual( "\n\n\n\n\n\n<p>foo</p>",
			$this->render( "\n\n\n\n\n\nfoo" ) );

		$this->assertEqual( "\n\n\n<div>FOO</div>\n",
			$this->render( "\n\n\n<div>FOO</div>\n" ) );

		// newline at beginning
		$this->assertEqual( "\n\n<div>FOO</div>\n<p>BAR</p>\n",
			$this->render( "\n\n<div>FOO</div>\nBAR\n" ) );

		// newline at end
		$this->assertEqual( "<div>FOO</div>\n\n",
			$this->render( "<div>FOO</div>\n\n" ) );

		// newline at end (nested)
		$this->assertEqual( "<div>FOO_1</div><div>FOO_2<div>FOO_3</div>\n\n</div>\n\n",
			$this->render( "<div>FOO_1</div><div>FOO_2<div>FOO_3</div>\n\n</div>\n\n" ) );

		$this->assertEqual( "\n<p><code>FOO<br />\nBAR<br />\n<br />\n<br />\nFOOBAR</code></p>\n",
			$this->render( "\n<code>FOO\nBAR\n\n\nFOOBAR</code>\n" ) );

		$this->assertEqual( "<p>foo<br />\n <a>SPAN</a> bar</p>",
			$this->render( "foo\n <a>SPAN</a> bar" ) );

		$this->assertEqual( "<p>FOO</p><ins><div>DIV</div></ins><p>BAR</p>",
			$this->render( "FOO<ins><div>DIV</div></ins>BAR" ) );

		$this->assertEqual( "<p>FOO</p><ins><hr style='' /></ins>TEST</invalid><p>BAR</p>",
			$this->render( "FOO<ins><hr style='' /></ins>TEST</invalid>BAR" ) );

		$this->assertEqual( "<p>TEXT</p>\n\n<ins><hr /></ins>",
			$this->render( "TEXT\n\n<ins><hr /></ins>" ) );

		$this->assertEqual( "<p>FOO</p>\n\n<p>BAR</p>",
			$this->render( "<p>FOO</p>\n\n<p>BAR</p>" ) );

		$this->assertEqual( "<p>blah</p>\n\n\n\n<p>blah</p>",
			$this->render( "<p>blah</p>\n\n\n\n<p>blah</p>" ) );

		$this->assertEqual( "<p>paragraph1</p>\n\n<hr />\n\n<p>paragraph2</p>\n\n<hr />\n\n<p>paragraph3</p>",
			$this->render( "paragraph1\n\n<hr />\n\nparagraph2\n\n<hr />\n\nparagraph3" ) );

		$this->assertEqual(
			'<blockquote><p>FOO <strong>BAR</strong> FOOBAR.</p></blockquote>',
			$this->render( "<blockquote>FOO <strong>BAR</strong> FOOBAR.</blockquote>" ) );

		$this->assertEqual(
			"<p><strong>FOO</strong><br />\nBAR</p>",
			$this->render( "<strong>FOO</strong>\nBAR" ) );

		$this->assertEqual(
			"<p><em><br />\nFOO</em></p>",
			$this->render( "<em>\nFOO</em>" ) );

		$this->assertEqual(
			"<p><em>FOO<br />\n</em></p>",
			$this->render( "<em>FOO\n</em>" ) );

		$this->assertEqual(
			"<ul>\n<li>FOO<br />\nBAR\n</li>\n\n\n</ul>",
			$this->render( "<ul>\n<li>FOO\nBAR\n</li>\n\n\n</ul>" ) );

		$this->assertEqual(
			"<div class=\"image_block\">\n<img /></div>",
			$this->render( "<div class=\"image_block\">\n<img /></div>" ) );

		$this->assertEqual(
			"<p><img class=\"foo\"/> <img class=\"bar\"  /></p>",
			$this->render('<img class="foo"/> <img class="bar"  />') );

	}

}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone

	$Timer = new Timer();
	$Debuglog = new Log();

	$test = new AutoPPluginTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
