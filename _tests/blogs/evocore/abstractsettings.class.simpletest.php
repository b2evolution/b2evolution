<?php
/**
 * Tests for the {@link AbstractSettings} class.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


load_class('plugins/model/_pluginusersettings.class.php', 'PluginUserSettings');


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

		$this->TestSettings = new AbstractSettings( 'testtable', array( 'test_name' ), 'test_value' );
	}


	function test_load()
	{
		$this->MockDB->expectOnce( 'get_results', array( new PatternExpectation('/SELECT test_name, test_value\s+FROM testtable/i'), ARRAY_A, 'Settings::load' ), 'DB select ok.' );
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

		// dbupdate should not kick in, when it has been set to the default (what delete does):
		$this->MockDB->returns('query', 1);
		$this->assertFalse($this->TestSettings->dbupdate());

		// setting the default value should not cause an update:
		$this->TestSettings->set( 'default_abc', $this->TestSettings->get_default('default_abc') );
		$this->assertFalse($this->TestSettings->dbupdate());

		// Saving int gets converted to string => no update:
		$this->TestSettings->set('default_1', 1 /* int not string */);
		$this->assertFalse($this->TestSettings->dbupdate()); // still, should get saved as string (=> no update)
		$this->assertIdentical($this->TestSettings->get('default_1'), '1');
	}


	function test_update_if_set_to_default_but_nondefault_in_db()
	{
		$s = new AbstractSettings( 'testtable', array( 'key1', 'key2', 'key3' ), 'val' );
		$s->_defaults = array('3' => 'defaultval');

		// Mock the return value of get_results, to simulate saved settings.
		$r = array(array('key1' => 1, 'key2' => 2,  'key3' => 3, 'val' => 'dbval' ));
		$this->MockDB->returns('get_results', $r);
		$this->MockDB->returns('query', 1);

		$s->load_all();

		$this->assertEqual( $s->get(1, 2, 3), 'dbval' );
		$this->assertEqual( $s->get_default(3), 'defaultval' );

		// Setting it to default value (with another value in DB) should update:
		$this->assertTrue( $s->set(1, 2, 3, 'defaultval') );
		$this->assertTrue( $s->dbupdate() );

		$this->assertTrue( $s->delete(1, 2, 3) );
		$this->assertEqual( $s->get(1, 2, 3), 'defaultval' );
		// If db value is the default value (set explicitly), do not remove (or update) it:
		$this->assertFalse( $s->dbupdate() );

		// Reload mocked db values.
		$s->reset();
		// Deleting a value should cause an update.
		$this->assertEqual( $s->get(1, 2, 3), 'dbval' );
		$this->assertTrue( $s->delete(1, 2, 3) );
		$this->assertEqual( $s->get(1, 2, 3), 'defaultval' );
		$this->assertTrue( $s->dbupdate() );
	}


	/**
	 * Tests AbstractSettings::set()
	 */
	function test_PreferExplicitSet()
	{
		$this->MockDB->expectOnce( 'get_results', array( new PatternExpectation('/SELECT test_name, test_value\s+FROM testtable/i'), ARRAY_A, 'Settings::load' ), 'DB select ok.' );
		$this->TestSettings->set( 'lala', 1 );

		$this->TestSettings->load_all();

		$this->assertEqual( $this->TestSettings->get( 'lala' ), 1, 'Prefer setting which was set before explicit load().' );
		$this->assertNull( $this->TestSettings->get( 'lala_notset' ), 'Return NULL for non-existing setting.' );
	}


	function test_delete_of_nonexistent()
	{
		$query_count = $this->MockDB->mock->getCallCount('query');
		$this->MockDB->expectCallCount('query', $query_count+1);
		$this->MockDB->expectAt( $query_count+1, 'query', array(
			new IdenticalExpectation("REPLACE INTO testtable (test_name, test_value) VALUES ( 'foobar', '1' )") ), 'DB REPLACE-INTO ok.' );

		// Should not do any query:
		$this->assertFalse( $this->TestSettings->delete('does-not-exist') );
		$this->assertFalse( $this->TestSettings->dbupdate() );

		// Should do a query:
		$this->assertTrue( $this->TestSettings->set('foobar', 1) );
		$this->assertFalse( $this->TestSettings->delete('does-not-exist') );
		$this->TestSettings->dbupdate(); // TODO: should return true, but is mocked.

		// Should not do any query:
		$this->assertFalse( $this->TestSettings->delete('does-not-exist') );
		$this->assertFalse( $this->TestSettings->dbupdate() );
	}


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
		$this->MockDB->expectOnce( 'get_results', array( new PatternExpectation('/^\s*SELECT key1, key2, val\s+FROM T_test$/i'), ARRAY_A, 'Settings::load' ), 'DB select-all-once ok.' );

		$s = new AbstractSettings( 'T_test', array( 'key1', 'key2' ), 'val', 0 );
		$s->get(1, 'foo');
		$s->get(2, 'bar');
	}


	function test_loadonlyonce_cachebycolkeys1of2()
	{
		$this->MockDB->expectOnce( 'get_results', array( new PatternExpectation(
			'/SELECT key1, key2, val\s+FROM T_test WHERE key1 = \'1\'/i'), ARRAY_A, 'Settings::load' ), 'DB select-all-once ok.' );

		$s = new AbstractSettings( 'T_test', array( 'key1', 'key2' ), 'val', 1 );
		$s->get(1, 'foo');
		$s->get(1, 'bar');
	}


	function test_loadonlyonce_cachebycolkeys2of2()
	{
		$this->MockDB->expectAt( 0, 'get_results', array( new PatternExpectation(
			'/^\s*SELECT key1, key2, key3, val\s+FROM T_test WHERE key1 = \'1\' AND key2 = \'2\'$/i'), ARRAY_A, 'Settings::load' ), 'DB select-all-once ok.' );

		$this->MockDB->expectAt( 1, 'get_results', array( new PatternExpectation(
			'/^\s*SELECT key1, key2, key3, val\s+FROM T_test WHERE key1 = \'1_2\' AND key2 = \'2\'$/i'), ARRAY_A, 'Settings::load' ), 'DB select-all-once ok.' );

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
		$query_count = $this->MockDB->mock->getCallCount('query');
		$this->MockDB->expectCallCount('query', $query_count+1);
		$this->MockDB->expectAt( $query_count+1, 'query', array(
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
		$s = new PluginUserSettings('plugin_ID');

		$this->assertTrue( $s->set('set_setting', 'set_value', 'user_ID') );
		$this->assertEqual( 'set_value', $s->get('set_setting', 'user_ID') );

		$this->assertEqual( 'set_value', $s->get('set_setting', 'user_ID') );

		//$this->MockDB->expectOnce( 'get_results', array( new PatternExpectation('/SELECT test_name, test_value\s+FROM testtable/i'), ARRAY_A ), 'DB select ok.' );
		$this->MockDB->returns('query', 1, array("REPLACE INTO T_pluginusersettings (puset_plug_ID, puset_user_ID, puset_name, puset_value) VALUES ( 'plugin_ID', 'user_ID', 'set_setting', 'set_value' )"));
		$this->assertTrue( $s->dbupdate() );

		$this->assertFalse( $s->delete('set_setting', 'non_existent') );

		// Delete after update must return true, too!
		$this->assertTrue( $s->delete('set_setting', 'user_ID') );
		$this->assertIdentical( NULL, $s->get('set_setting', 'user_ID') );
		$this->assertFalse( $s->delete('set_setting', 'user_ID') );
	}


	function test_dirty_cache_when_deleting_deep()
	{
		// query() should return 1 always; it does not get called when dbupdate() does not consider the cache to be dirty.
		$this->MockDB->returns('query', 1);

		$s = new AbstractSettings( 'T_test', array( 'key1', 'key2', 'key3' ), 'val', 0 );
		$this->assertTrue( $s->set(1, 2, 3, 'value'));
		$this->assertEqual($s->get(1, 2, 3), 'value');
		$this->assertTrue( $s->dbupdate());
		$this->assertFalse($s->dbupdate());
		$this->assertTrue( $s->delete(1, 2, 3));
		$this->MockDB->expect('query', array("DELETE FROM T_test WHERE (`key1` = '1' AND `key2` = '2' AND `key3` = '3')"));
		$this->assertTrue( $s->dbupdate());
	}


	function test_applySettingsCorrectlyForCachebycolkeys0()
	{
		$s = new AbstractSettings( 'T_test', array( 'key1', 'key2', 'key3' ), 'val', 0 );

		$s->_defaults = array('3' => 'bar');

		$this->MockDB->expectAt( 0, 'get_results', array(
			new PatternExpectation("/SELECT key1, key2, key3, val\s+FROM T_test$/"), ARRAY_A, 'Settings::load' ), 'DB SELECT ok.' );
		$this->MockDB->expectOnce('get_results');

		// Mock the return value of get_results, to simulate saved settings.
		$r = array(
			array('key1' => 1, 'key2' => 2,  'key3' => 3, 'val' => 4 ),
			array('key1' => 1, 'key2' => 22, 'key3' => 3, 'val' => 44),
			array('key1' => 2, 'key2' => 2,  'key3' => 2, 'val' => 2 ));
		$this->MockDB->setReturnValueAt(0, 'get_results', $r);

		// Should return the saved value, not default:
		$this->assertEqual( $s->get(1, 2, 3), '4' );
		$this->assertEqual( $s->get(1, 22, 3), '44' );

		$this->assertEqual($s->get(2, 2, 2), 2 );
	}


	function test_cache_only_once()
	{
		$this->MockDB->expectAt( 0, 'get_results', array(
			new PatternExpectation("/SELECT cset_coll_ID, cset_name, cset_value\s+FROM T_coll_settings$/"), ARRAY_A, 'Settings::load' ), 'DB SELECT ok.' );
		$this->MockDB->expectOnce('get_results');

		$s = new AbstractSettings( 'T_coll_settings', array( 'cset_coll_ID', 'cset_name' ), 'cset_value', 0 );
		$s->get( "1", "cache_enabled" );
		$s->get( "17", "title_link_type" );
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new AbstractSettingsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>