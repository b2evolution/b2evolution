<?php
/**
 * Tests for locale functions.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


load_funcs('locales/_locale.funcs.php');


/**
 * @package tests
 */
class LocaleFuncsTestCase extends EvoUnitTestCase
{
	function __construct()
	{
		parent::__construct( 'Locale functions test' );
	}


	/**
	 * Test {@link locale_from_httpaccept()}
	 */
	function test_locale_from_httpaccept()
	{
		$this->change_global('locales', array());
		$this->change_global('default_locale', '_DEFAULT_'); // to make it distinct
		$this->change_server('HTTP_ACCEPT_LANGUAGE', 'de,en;q=0.8,zh-hk;q=0.7,zh-cn;q=0.5,en-us;q=0.3,de-de;q=0.2');
		$this->assertEqual('_DEFAULT_', locale_from_httpaccept());
		$this->assertEqual('_DEFAULT_', locale_from_httpaccept());

		$this->change_global('locales', array(
			'de-CH' => array('enabled'=>true),
			'de-DE' => array('enabled'=>true),
			'en-US' => array('enabled'=>true),
			'zh-hk' => array('enabled'=>true),
		));
		$this->change_server('HTTP_ACCEPT_LANGUAGE', 'de,en;q=0.8,zh-hk;q=0.7,zh-cn;q=0.5,en-us;q=0.3,de-de;q=0.2');
		$this->assertEqual('de-DE', locale_from_httpaccept());
		$this->change_server('HTTP_ACCEPT_LANGUAGE', 'de,en;q=0.8,zh-hk;q=0.7,zh-cn;q=0.5,en-us;q=0.3');
		$this->assertEqual('de-DE', locale_from_httpaccept());
		$this->change_global('locales', array(
			'en-US' => array('enabled'=>true),
			'zh-hk' => array('enabled'=>true),
			'de-CH' => array('enabled'=>true),
		));
		$this->change_server('HTTP_ACCEPT_LANGUAGE', 'de,en;q=0.8,zh-hk;q=0.7,zh-cn;q=0.5,en-us;q=0.3');
		$this->assertEqual('de-CH', locale_from_httpaccept());
		$this->change_server('HTTP_ACCEPT_LANGUAGE', 'de,en,zh-hk;q=0.7,zh-cn;q=0.5,en-uk;q=0.3');
		$this->assertEqual('de-CH', locale_from_httpaccept());

		$this->change_global('locales', array(
			'en-US' => array('enabled'=>true),
			'en-UK' => array('enabled'=>true),
			'de-DE' => array('enabled'=>true),
		));
		$this->change_server('HTTP_ACCEPT_LANGUAGE', 'en-uk');
		$this->assertEqual('en-UK', locale_from_httpaccept());
		$this->change_server('HTTP_ACCEPT_LANGUAGE', 'en,en-uk');
		$this->assertEqual('en-UK', locale_from_httpaccept());
		$this->change_server('HTTP_ACCEPT_LANGUAGE', 'en,de-DE,en-uk;q=0.3');
		$this->assertEqual('en-UK', locale_from_httpaccept()); // not sure here, if it should be "de-DE" instead: same prio, but more specific
		$this->change_server('HTTP_ACCEPT_LANGUAGE', 'de-DE,en,en-uk;q=0.3');
		$this->assertEqual('de-DE', locale_from_httpaccept());

		// test "not acceptable" (q=0)
		$this->change_server('HTTP_ACCEPT_LANGUAGE', 'de-DE;q=0,foo');
		$this->assertEqual('_DEFAULT_', locale_from_httpaccept());

		$this->change_server('HTTP_ACCEPT_LANGUAGE', '');
		$this->assertEqual('_DEFAULT_', locale_from_httpaccept());
	}
}

if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new LocaleFuncsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
