<?php
/**
 * Tests for miscellaneous functions.
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


global $inc_path;

require_once( $inc_path.'MODEL/antispam/_antispam.funcs.php' );
require_once( $inc_path.'_misc/_misc.funcs.php' );


/**
 * @package tests
 */
class MiscFuncsTestCase extends EvoUnitTestCase
{
	function MiscFuncsTestCase()
	{
		$this->EvoUnitTestCase( 'Miscellaneous functions test' );
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
									) as $lText => $lExpexted )
		{
			$this->assertEqual( make_clickable($lText), $lExpexted );
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
			'one and two' );

		$this->assertEqual(
			implode_with_and( array('one', 'two', 'three') ),
			'one, two and three' );
	}


	function test_validate_url()
	{
		// valid:
		foreach( array(
			'mailto:example@example.org',
			'http://b2evolution.net',
			'https://www.b2evolution.net',
			'http://user@example.com/path',
			'http://user:pass@example.com/path',
			) as $url )
		{
			$r = validate_url( $url, $GLOBALS['comments_allowed_uri_scheme'] );
			$this->assertFalse( $r, $url.' allowed in comments' );

			$r = validate_url( $url, $GLOBALS['allowed_uri_scheme'] );
			$this->assertFalse( $r, $url.' allowed in general' );
		}

		// invalid:
		foreach( array(
			'http://',
			'http://&amp;',
			'http://<script>...</script>',
			) as $url )
		{
			$r = validate_url( $url, $GLOBALS['comments_allowed_uri_scheme'] );
			$this->assertTrue( $r, $url.' allowed in comments' );

			$r = validate_url( $url, $GLOBALS['allowed_uri_scheme'] );
			$this->assertTrue( $r, $url.' allowed in general' );
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
			'[[]] [[foo]] [[bar]] [[]]' );

		// Replace anything outside <pre></pre> and <code></code> that's not in a tag (smilies plugin):
		$this->assertEqual(
			callback_on_non_matching_blocks( 'foo <code>FOOBAR</code> bar ',
				'~<(code|pre)[^>]*>.*?</\1>~is',
				'callback_on_non_matching_blocks',
				array( '~<[^>]*>~', array(&$this, 'helper_test_callback_on_non_matching_blocks') ) ),
			'[[foo ]]<code>FOOBAR</code>[[ bar ]]' );
	}


	/**
	 * Helper method for {@link test_callback_on_non_matching_blocks()}.
	 *
	 * @return string
	 */
	function helper_test_callback_on_non_matching_blocks( $text )
	{
		return '[['.$text.']]';
	}


	/**
	 * Test {@link get_ban_domain()}
	 */
	function test_get_ban_domain()
	{
		$this->assertEqual( get_ban_domain('www.example.com'), '.example.com' );
		$this->assertEqual( get_ban_domain('www2.example.com'), '.example.com' );
		$this->assertEqual( get_ban_domain('http://www.example.com'), '.example.com' );
		$this->assertEqual( get_ban_domain('http://www2.example.com'), '.example.com' );
		$this->assertEqual( get_ban_domain('http://www.example.com/path/'), '.example.com' );
		$this->assertEqual( get_ban_domain('http://www2.example.com/path/?query=1'), '.example.com' );
		$this->assertEqual( get_ban_domain('https://www.example.com'), '.example.com' );
		$this->assertEqual( get_ban_domain('https://www2.example.com'), '.example.com' );
		$this->assertEqual( get_ban_domain('http://sub2.sub1.example.com'), '.example.com' );
		$this->assertEqual( get_ban_domain('http://sub3.sub2.sub1.example.com'), '.example.com' );
		$this->assertEqual( get_ban_domain('http://hostname'), '//hostname' );
		$this->assertEqual( get_ban_domain('http://hostname.tld'), '//hostname.tld' );
	}


	/**
	 * Test {@link url_same_protocol()}
	 */
	function test_url_same_protocol()
	{
		$this->assertEqual( url_same_protocol( 'http://example.com', 'https://example.com/admin/' ),
			'https://example.com' );

		$this->assertEqual( url_same_protocol( 'https://example.com', 'https://example.com/admin/' ),
			'https://example.com' );

		$this->assertEqual( url_same_protocol( 'http://example.com', 'http://example.com/admin/' ),
			'http://example.com' );

		$this->assertEqual( url_same_protocol( 'https://example.com', 'http://example.com/admin/' ),
			'http://example.com' );
	}


	/**
	 * Test {@link format_to_output()}
	 */
	function test_format_to_output()
	{
		$this->assertEqual( format_to_output('<a href="">link</a>  text', 'text'), 'link text' );
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
	$test = new MiscFuncsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
