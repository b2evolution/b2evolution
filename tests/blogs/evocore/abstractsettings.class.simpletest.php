<?php
/**
 * Tests for the {@link AbstractSettings} class.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


/**
 * @package tests
 */
class AbstractSettingsTestCase extends EvoMockDbUnitTestCase
{
	var $mocked_DB_methods = array('get_results', 'query');

	function __construct()
	{
		parent::__construct( 'AbstractSettings class test' );
	}


	function setUp()
	{
		parent::setup();

		$this->TestSettings =& new AbstractSettings( 'testtable', array( 'test_name' ), 'test_value' );
	}


	function test_load()
	{
		$this->MockDB->expectOnce( 'get_results', array( new PatternExpectation('/SELECT test_name, test_value\s+FROM testtable/i') ), 'DB select ok.' );
		$this->TestSettings->load_all();
		$this->TestSettings->load_all();
	}


	/**
	 * Check if we get defaults right.
	 */
	function test_defaults()
	{
		$this->TestSettings->_defaults = array(
			'default_1' => '1',
			'default_abc' => 'abc',
		);

		$this->TestSettings->load_all();
		$this->assertEqual( 'abc', $this->TestSettings->get_default( 'default_abc' ) );

		$this->assertEqual( 'abc', $this->TestSettings->get( 'default_abc' ) );

		// After delete it should return the default again:
		$this->TestSettings->set( 'default_abc', 'foo' );
		$this->TestSettings->delete( 'default_abc' );
		$this->assertEqual( 'abc', $this->TestSettings->get( 'default_abc' ) );
	}


	/**
	 * Tests AbstractSettings::set()
	 */
	function test_PreferExplicitSet()
	{
		$this->MockDB->expectOnce( 'get_results', array( new PatternExpectation('/SELECT test_name, test_value\s+FROM testtable/i') ), 'DB select ok.' );
		$this->TestSettings->set( 'lala', 1 );

		$this->MockDB->expectNever( 'get_results', false, 'Did not reload settings from DB.' );
		$this->TestSettings->load_all();

		$this->assertEqual( $this->TestSettings->get( 'lala' ), 1, 'Prefer setting which was set before explicit load().' );
		$this->assertNull( $this->TestSettings->get( 'lala_notset' ), 'Return NULL for non-existing setting.' );
	}


	/**
	 *
	 */
	function test_delete_of_nonexistent()
	{
		$this->MockDB->expectAt( 0, 'query', array(
			new IdenticalExpectation("REPLACE INTO testtable (test_name, test_value) VALUES ( 'foobar', '1' )") ), 'DB REPLACE-INTO ok.' );
		$this->MockDB->expectCallCount('query', 1);

		// Should not do any query:
		$this->assertFalse( $this->TestSettings->delete('does-not-exist') );
		$this->assertFalse( $this->TestSettings->dbupdate() );

		// Should do a query:
		$this->assertTrue( $this->TestSettings->set('foobar', 1) );
		$this->TestSettings->dbupdate(); // TODO: should return true, but is mocked.

		// Should not do any query:
		$this->assertFalse( $this->TestSettings->delete('does-not-exist') );
		$this->assertFalse( $this->TestSettings->dbupdate() );
	}


	/**
	 *
	 */
	function test_multicolumndelete()
	{
		$TestSettings = new AbstractSettings( 'testtable', array( 'test_key1', 'test_key2' ), 'test_value' );
		$this->assertFalse( $TestSettings->set('foobar', 1) );
		$this->assertTrue( $TestSettings->set('foobar', 1, 1) );
	}


	function test_usersettings()
	{
		$us = new UserSettings();

		$this->assertFalse( $us->get('foo') ); // no current user
		$this->assertNull( $us->get('foo', 1) ); // not set
		$this->assertTrue( $us->set('foo', 'bar', 1) ); // successfully set
		$this->assertEqual('bar', $us->get('foo', 1));
		$us->dbupdate();
	}


	function test_loadonlyonce_nocachebycolkeys()
	{
		$this->MockDB->expectOnce( 'get_results', array( new PatternExpectation('/^\s*SELECT key1, key2, val\s+FROM T_test$/i') ), 'DB select-all-once ok.' );

		$s = new AbstractSettings( 'T_test', array( 'key1', 'key2' ), 'val', 0 );
		$s->get(1, 'foo');
		$s->get(2, 'bar');
	}


	function test_loadonlyonce_cachebycolkeys1of2()
	{
		$this->MockDB->expectOnce( 'get_results', array( new PatternExpectation(
			'/^\s*SELECT key1, key2, val\s+FROM T_test WHERE key1 = \'1\'$/i') ), 'DB select-all-once ok.' );

		$s = new AbstractSettings( 'T_test', array( 'key1', 'key2' ), 'val', 1 );
		$s->get(1, 'foo');
		$s->get(1, 'bar');
	}


	function test_loadonlyonce_cachebycolkeys2of2()
	{
		$this->MockDB->expectAt( 0, 'get_results', array( new PatternExpectation(
			'/^\s*SELECT key1, key2, key3, val\s+FROM T_test WHERE key1 = \'1\' AND key2 = \'2\'$/i') ), 'DB select-all-once ok.' );

		$this->MockDB->expectAt( 1, 'get_results', array( new PatternExpectation(
			'/^\s*SELECT key1, key2, key3, val\s+FROM T_test WHERE key1 = \'1_2\' AND key2 = \'2\'$/i') ), 'DB select-all-once ok.' );

		$s = new AbstractSettings( 'T_test', array( 'key1', 'key2', 'key3' ), 'val', 2 );
		// 1st query
		$s->get(1, 2, 3);
		$s->get(1, 2, 3);
		$s->get(1, 2, '3_2');

		// 2nd
		$s->get('1_2', 2, 3);
		$s->get('1_2', 2, '3_2');
	}


	function test_get_undefined_then_set_updates_db()
	{
		$this->MockDB->expectOnce( 'query', array(
			new IdenticalExpectation("REPLACE INTO T_test (key1, key2, val) VALUES ( '1', 'foo', '1' )") ), 'DB REPLACE-INTO ok.' );

		$s = new AbstractSettings( 'T_test', array( 'key1', 'key2' ), 'val', 0 );
		$s->get(1, 'foo');
		$s->set(1, 'foo', 1);
		$s->dbupdate();
	}


	function test_delete_goes_back_to_default()
	{
		$s = $this->TestSettings;
		$s->_defaults = array('foo' => 'bar');

		$this->assertEqual('bar', $s->get('foo'));
		$s->set('foo', 'barbar');
		$s->dbupdate();
		$this->assertEqual('barbar', $s->get('foo'));

		$s->delete('foo');
		$this->assertEqual('bar', $s->get('foo'));
	}


	function test_pluginusersettings()
	{
		load_class('plugins/model/_pluginusersettings.class.php', 'PluginUserSettings');
		$s = new PluginUserSettings('plugin_ID');
		$this->assertTrue( $s->set('set_setting', 'set_value', 'user_ID') );
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new AbstractSettingsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
