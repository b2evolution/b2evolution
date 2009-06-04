<?php
/**
 * Tests for URL handling functions.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


global $inc_path;

load_funcs( '_core/_url.funcs.php' );


/**
 * @package tests
 */
class UrlFuncsTestCase extends EvoUnitTestCase
{
	function __construct()
	{
		parent::__construct( 'URL functions test' );
	}


	/**
	 * Test {@link url_rel_to_same_host()}
	 */
	function test_url_rel_to_same_host()
	{
		$this->assertEqual(
			url_rel_to_same_host('http://example.com/foobar', 'http://example.com/barfoo'),
			'/foobar' );

		$this->assertEqual(
			url_rel_to_same_host('http://example.com/foobar', 'https://example.com/barfoo'),
			'http://example.com/foobar' );

		$this->assertEqual(
			url_rel_to_same_host('http://example.com/foobar', '/barfoo'),
			'/foobar' );

		$this->assertEqual(
			url_rel_to_same_host('foobar', 'http://example.com/barfoo'),
			'foobar' );

		$this->assertEqual(
			url_rel_to_same_host('http://example.com/barfoo?f=b', 'https://example.com/barfoo'),
			'http://example.com/barfoo?f=b' );

		$this->assertEqual(
			url_rel_to_same_host('https://example.com/barfoo?f=b#a', 'https://user:pass@example.com/barfoo'),
			'https://example.com/barfoo?f=b#a' );

		$this->assertEqual(
			url_rel_to_same_host('foobar', 'http://example.com/barfoo'),
			'foobar' );

		// Tests for URLs without protocol. Currently failing.
		$this->assertEqual(
			url_rel_to_same_host('http://foo/bar', '//foo/baz'),
			'//foo/bar' );

		$this->assertEqual(
			url_rel_to_same_host('//foo/bar', 'https://foo/baz'),
			'//foo/bar' );
	}


	/**
	 * Tests {@link idna_encode()}
	 */
	function test_idna_encode()
	{
		global $evo_charset;
		$old_evo_charset = $evo_charset;
		$evo_charset = 'utf-8'; // this file
		$this->assertEqual( idna_encode( 'läu.de' ), 'xn--lu-via.de' );
		$evo_charset = $old_evo_charset;
	}


	/**
	 * Test {@link url_add_param()}
	 */
	function test_url_add_param()
	{
		$this->assertEqual( url_add_param('foo', 'bar', '&'), 'foo?bar' );
		$this->assertEqual( url_add_param('foo#anchor', 'bar', '&'), 'foo?bar#anchor' );
		
		$this->assertEqual( url_add_param('foo?', 'bar', '&'), 'foo?bar' );
		$this->assertEqual( url_add_param('foo?#anchor', 'bar', '&'), 'foo?bar#anchor' );
		$this->assertEqual( url_add_param('?', 'bar', '&'), '?bar' );
		$this->assertEqual( url_add_param('?#anchor', 'bar', '&'), '?bar#anchor' );
		$this->assertEqual( url_add_param('#anchor', 'bar', '&'), '?bar#anchor' );
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new UrlFuncsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
