<?php
/**
 * Tests for misc. external functions / libraries.
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../config.simpletest.php' );


global $inc_path;

/**
 * Includes
 */
load_class('_ext/idna/_idna_convert.class.php', 'idna_convert');
load_class( 'xhtml_validator/_xhtml_validator.class.php', 'XHTML_Validator' );


/**
 * Testcase for external libraries, shipped with b2evo.
 *
 * @package tests
 */
class ExtLibsTestCase extends EvoUnitTestCase
{
	function __construct()
	{
		parent::__construct( 'ExtLibs functions test' );
	}


	/**
	 * Test {@link XHTML_Validator::check()} for encoding issues.
	 * NOTE: assignment by "& new" is required for PHP4! See also http://de3.php.net/manual/en/function.xml-set-object.php#46107
	 *       Alternatively, multiple vars for each test may work, or unsetting the last one..
	 */
	function test_htmlchecker_check_encoding()
	{
		global $allowed_tags, $allowed_attributes, $uri_attrs, $allowed_uri_scheme;

		if( ! function_exists('utf8_encode') )
		{
			$this->fail( 'utf8_encode() not available, cannot test.' );
			return;
		}
		$context = 'posting';
		$allow_css_tweaks = false;
		$allow_iframes = false;
		$allow_javascript = false;
		$allow_objects = false;

		// default encoding
		$SHC = new XHTML_Validator($context, $allow_css_tweaks, $allow_iframes, $allow_javascript, $allow_objects );
		$SHC->check( 'foo bar' );
		$this->assertTrue( $SHC->isOK() );

		$SHC = new XHTML_Validator($context, $allow_css_tweaks, $allow_iframes, $allow_javascript, $allow_objects, 'ISO-8859-1' );
		$SHC->check( 'foo дц bar' );

		$this->assertTrue( $SHC->isOK() );

		$SHC = new XHTML_Validator($context, $allow_css_tweaks, $allow_iframes, $allow_javascript, $allow_objects );
		$SHC->check( utf8_encode('foo дц bar') );
		$this->assertTrue( $SHC->isOK() );

		$SHC = new XHTML_Validator($context, $allow_css_tweaks, $allow_iframes, $allow_javascript, $allow_objects, 'utf-8' );
		$SHC->check( 'foo bar' );
		$this->assertTrue( $SHC->isOK() );

		$SHC = new XHTML_Validator($context, $allow_css_tweaks, $allow_iframes, $allow_javascript, $allow_objects, 'utf-8' );
		$SHC->check( utf8_encode('foo дц bar' ) );
		$this->assertTrue( $SHC->isOK() );

		$SHC = new XHTML_Validator($context, $allow_css_tweaks, $allow_iframes, $allow_javascript, $allow_objects, 'utf-8' );
		$SHC->check( 'foo дц bar' );
		$this->assertFalse( $SHC->isOK() );

		$SHC = new XHTML_Validator($context, $allow_css_tweaks, $allow_iframes, $allow_javascript, $allow_objects, 'iso-8859-1' );
		$SHC->check( 'foo дц bar' );
		$this->assertTrue( $SHC->isOK() );

		$SHC = new XHTML_Validator($context, $allow_css_tweaks, $allow_iframes, $allow_javascript, $allow_objects, 'iso-8859-15' );
		$SHC->check( utf8_encode('foo д bar') );
		$this->assertTrue( $SHC->isOK() );

		if( function_exists('mb_convert_encoding') )
		{
			$this->assertEqual( $SHC->encoding, 'UTF-8' ); // should have been converted to UTF-8
		}
		else
		{
			$this->assertEqual( $SHC->encoding, 'ISO-8859-15' );
		}

		$SHC = new XHTML_Validator($context, $allow_css_tweaks, $allow_iframes, $allow_javascript, $allow_objects, 'iso-8859-1' );
		$SHC->check( utf8_encode('foo д bar') );
		$this->assertTrue( $SHC->isOK() );
		$this->assertEqual( $SHC->encoding, 'ISO-8859-1' );
	}


	/**
	 * Test {@link SafeHtmlChecker::check()}.
	 * NOTE: assignment by "& new" is required for PHP4! See also http://de3.php.net/manual/en/function.xml-set-object.php#46107
	 *       Alternatively, multiple vars for each test may work, or unsetting the last one..
	 */
	function test_htmlchecker_check()
	{
		global $Messages;

		$context = 'posting';
		$allow_css_tweaks = false;
		$allow_iframes = false;
		$allow_javascript = false;
		$allow_objects = false;

		$SHC = new XHTML_Validator($context, $allow_css_tweaks, $allow_iframes, $allow_javascript, $allow_objects );
		$SHC->check( '<moo>foo</moo>' );
		$this->assertEqual( $GLOBALS['Messages']->messages['error'][0],
			T_('Illegal tag').': <code>moo</code>' );
		$Messages->clear();

		$SHC = new XHTML_Validator($context, $allow_css_tweaks, $allow_iframes, $allow_javascript, $allow_objects );
		$SHC->check( '<img>foo</img>' );
		$this->assertEqual( $GLOBALS['Messages']->messages['error'][0],
			sprintf( T_('Tag &lt;%s&gt; may not contain raw character data'), '<code>img</code>' ) );

	}

}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new ExtLibsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
