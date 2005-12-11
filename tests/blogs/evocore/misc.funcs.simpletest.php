<?php
/**
 * Tests for miscellaneous functions.
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );

require_once( EVODIR.'blogs/evocore/_misc.funcs.php' );


/**
 *
 */
class MiscFuncsTestCase extends UnitTestCase
{
	function MiscFuncsTestCase()
	{
		$this->UnitTestCase( 'Miscellaneous functions test' );
	}


	function setUp()
	{
		parent::setup();
	}


	function tearDown()
	{
		parent::tearDown();
	}


	function testMake_clickable()
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

}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new MiscFuncsTestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
