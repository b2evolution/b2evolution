<?php
/**
 * Tests for the upgrade functions, mainly {@link db_delta()}.
 */

/**
 * SimpleTest config
 */
require_once dirname(__FILE__).'/../../config.simpletest.php';


/**
 * @package tests
 */
class UpgradeFuncsTestCase extends DbUnitTestCase
{
	function UpgradeFuncsTestCase()
	{
		$this->DbUnitTestCase( 'Upgrade funcs tests' );
	}


	function setUp()
	{
		parent::setup();

		$this->dropTestDbTables();
	}


	function tearDown()
	{
		parent::tearDown();
	}


	/**
	 * A wrapper to always execute the generated queries (check for SQL errors) and
	 * do not exclude any query types.
	 *
	 * @return array Generated queries, see {@link db_delta}.
	 */
	function db_delta_wrapper( $queries, $exclude = array() )
	{
		$this->test_DB->error = false; // reset any error

		$r = db_delta( $queries, true, $exclude );

		if( $this->test_DB->error )
		{
			pre_dump( $r );
		}

		return $r;
	}


	/**
	 * db_delta(): basic tests
	 */
	function test_db_delta()
	{
		$this->test_DB->query("
			CREATE TABLE test_1 (
				set_name VARCHAR( 30 ) NOT NULL ,
				set_value VARCHAR( 255 ) NULL ,
				cpt_timestamp TIMESTAMP NOT NULL,
				set_enum ENUM( 'stealth', 'always', 'opt-out', 'opt-in', 'lazy', 'never' ) NOT NULL DEFAULT 'never',
				PRIMARY KEY ( set_name ) )" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE IF NOT EXISTS test_1 (
				set_name VARCHAR(30) NOT  NULL,
				set_value VARCHAR(255)   NULL,
				cpt_timestamp TIMESTAMP NOT NULL,
				set_enum ENUM( 'stealth', 'always' , 'opt-out'  ,'opt-in','lazy' , 'never' ) NOT NULL DEFAULT 'never',
				PRIMARY KEY keyname(set_name) )" );

		$this->assertIdentical( $r, array(), 'Table has been detected as equal.' );
	}


	function test_db_delta_no_drop_by_default()
	{
		$this->test_DB->query( "
			CREATE TABLE IF NOT EXISTS test_1 (
				set_name VARCHAR(30) NOT  NULL,
				set_value VARCHAR(255)   NULL,
				PRIMARY KEY keyname(set_name) )" );

		$r = db_delta("
			CREATE TABLE IF NOT EXISTS test_1 (
				set_name VARCHAR(30) NOT  NULL )" );

		$this->assertIdentical( $r, array() );
	}


	/**
	 * db_delta(): Case sensitiveness of ENUM values.
	 */
	function test_db_delta_case_sensitive_enum()
	{
		$this->test_DB->query("
			CREATE TABLE test_1 (
				set_enum ENUM( 'foo', 'bar' )
				)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_1 (
				set_enum ENUM( 'Foo', 'bar' )
				)" );

		$this->assertTrue( isset( $r['test_1'] ) );
		$this->assertEqual( count($r['test_1']), 1 );
		$this->assertPattern( '~^ALTER TABLE test_1 CHANGE COLUMN set_enum set_enum ENUM\( \'Foo\', \'bar\' \)$~', $r['test_1'][0]['query'] );
	}


	/**
	 * db_delta(): Case sensitiveness of SET values.
	 */
	function test_db_delta_case_sensitive_set_varchar()
	{
		$this->test_DB->query("
			CREATE TABLE test_1 (
				set_set SET( 'foo', 'bar' ),
				set_varchar VARCHAR(255)  DEFAULT 'foobar'
				)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_1 (
				set_set SET( 'Foo', 'bar' ),
				set_varchar VARCHAR(255) DEFAULT 'foObar'
				)" );

		$this->assertTrue( isset( $r['test_1'] ) );
		$this->assertEqual( count($r['test_1']), 2 );
		$this->assertPattern( '~^ALTER TABLE test_1 CHANGE COLUMN set_set set_set SET\( \'Foo\', \'bar\' \)$~', $r['test_1'][0]['query'] );
		$this->assertPattern( '~^ALTER TABLE test_1 ALTER COLUMN set_varchar SET DEFAULT \'foObar\'$~', $r['test_1'][1]['query'] );
	}


	/**
	 * db_delta(): Case sensitiveness of SET values.
	 */
	function test_db_delta_case_sensitive_fieldnames()
	{
		$this->test_DB->query("
			CREATE TABLE test_1 (
				foo INT,
				bar INT
				)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_1 (
				Foo INT,
				Bar INT
				)" );

		$this->assertEqual( count($r), 0, 'Field names are handled case-insensitive.' );
	}


	/**
	 * db_delta(): Test if defaults get changed
	 */
	function test_db_delta_defaults()
	{
		// test changing default for ENUM field
		$this->test_DB->query("
			CREATE TABLE test_1 (
				set_enum ENUM( 'A', 'B', 'C' ) NOT NULL DEFAULT 'A'
			)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_1 (
				set_enum ENUM( 'A', 'B', 'C' ) NOT NULL DEFAULT 'B'
			)" );
		$this->assertNotIdentical( $r, array() );

		// test "implicit NULL" => DEFAULT
		$this->test_DB->query("
			CREATE TABLE test_2 (
				i INTEGER
			)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_2 (
				i INTEGER DEFAULT 1
			)" );
		$this->assertNotIdentical( $r, array() );
	}


	/**
	 * db_delta(): Tests for if "[NOT] NULL" handling.
	 */
	function test_db_delta_null()
	{
		// test "NOT NULL" => "NULL"
		$this->test_DB->query("
			CREATE TABLE test_1 (
				i INTEGER NOT NULL
			)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_1 (
				i INTEGER NULL
			)" );
		$this->assertNotIdentical( $r, array() );

		// test DEFAULT => "implicit NULL"
		$this->test_DB->query("
			CREATE TABLE test_2 (
				i INTEGER DEFAULT 1
			)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_2 (
				i INTEGER
			)" );
		$this->assertNotIdentical( $r, array() );

		// test "NOT NULL" => "implicit NULL"
		$this->test_DB->query("
			CREATE TABLE test_3 (
				i INTEGER NOT NULL
			)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_3 (
				i INTEGER
			)" );
		$this->assertNotIdentical( $r, array() );

		// test DEFAULT => "implicit NULL"
		$this->test_DB->query("
			CREATE TABLE test_4 (
				i INTEGER
			)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_4 (
				i INTEGER NOT NULL
			)" );
		$this->assertNotIdentical( $r, array() );
	}


	/**
	 * db_delta(): Tests for indices.
	 */
	function test_db_delta_indices()
	{
		// test DEFAULT => "implicit NULL"
		$this->test_DB->query("
			CREATE TABLE test_4 (
				i INTEGER
			)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_4 (
				i INTEGER,
				UNIQUE i( i )
			)" );
		$this->assertNotIdentical( $r, array() );
	}


	/**
	 * db_delta(): Check if we get our current scheme right
	 */
	function test_db_delta_currentscheme()
	{
		global $schema_queries, $basepath;

		require_once $basepath.'install/_db_schema.inc.php';

		foreach( $schema_queries as $query_info )
		{
			$this->test_DB->query( $query_info[1] );
			$r = $this->db_delta_wrapper( $query_info[1] );

			if( ! empty($r) )
			{
				pre_dump( $query_info[1], $r );
			}

			$this->assertIdentical( $r, array() );
		}
	}


	function test_change_index()
	{
		$this->test_DB->query("
			CREATE TABLE test_1 (
				i INTEGER NULL,
				PRIMARY KEY( i )
			)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_1 (
				i INTEGER,
				UNIQUE i( i )
			)" );

		$this->assertTrue( isset($r['test_1']) );
		$this->assertEqual( count($r['test_1']), 2 );
		$this->assertPattern( '~^ALTER TABLE test_1 ADD UNIQUE i\( i \)$~', $r['test_1'][0]['query'] );
		$this->assertPattern( '~^ALTER TABLE test_1 DROP PRIMARY KEY$~', $r['test_1'][1]['query'] );
	}


	/**
	 * Test handling of PRIMARY KEY when changing a field to AUTO_INCREMENT
	 */
	function test_change_to_autoincrement_add_primary_key()
	{
		$this->test_DB->query("
			CREATE TABLE test_1 (
				auto_inc INTEGER,
				i INTEGER
			)" );

		$r = $this->db_delta_wrapper("
			CREATE TABLE test_1 (
				auto_inc INTEGER AUTO_INCREMENT,
				i INTEGER,
				PRIMARY KEY( auto_inc, i )
			)" );

		$this->assertTrue( isset($r['test_1']) );
		$this->assertEqual( count($r['test_1']), 1 );
		$this->assertPattern( '~^ALTER TABLE test_1 CHANGE COLUMN auto_inc auto_inc INTEGER AUTO_INCREMENT, ADD PRIMARY KEY\( auto_inc, i \)$~', $r['test_1'][0]['query'] );
	}


	/**
	 * Test changing a field to AUTO_INCREMENT without INDEX changes
	 */
	function test_change_to_autoincrement_no_key_change()
	{
		$this->test_DB->query("
			CREATE TABLE test_1 (
				auto_inc INTEGER,
				i INTEGER,
				KEY( auto_inc, i )
			)" );

		$r = $this->db_delta_wrapper("
			CREATE TABLE test_1 (
				auto_inc INTEGER AUTO_INCREMENT,
				i INTEGER,
				KEY( auto_inc, i )
			)" );

		$this->assertTrue( isset($r['test_1']) );
		$this->assertEqual( count($r['test_1']), 1 );
		$this->assertPattern( '~^ALTER TABLE test_1 CHANGE COLUMN auto_inc auto_inc INTEGER AUTO_INCREMENT$$~', $r['test_1'][0]['query'] );
	}


	/**
	 * Test changing KEYs while adding an AUTO_INCREMENT type to a column.
	 */
	function test_change_to_autoincrement_key_change()
	{
		$this->test_DB->query("
			CREATE TABLE test_1 (
				auto_inc INTEGER,
				i INTEGER,
				PRIMARY KEY( auto_inc, i )
			)" );

		$r = $this->db_delta_wrapper("
			CREATE TABLE test_1 (
				auto_inc INTEGER AUTO_INCREMENT,
				i INTEGER,
				KEY( auto_inc, i )
			)" );

		$this->assertTrue( isset($r['test_1']) );
		$this->assertEqual( count($r['test_1']), 3 );
		$this->assertPattern( '~^ALTER TABLE test_1 CHANGE COLUMN auto_inc auto_inc INTEGER AUTO_INCREMENT$~', $r['test_1'][0]['query'] );
		$this->assertPattern( '~^ALTER TABLE test_1 ADD KEY\( auto_inc, i \)$~', $r['test_1'][1]['query'] );
		$this->assertPattern( '~^ALTER TABLE test_1 DROP PRIMARY KEY$~', $r['test_1'][2]['query'] );
	}


	/**
	 * When adding AUTO_INCREMENT columns, we have to pass the KEY already with the ALTER statement.
	 */
	function test_autoincrement_move_key()
	{
		$this->test_DB->query("
			CREATE TABLE test_1 (
				auto_inc INTEGER AUTO_INCREMENT,
				i INTEGER,
				KEY( i ),
				KEY auto ( auto_inc, i )
			)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_1 (
				auto_inc INTEGER AUTO_INCREMENT,
				i INTEGER,
				KEY auto_new ( i, auto_inc ),
				PRIMARY KEY( i )
			)" );

		$this->assertTrue( isset($r['test_1']) );
		$this->assertEqual( count($r['test_1']), 2 );
		$this->assertPattern( '~ALTER TABLE test_1 ADD KEY auto_new \( i, auto_inc \)$~', $r['test_1'][0]['query'] );
		$this->assertPattern( '~ALTER TABLE test_1 DROP INDEX auto~', $r['test_1'][1]['query'] );
	}


	function test_autoincrement_move_autoincrement_key()
	{
		$this->test_DB->query( "
			CREATE TABLE test_1 (
				test_ID int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				test_name varchar(50) NOT NULL default ''
			)" );

		$r = $this->db_delta_wrapper( "
			CREATE TABLE test_1 (
				test_ID int(11) NULL AUTO_INCREMENT,
				test_name varchar(50) NOT NULL default '',
				PRIMARY KEY ( test_ID )
			)" );

		$this->assertEqual( $r, array() );
	}


	/**
	 * Test adding and AUTO_INCREMENT field and another indices
	 *
	 * @return
	 */
	function test_add_autoincrement_and_keys()
	{
		$this->test_DB->query("
			CREATE TABLE test_1 (
				i INTEGER
			)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_1 (
				auto_inc INTEGER AUTO_INCREMENT,
				i INTEGER,
				UNIQUE i( i ),
				PRIMARY KEY( auto_inc, i )
			)" );

		$this->assertTrue( isset($r['test_1']) );
		$this->assertEqual( count($r['test_1']), 2 );
		$this->assertPattern( '~^ALTER TABLE test_1 ADD COLUMN auto_inc INTEGER AUTO_INCREMENT FIRST, ADD PRIMARY KEY\( auto_inc, i \)$~', $r['test_1'][0]['query'] );
		$this->assertPattern( '~^ALTER TABLE test_1 ADD UNIQUE i\( i \)$~', $r['test_1'][1]['query'] );
	}


	/**
	 * Test if a PRIMARY KEY gets detected when "moved" in the schema
	 */
	function test_db_delta_move_key()
	{
		$this->test_DB->query("
			CREATE TABLE test_1 (
				i INTEGER,
				PRIMARY KEY( i )
			)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_1 (
				i INTEGER KEY,
			)" );
		$this->assertEqual( $r, array() );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_1 (
				i INTEGER PRIMARY KEY,
			)" );
		$this->assertEqual( $r, array() );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_1 (
				i INTEGER,
				PRIMARY KEY( i )
			)" );
		$this->assertEqual( $r, array() );


		$this->test_DB->query("
			CREATE TABLE test_2 (
				i INTEGER PRIMARY KEY
			)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_2 (
				i INTEGER KEY,
				PRIMARY KEY( i )
			)" );
		$this->assertEqual( $r, array() );

		$this->test_DB->query("
			CREATE TABLE test_3 (
				i INTEGER PRIMARY KEY,
				i2 INTEGER
			)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_3 (
				i INTEGER,
				i2 INTEGER,
				PRIMARY KEY( i, i2 )
			)" );

		$this->assertTrue( isset($r['test_3']) );
		$this->assertEqual( count($r['test_3']), 1 );
		$this->assertPattern( '~^ALTER TABLE test_3 DROP PRIMARY KEY, ADD PRIMARY KEY\( i, i2 \)$~', $r['test_3'][0]['query'] );
	}


	/**
	 * Test that we do detect implicit "NULL" because of key change.
	 */
	function test_db_delta_implicit_null()
	{
		$this->test_DB->query( '
			CREATE TABLE test_1 (
				auto_inc INTEGER AUTO_INCREMENT,
				PRIMARY KEY ( auto_inc )
			)' );

		$r = $this->db_delta_wrapper( '
			CREATE TABLE test_1 (
				auto_inc INTEGER,
				KEY ( auto_inc )
			)' );

		$this->assertTrue( isset($r['test_1']) );
		$this->assertPattern( '~^ALTER TABLE test_1 ADD KEY \( auto_inc \)$~', $r['test_1'][0]['query'] );
		$this->assertPattern( '~^ALTER TABLE test_1 DROP PRIMARY KEY$~', $r['test_1'][1]['query'] );
	}


	/**
	 *
	 */
	function test_foobar()
	{
		$this->test_DB->query( "
			CREATE  TABLE  test_1 (
				`sess_time` int( 10  ) unsigned NOT  NULL default  '0',
				`sess_ipaddress` varchar( 15  )  collate latin1_german1_ci NOT  NULL default  '',
				`sess_user_ID` mediumint( 8  ) unsigned default NULL ,
				KEY  `start_time` (  `sess_time`  ) ,
				KEY  `remote_ip` (  `sess_ipaddress`  )  )" );

		$r = $this->db_delta_wrapper( "
			CREATE TABLE test_1 (
				sess_ID        INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				sess_key       CHAR(32) NULL,
				sess_lastseen  DATETIME NOT NULL,
				sess_ipaddress VARCHAR(15) NOT NULL DEFAULT '',
				sess_user_ID   INT(10) DEFAULT NULL,
				sess_data      TEXT DEFAULT NULL,
				PRIMARY KEY( sess_ID )
			)", NULL );
	}


	// TODO: VARCHAR(3) <=> CHAR(3)
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new UpgradeFuncsTestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
