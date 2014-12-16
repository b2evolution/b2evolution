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

load_funcs('antispam/model/_antispam.funcs.php');
load_funcs('_core/_url.funcs.php');


/**
 * @package tests
 */
class MiscFuncsTestCase extends EvoUnitTestCase
{
	function __construct()
	{
		parent::__construct( 'Miscellaneous functions test' );
	}


	function test_make_clickable()
	{
		foreach( array(
				'http://b2evolution.net' => '<a href="http://b2evolution.net">http://b2evolution.net</a>',
				'http://www.logitech.com/index.cfm/products/detailsharmony/US/EN,CRID=2084,CONTENTID=8747' => '<a href="http://www.logitech.com/index.cfm/products/detailsharmony/US/EN,CRID=2084,CONTENTID=8747">http://www.logitech.com/index.cfm/products/detailsharmony/US/EN,CRID=2084,CONTENTID=8747</a>',
				'Please look at http://this.com, and tell me what you think.' => 'Please look at <a href="http://this.com">http://this.com</a>, and tell me what you think.',
				'https://paypal.com' => '<a href="https://paypal.com">https://paypal.com</a>',
				'www.google.de' => '<a href="http://www.google.de">www.google.de</a>',
				'www.google.de, and www.yahoo.com.' => '<a href="http://www.google.de">www.google.de</a>, and <a href="http://www.yahoo.com">www.yahoo.com</a>.',
				'See http://www.google.de.' => 'See <a href="http://www.google.de">http://www.google.de</a>.',
				'See https://www.google.de, or www.yahoo.com/test?a=b,c=d.' => 'See <a href="https://www.google.de">https://www.google.de</a>, or <a href="http://www.yahoo.com/test?a=b,c=d">www.yahoo.com/test?a=b,c=d</a>.',
				'www. ' => 'www. ',
				'www.example.org' => '<a href="http://www.example.org">www.example.org</a>',

				'http://user@somewhere.com' => '<a href="http://user@somewhere.com">http://user@somewhere.com</a>',
				'<a href="http://setiathome.berkeley.edu">SETI@Home</a>' => '<a href="http://setiathome.berkeley.edu">SETI@Home</a>',

				'<a href="mailto:test@example.org">test@example.org</a>' => '<a href="mailto:test@example.org">test@example.org</a>',
				'<a href="mailto:test@example.org">test@example.org</a>test2@example.org' => '<a href="mailto:test@example.org">test@example.org</a><a href="mailto:test2@example.org">test2@example.org</a>',
				'mailto://postmaster' => '<a href="mailto://postmaster">mailto://postmaster</a>',
				// aim:

				// icq:
				'wanna chat? icq:878787.' => 'wanna chat? <a href="http://wwp.icq.com/scripts/search.dll?to=878787">878787</a>.',

				'<img src="http://example.com/" />' => '<img src="http://example.com/" />',
				'<img src=http://example.com/ />' => '<img src=http://example.com/ />',
				'<div>http://example.com/</div>' => '<div><a href="http://example.com/">http://example.com/</a></div>',
			) as $lText => $lExpected )
		{
			$this->assertEqual( make_clickable($lText), $lExpected );
		}
	}


	function test_is_email()
	{
		$must_match = array(
			'single' => array(),
			'rfc2822' => array(
				'My Name <my.name@example.org>',

				// taken from http://www.regexlib.com/REDetails.aspx?regexp_id=711
				'name.surname@blah.com',
				'Name Surname <name.surname@blah.com>',
				'"b. blah"@blah.co.nz',
				// taken from RFC (http://rfc.net/rfc2822.html#sA.1.2.)
				'"Joe Q. Public" <john.q.public@example.com>',
				'Mary Smith <mary@x.test>',
				'jdoe@example.org',
				'Who? <one@y.test>',
				'<boss@nil.test>',
				'"Giant; \"Big\" Box" <sysservices@example.net>',
				),
			'all' => array(
				'my.name@example.org',
				),
			);

		$must_not_match = array(
			'single' => array(
				'My Name <my.name@example.org>', // no single address
				),
			'rfc2822' => array(
				' me@example.org',

				// taken from http://www.regexlib.com/REDetails.aspx?regexp_id=711
				'name surname@blah.com',
				'name."surname"@blah.com',
				'name@bla-.com',
				),
			'all' => array(
				'',
				'a@b',
				'abc',
				'a @ b',
				'a @ example.org',
				'a@example.org ',
				' example.org',
				),
			);

		// must match:
		foreach( $must_match['single'] as $l_email )
		{
			$this->assertTrue( is_email( $l_email, 'single' ), 'single: '.$l_email );
		}
		foreach( $must_match['rfc2822'] as $l_email )
		{
			$this->assertTrue( is_email( $l_email, 'rfc2822' ), 'rfc2822: '.$l_email );
		}
		foreach( $must_match['all'] as $l_email )
		{
			$this->assertTrue( is_email( $l_email, 'single' ), 'single: '.$l_email );
			$this->assertTrue( is_email( $l_email, 'rfc2822' ), 'rfc2822: '.$l_email );
		}

		// must not match
		foreach( $must_not_match['single'] as $l_email )
		{
			$this->assertFalse( is_email( $l_email, 'single' ), 'single: '.$l_email );
		}
		foreach( $must_not_match['rfc2822'] as $l_email )
		{
			$this->assertFalse( is_email( $l_email, 'rfc2822' ), 'rfc2822: '.$l_email );
		}
		foreach( $must_not_match['all'] as $l_email )
		{
			$this->assertFalse( is_email( $l_email, 'single' ), 'single: '.$l_email );
			$this->assertFalse( is_email( $l_email, 'rfc2822' ), 'rfc2822: '.$l_email );
		}
	}


	function test_implode_with_and()
	{
		$this->assertEqual(
			implode_with_and( array() ),
			'' );

		$this->assertEqual(
			implode_with_and( array('one') ),
			'one' );

		$this->assertEqual(
			implode_with_and( array('one', 'two') ),
			'one &amp; two' );

		$this->assertEqual(
			implode_with_and( array('one', 'two', 'three') ),
			'one, two &amp; three' );
	}


	function test_validate_url()
	{
		$this->change_global('evo_charset', 'latin1');

		// valid:
		foreach( array(
			'http://b2evolution.net',
			'https://demo.b2evolution.net',
			'http://user@example.com/path',
			'http://user:pass@example.com/path',
			'mailto:example@example.org',
			'mailto:example@example.org?subject=TEST',
			'http://läu.de/',
			'http://läu.de/foo bar',
			) as $url )
		{
			$r = validate_url( $url, 'commenting', false );
			// True means validation ok
			$this->assertFalse( $r, $url.' NOT allowed in comments' );
		}

		foreach( array(
			'http://b2evolution.net',
			'https://demo.b2evolution.net',
			'http://user@example.com/path',
			'http://user:pass@example.com/path',
			'mailto:example@example.org',
			'mailto:example@example.org?subject=TEST',
			'http://läu.de/',
			'/foobar',
			'/foobar#anchor',
			'#anchor',
			) as $url )
		{
			$r = validate_url( $url, 'posting', false );
			$this->assertFalse( $r, $url.' NOT allowed in posts' );
		}

		// invalid:
		foreach( array(
			'http://',
			'http://&amp;',
			'http://<script>...</script>',
			'mailto:www.example.com',
			'foobar',
			) as $url )
		{
			$r = validate_url( $url, 'commenting', false );
			// True means validation rejected
			$this->assertTrue( $r, $url.' allowed in comments' );

			$r = validate_url( $url, 'posting', false );
			$this->assertTrue( $r, $url.' allowed in posts' );
		}
	}


	/**
	 * Tests {@link callback_on_non_matching_blocks()}.
	 */
	function test_callback_on_non_matching_blocks()
	{
		$this->assertEqual(
			callback_on_non_matching_blocks( 'foo bar', '~\s~', array(&$this, 'helper_test_callback_on_non_matching_blocks') ),
			'[[foo]] [[bar]]' );

		$this->assertEqual(
			callback_on_non_matching_blocks( ' foo bar ', '~\s~', array(&$this, 'helper_test_callback_on_non_matching_blocks') ),
			' [[foo]] [[bar]] ' );

		// Replace anything outside <pre></pre> and <code></code> that's not in a tag (smilies plugin):
		$this->assertEqual(
			callback_on_non_matching_blocks( 'foo <code>FOOBAR</code> bar ',
				'~<(code|pre)[^>]*>.*?</\1>~is',
				'callback_on_non_matching_blocks',
				array( '~<[^>]*>~', array(&$this, 'helper_test_callback_on_non_matching_blocks') ) ),
			'[[foo]] <code>FOOBAR</code> [[bar]] ' );
	}


	/**
	 * Helper method for {@link test_callback_on_non_matching_blocks()}.
	 *
	 * @return string
	 */
	function helper_test_callback_on_non_matching_blocks( $text )
	{
		return preg_replace( '/(foo|bar)/i', '[[$1]]', $text );
	}


	/**
	 * Test {@link get_base_domain()}
	 */
	function test_get_base_domain()
	{
		$this->change_global('evo_charset', 'iso-8859-1');

		$this->assertEqual( get_base_domain(''), '' ); // Example: empty referer
		$this->assertEqual( get_base_domain('hostname'), 'hostname' );
		$this->assertEqual( get_base_domain('http://hostname'), 'hostname' );
		$this->assertEqual( get_base_domain('www.example.com'), 'example.com' );
		$this->assertEqual( get_base_domain('www2.example.com'), 'www2.example.com' );  // We no longer treat www2.ex.com equal to ex.com
		$this->assertEqual( get_base_domain('subdom.example.com'), 'subdom.example.com' );
		$this->assertEqual( get_base_domain('https://www.hello.example.com/path/1/2/3/page.html?param=hello#location'), 'hello.example.com' );
		$this->assertEqual( get_base_domain('https://www.sub1.hello.example.com/path/1/2/3/page.html?param=hello#location'), 'hello.example.com' );
		$this->assertEqual( get_base_domain('https://sub1.hello.example.com/path/1/2/3/page.html?param=hello#location'), 'hello.example.com' );
		$this->assertEqual( get_base_domain('https://hello.example.com/path/1/2/3/page.html?param=hello#location'), 'hello.example.com' );
		$this->assertEqual( get_base_domain('https://hello.example.com:8080/path/1/2/3/page.html?param=hello#location'), 'hello.example.com' );

		// Anchor after domain name, used by spammers:
		$this->assertEqual( get_base_domain('http://example.com#anchor'), 'example.com' );
		$this->assertEqual( get_base_domain('http://example.com/#anchor'), 'example.com' );

		// "-" is a valid char:
		$this->assertEqual( get_base_domain('host-name'), 'host-name' );
		$this->assertEqual( get_base_domain('www-2.host-name.tld'), 'www-2.host-name.tld' );

		// IDN:
		$this->assertEqual( get_base_domain('käse'), 'käse' );
		$this->assertEqual( get_base_domain('öl.de'), 'öl.de' );
		$this->assertEqual( get_base_domain('www-öl.käse-öl.de'), 'www-öl.käse-öl.de' );
		$this->assertEqual( get_base_domain('sub1.sub2.pröhl.de'), 'sub2.pröhl.de' );

		// Numerical, should be kept:
		$this->assertIdentical( get_base_domain( '123.123.123.123' ), '123.123.123.123' );
		$this->assertIdentical( get_base_domain( '123.123.123.123:8080' ), '123.123.123.123' );

		// Invalid, but ok:
		// fp> This function is called get_base_domain(), not validate_domain() . If we receive a domain starting with a _, then it is not a problem to keep it in the base domain.
		$this->assertEqual( get_base_domain('_host'), '_host' );

		// The following may not be valid in the future but seem good enough for now:
		$this->assertEqual( get_base_domain('.de'), 'de' );
		$this->assertEqual( get_base_domain('.....de'), 'de' );
		$this->assertIdentical( get_base_domain('...'), '' );
		$this->assertIdentical( get_base_domain( '1..' ), '' );
		$this->assertIdentical( get_base_domain( chr(0) ), '' );
	}


	/**
	 * Test {@link get_ban_domain()}
	 */
	function test_get_ban_domain()
	{
		$this->assertEqual( get_ban_domain('www.example.com'), '.example.com' );
		$this->assertEqual( get_ban_domain('www2.example.com'), '.example.com' );
		$this->assertEqual( get_ban_domain('http://hostname'), '//hostname' );
		$this->assertEqual( get_ban_domain('http://hostname.tld'), '//hostname.tld' );
		$this->assertEqual( get_ban_domain('http://www.example.com'), '.example.com' );
		$this->assertEqual( get_ban_domain('http://www2.example.com'), '.example.com' );
		$this->assertEqual( get_ban_domain('http://www2.example.com/'), '.example.com' );
		$this->assertEqual( get_ban_domain('http://www.example.com/path/'), '.example.com/path/' );
		$this->assertEqual( get_ban_domain('http://www.example.com/path/page.html'), '.example.com/path/' );
		$this->assertEqual( get_ban_domain('http://www2.example.com/path/?query=1'), '.example.com/path/' );
		$this->assertEqual( get_ban_domain('http://www2.example.com/path/page.html?query=1'), '.example.com/path/' );
		$this->assertEqual( get_ban_domain('http://example.com/path/'), '//example.com/path/' );
		$this->assertEqual( get_ban_domain('http://example.com/path/sub/?query=1'), '//example.com/path/sub/' );
		$this->assertEqual( get_ban_domain('http://example.com/path/sub/page.html'), '//example.com/path/sub/' );
		$this->assertEqual( get_ban_domain('http://example.com/path/sub/page.html?query=1'), '//example.com/path/sub/' );
		$this->assertEqual( get_ban_domain('http://example.com:8080/path/sub/page.html?query=1'), '//example.com:8080/path/sub/' );
		$this->assertEqual( get_ban_domain('https://www.example.com'), '.example.com' );
		$this->assertEqual( get_ban_domain('https://www2.example.com'), '.example.com' );
		$this->assertEqual( get_ban_domain('http://sub2.sub1.example.com'), '//sub2.sub1.example.com' );
		$this->assertEqual( get_ban_domain('http://sub3.sub2.sub1.example.com'), '//sub3.sub2.sub1.example.com' );
		$this->assertEqual( get_ban_domain('http://sub3.sub2.sub1.example.com'), '//sub3.sub2.sub1.example.com' );
		$this->assertIdentical( get_ban_domain(''), false );

		// Anchor after domain name, used by spammers:
		$this->assertEqual( get_ban_domain('http://example.com#anchor'), '//example.com' );
		$this->assertEqual( get_ban_domain('http://example.com/#anchor'), '//example.com' );
	}


	/**
	 * Test {@link format_to_output()}
	 */
	function test_format_to_output()
	{
		$this->change_global('evo_charset', 'latin1');

		$this->assertEqual( format_to_output('<a href="">link</a>  text', 'text'), 'link text' );
		$this->assertEqual( format_to_output('<b>®µ¥¿-test</b>', 'htmlbody'), '<b>&#174;&#181;&#165;&#191;-test</b>' );
		$this->assertEqual( format_to_output('<b>®µ¥¿-test</b>', 'xml'), '&#174;&#181;&#165;&#191;-test' );
		$this->assertEqual( format_to_output( chr(128).'&#128;' ), '&#8364;&#8364;' ); // Euro sign, Windows style

		$this->change_global('evo_charset', 'utf-8');
		$this->assertEqual( format_to_output('<a href="">link</a>  text', 'text'), 'link text' );
		$this->assertEqual( format_to_output('<b>®µ¥¿-test</b>', 'htmlbody'), '<b>®µ¥¿-test</b>' );
		$this->assertEqual( format_to_output('<b>®µ¥¿-test</b>', 'xml'), '®µ¥¿-test' );

		$this->assertEqual( format_to_output('2 > &1', 'htmlbody'), '2 > &amp;1' );
	}


	/**
	 * Tests {@link make_rel_links_abs()}.
	 */
	function test_make_rel_links_abs()
	{
		$this->assertEqual(
			make_rel_links_abs('foo <a href="/bar">bar</a>', 'http://example.com'),
			'foo <a href="http://example.com/bar">bar</a>' );
		$this->assertEqual(
			make_rel_links_abs('foo <a href="http://test/bar">bar</a> <img src="/bar" />', 'http://example.com'),
			'foo <a href="http://test/bar">bar</a> <img src="http://example.com/bar" />' );
	}


	/**
	 * Tests {@link test_convert_charset()}.
	 */
	function test_convert_charset()
	{
		$this->assertEqual( convert_charset( 'éêèë', 'utf-8', 'latin1' ), 'Ã©ÃªÃ¨Ã«' );
		$this->assertEqual( convert_charset( 'Ã©ÃªÃ¨Ã«', 'latin1', 'utf-8' ), 'éêèë' );
		$this->assertEqual( convert_charset( 'Ã©ÃªÃ¨Ã«', 'Latin1', 'UTF-8' ), 'éêèë' );
		$this->assertEqual( convert_charset( 'Ã©ÃªÃ¨Ã«', 'Latin1', 'Utf8' ), 'éêèë' );

		// THIS ONE will produce NO conversion because 'latin-1' is not a valid charset name for this func
		$this->assertEqual( convert_charset( 'éêèë', 'utf-8', 'latin-1' ), 'éêèë' );
	}


	/**
	 * Test {@link strmaxlen()}
	 */
	function test_strmaxlen()
	{
		$this->assertEqual( strmaxlen('foo', 3), 'foo' );
		$this->assertEqual( strmaxlen('foo', 2), 'f&hellip;' );
		$this->assertEqual( strmaxlen('foo', 2, '.'), 'f.' );
		$this->assertEqual( strmaxlen('foobar', 6, '...'), 'foobar' );
		$this->assertEqual( strmaxlen('foobar', 5, '...'), 'fo...' );
		$this->assertEqual( strmaxlen('foobar', 5, '&amp;&hellip;'), 'foo&amp;&hellip;' );

		$this->assertEqual( strmaxlen('M?', 2), 'M?', 'Do not cut utf8 char in the middle' );

		$this->assertEqual( strmaxlen('1', 1, '&hellip;'), '1' );
		$this->assertEqual( strmaxlen('1', 1, '...'), '1' );
		$this->assertEqual( strmaxlen('123', 1, '...'), '...' );
		$this->assertEqual( strmaxlen('12345', 1, '...'), '...' );

		$this->assertEqual( strmaxlen('1&2', 3, NULL, 'htmlbody'), '1&amp;2' );
		$this->assertEqual( strmaxlen('1&2', 3, NULL, 'raw'), '1&2' );
		$this->assertEqual( strmaxlen('1&2', 3), '1&2' );

		$this->assertEqual( strmaxlen('1&amp;2', 10, NULL, 'htmlbody'), '1&amp;2' );
		$this->assertEqual( strmaxlen('1&amp;2', 10, NULL, 'formvalue'), '1&amp;amp;2' );

		# special cases, where entities must not get cut in the middle
		$this->assertEqual( strmaxlen('1&amp;2', 5, NULL, 'htmlbody'), '1&hellip;' );
		$this->assertEqual( strmaxlen('1&amp;22', 7, NULL, 'htmlbody'), '1&amp;&hellip;' );
		$this->assertEqual( strmaxlen('1&amp;2', 3, NULL, 'formvalue'), '1&hellip;' );
		$this->assertEqual( strmaxlen('1&    2', 3, NULL, 'formvalue'), '1&amp;&hellip;' );
		$this->assertEqual( strmaxlen('1&2', 3, NULL, 'formvalue'), '1&amp;2' );
		$this->assertEqual( strmaxlen('12345678901234567890&amp;', 21, NULL, 'formvalue'),
			'12345678901234567890&hellip;' );
		$this->assertEqual( strmaxlen('123456789012345&amp;', 21, NULL, 'formvalue'),
			'123456789012345&amp;amp;' );

		$this->assertEqual( strmaxlen('foo ', 3), 'foo' );
		$this->assertEqual( strmaxlen('foo ', 4), 'foo' );
		$this->assertEqual( strmaxlen('foo bar', 3), 'fo&hellip;' );
		$this->assertEqual( strmaxlen('foo bar', 4), 'foo&hellip;' );
		$this->assertEqual( strmaxlen('foo bar', 5), 'foo&hellip;' );
		$this->assertEqual( strmaxlen('foo bar', 6), 'foo b&hellip;' );

		// test cut_at_whitespace:
		$this->assertEqual( strmaxlen('foo bar', 5, ''), 'foo b' );
		$this->assertEqual( strmaxlen('foo bar', 5, '', 'raw', true), 'foo' );
		$this->assertEqual( strmaxlen('foo bar', 5, '.', 'raw', true), 'foo.' );
		$this->assertEqual( strmaxlen('foo bar', 4, '.', 'raw', true), 'foo.' );
		$this->assertEqual( strmaxlen('foo bar', 2, '', 'raw', true), 'fo' );
		$this->assertEqual( strmaxlen('foo bar', 2, '..', 'raw', true), '..' );
		$this->assertEqual( strmaxlen("foo\nbar", 2, '', 'raw', true), 'fo' );
		$this->assertEqual( strmaxlen("foo\nbar", 3, '', 'raw', true), 'foo' );
		$this->assertEqual( strmaxlen("foo\nbar", 4, '', 'raw', true), 'foo' );
	}


	/**
	 * Test {@link strmaxwords()}
	 */
	function test_strmaxwords()
	{
		$this->assertEqual( strmaxwords('foo bar', 2), 'foo bar' );
		$this->assertEqual( strmaxwords('foo  bar', 2), 'foo  bar' );
		$this->assertEqual( strmaxwords('foo  bar  ', 2), 'foo  bar  ' );
		$this->assertEqual( strmaxwords('  foo  bar  ', 2), '  foo  bar  ' );
		$this->assertEqual( strmaxwords('  <img />foo  bar  ', 2), '  <img />foo  bar  ' );
		$this->assertEqual( strmaxwords('  <img />foo  bar  ', 1), '  <img />foo  &hellip;' );
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new MiscFuncsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
