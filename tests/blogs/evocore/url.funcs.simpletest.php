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

require_once( $inc_path.'_misc/_url.funcs.php' );


/**
 * @package tests
 */
class UrlFuncsTestCase extends EvoUnitTestCase
{
	function UrlFuncsTestCase()
	{
		$this->EvoUnitTestCase( 'URL functions test' );
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
	}

}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new UrlFuncsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
