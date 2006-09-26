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


	/**
	 * A wrapper to always execute the generated queries (check for SQL errors) and
	 * do not exclude any query types.
	 *
	 * @return array Generated queries, see {@link db_delta}.
	 */
	function db_delta_wrapper( $queries, $exclude = array() )
	{
		$old_error = $this->test_DB->error;

		$this->test_DB->error = false; // reset any error

		$r = db_delta( $queries, $exclude, true );

		if( $this->test_DB->error )
		{
			pre_dump( 'db_delta failed!', $queries, $r );
		}

		$this->test_DB->error = ( $old_error || $this->test_DB->error );

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


	/**
	 * Test, if all query types (including DROPs get returned)
	 */
	function test_db_delta_drop_by_default()
	{
		$this->test_DB->query( "
			CREATE TABLE IF NOT EXISTS test_1 (
				set_name VARCHAR(30) NOT  NULL,
				set_value VARCHAR(255)   NULL,
				PRIMARY KEY keyname(set_name) )" );

		$r = db_delta("
			CREATE TABLE IF NOT EXISTS test_1 (
				set_name VARCHAR(30) NOT  NULL )" );

		$this->assertIdentical( 'ALTER TABLE test_1 DROP COLUMN set_value', $r['test_1'][0]['queries'][0] );
		$this->assertIdentical( 'ALTER TABLE test_1 DROP PRIMARY KEY', $r['test_1'][1]['queries'][0] );
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
		$this->assertPattern( '~^ALTER TABLE test_1 CHANGE COLUMN set_enum set_enum ENUM\( \'Foo\', \'bar\' \)$~', $r['test_1'][0]['queries'][0] );
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
		$this->assertPattern( '~^ALTER TABLE test_1 CHANGE COLUMN set_set set_set SET\( \'Foo\', \'bar\' \)$~', $r['test_1'][0]['queries'][0] );
		$this->assertPattern( '~^ALTER TABLE test_1 ALTER COLUMN set_varchar SET DEFAULT \'foObar\'$~', $r['test_1'][1]['queries'][0] );
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
		$this->assertPattern( '~^ALTER TABLE test_1 ADD UNIQUE i\( i \)$~', $r['test_1'][0]['queries'][0] );
		$this->assertPattern( '~^ALTER TABLE test_1 DROP PRIMARY KEY$~', $r['test_1'][1]['queries'][0] );
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
		$this->assertEqual( 'ALTER TABLE test_1 CHANGE COLUMN auto_inc auto_inc INTEGER AUTO_INCREMENT, ADD PRIMARY KEY( auto_inc, i )', $r['test_1'][0]['queries'][0] );
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
		$this->assertEqual( 'ALTER TABLE test_1 CHANGE COLUMN auto_inc auto_inc INTEGER AUTO_INCREMENT', $r['test_1'][0]['queries'][0] );
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
		$this->assertEqual( 'ALTER TABLE test_1 CHANGE COLUMN auto_inc auto_inc INTEGER AUTO_INCREMENT', $r['test_1'][0]['queries'][0] );
		$this->assertEqual( 'ALTER TABLE test_1 ADD KEY( auto_inc, i )', $r['test_1'][1]['queries'][0] );
		$this->assertEqual( 'ALTER TABLE test_1 DROP PRIMARY KEY', $r['test_1'][2]['queries'][0] );
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
				KEY auto ( auto_inc, i )
			)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_1 (
				auto_inc INTEGER AUTO_INCREMENT,
				i INTEGER,
				KEY auto_new ( auto_inc )
			)" );

		$this->assertTrue( isset($r['test_1']) );
		$this->assertEqual( count($r['test_1']), 2 );
		$this->assertEqual( 'ALTER TABLE test_1 ADD KEY auto_new ( auto_inc )', $r['test_1'][0]['queries'][0] );
		$this->assertEqual( 'ALTER TABLE test_1 DROP INDEX auto', $r['test_1'][1]['queries'][0] );
	}

	/**
	 * Test if a (non-primary) KEY gets transfered to a PRIMARY KEY.
	 */
	function test_db_delta_move_KEY_to_PK()
	{
		$this->test_DB->query("
			CREATE TABLE test_1 (
				i INTEGER,
				KEY i ( i )
			)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_1 (
				i INTEGER,
				PRIMARY KEY i ( i )
			)" );

		$this->assertTrue( isset($r['test_1']) );
		$this->assertEqual( count($r['test_1']), 2 );
		$this->assertEqual( 'ALTER TABLE test_1 ADD PRIMARY KEY i ( i )', $r['test_1'][0]['queries'][0] );
		$this->assertEqual( 'ALTER TABLE test_1 DROP INDEX i', $r['test_1'][1]['queries'][0] );
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
		$this->assertPattern( '~^ALTER TABLE test_1 ADD COLUMN auto_inc INTEGER AUTO_INCREMENT FIRST, ADD PRIMARY KEY\( auto_inc, i \)$~', $r['test_1'][0]['queries'][0] );
		$this->assertPattern( '~^ALTER TABLE test_1 ADD UNIQUE i\( i \)$~', $r['test_1'][1]['queries'][0] );
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
		$this->assertPattern( '~^ALTER TABLE test_3 DROP PRIMARY KEY, ADD PRIMARY KEY\( i, i2 \)$~', $r['test_3'][0]['queries'][0] );
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
		$this->assertPattern( '~^ALTER TABLE test_1 ADD KEY \( auto_inc \)$~', $r['test_1'][0]['queries'][0] );
		$this->assertPattern( '~^ALTER TABLE test_1 DROP PRIMARY KEY$~', $r['test_1'][1]['queries'][0] );
	}


	/**
	 * VARCHAR shorter then 4 characters get silently converted to CHAR by MySQL.
	 */
	function test_db_delta_varchar_shorter_than_4()
	{
		$this->test_DB->query( '
			CREATE TABLE test_1 (
				v VARCHAR(2)
			)' );

		$r = $this->db_delta_wrapper( '
			CREATE TABLE test_1 (
				v VARCHAR(2)
			)' );

		$this->assertEqual( $r, array() );
	}


	/**
	 * If a row contains any variable length column, all CHAR fields become VARCHAR fields.
	 */
	function test_db_delta_varchar_to_char_if_any_varlength_field()
	{
		$this->test_DB->query( '
			CREATE TABLE test_1 (
				v VARCHAR(22),
				c VARCHAR(22),
				c2 CHAR(2)
			)' );

		$r = $this->db_delta_wrapper( '
			CREATE TABLE test_1 (
				v VARCHAR(22),
				c CHAR(22),
				c2 VARCHAR(2)
			)' );

		$this->assertEqual( $r, array() );
	}


	function test_db_delta_varchar_to_char_change_length()
	{
		$this->test_DB->query( '
			CREATE TABLE test_1 (
				v VARCHAR(20)
			)' );

		$r = $this->db_delta_wrapper( '
			CREATE TABLE test_1 (
				v CHAR(20)
			)' );

		$this->assertEqual( count($r), 1 );
		$this->assertEqual( count($r['test_1']), 1 );
		$this->assertEqual( $r['test_1'][0]['queries'][0], 'ALTER TABLE test_1 CHANGE COLUMN v v CHAR(20)' );
	}


	function test_db_delta_change_field_and_primary()
	{
		$this->test_DB->query("
			CREATE TABLE test_1 (
				i INTEGER,
				PRIMARY KEY( i )
			)" );
		$r = $this->db_delta_wrapper("
			CREATE TABLE test_1 (
				i SMALLINT KEY
			)" );

		$this->assertEqual( count($r), 1 );
		$this->assertEqual( count($r['test_1']), 1 );
		$this->assertEqual( 'ALTER TABLE test_1 DROP PRIMARY KEY, CHANGE COLUMN i i SMALLINT KEY', $r['test_1'][0]['queries'][0] );
	}


	function test_db_delta_no_drop_primary_if_not_changed()
	{
		$this->test_DB->query( "
			CREATE TABLE test_1 (
				i INT( 10 ) NOT NULL DEFAULT '0',
				v VARCHAR( 30 ) COLLATE latin1_german1_ci NOT NULL DEFAULT '',
				PRIMARY KEY ( i, v )
			)" );

		$r = $this->db_delta_wrapper( "
			CREATE TABLE test_1 (
				i INT(11) UNSIGNED NOT NULL,
				v VARCHAR( 30 ) NOT NULL,
				PRIMARY KEY ( i, v )
			)" );

		$this->assertEqual( count($r), 1 );
		$this->assertEqual( count($r['test_1']), 1 );
		$this->assertEqual( 'ALTER TABLE test_1 CHANGE COLUMN i i INT(11) UNSIGNED NOT NULL', $r['test_1'][0]['queries'][0] );
	}


	function test_db_delta_handle_not_null_change_add_default()
	{
		$this->test_DB->query( "
			CREATE TABLE test_1 (
				v VARCHAR(255) NULL DEFAULT '0'
			)" );
		$this->test_DB->query( "
			INSERT INTO test_1 VALUES (NULL);
			" );

		$r = $this->db_delta_wrapper( "
			CREATE TABLE test_1 (
				v VARCHAR(255) NOT NULL DEFAULT '0'
			)" );

		$this->assertEqual( count($r), 1 );
		$this->assertEqual( count($r['test_1']), 1 );
		$this->assertEqual( $r['test_1'][0]['queries'],
			array( 'UPDATE test_1 SET v = \'0\' WHERE v IS NULL',
				'ALTER TABLE test_1 CHANGE COLUMN v v VARCHAR(255) NOT NULL DEFAULT \'0\'' ) );
	}


	function test_db_delta_handle_not_null_change_add_implicit_default()
	{
		$this->test_DB->query( "
			CREATE TABLE test_1 (
				i INT NULL DEFAULT '0'
			)" );
		$this->test_DB->query( "
			INSERT INTO test_1 VALUES (NULL);
			" );

		$r = $this->db_delta_wrapper( "
			CREATE TABLE test_1 (
				i INT NOT NULL
			)" );

		$this->assertEqual( count($r), 1 );
		$this->assertEqual( count($r['test_1']), 1 );
		$this->assertEqual( $r['test_1'][0]['queries'],
			array( 'UPDATE test_1 SET i = 0 WHERE i IS NULL',
				'ALTER TABLE test_1 CHANGE COLUMN i i INT NOT NULL' ) );
	}


	function test_db_delta_handle_not_null_change_add_implicit_default_enum()
	{
		$this->test_DB->query( "
			CREATE TABLE test_1 (
				e ENUM('a','b') NULL DEFAULT 'b'
			)" );
		$this->test_DB->query( "
			INSERT INTO test_1 VALUES (NULL);
			" );

		$r = $this->db_delta_wrapper( "
			CREATE TABLE test_1 (
				e ENUM( 'a', 'b' ) NOT NULL
			)" );

		$this->assertEqual( count($r), 1 );
		$this->assertEqual( count($r['test_1']), 1 );
		$this->assertEqual( $r['test_1'][0]['queries'],
			array( 'UPDATE test_1 SET e = \'a\' WHERE e IS NULL',
				'ALTER TABLE test_1 CHANGE COLUMN e e ENUM( \'a\', \'b\' ) NOT NULL' ) );
	}


	function test_db_delta_handle_not_null_change_add_implicit_default_enum_change()
	{
		$this->test_DB->query( "
			CREATE TABLE test_1 (
				e ENUM('a','b') NULL DEFAULT 'b'
			)" );
		$this->test_DB->query( "
			INSERT INTO test_1 VALUES (NULL);
			" );

		$r = $this->db_delta_wrapper( "
			CREATE TABLE test_1 (
				e ENUM( 'a_new', 'b' ) NOT NULL
			)" );

		$this->assertEqual( count($r), 1 );
		$this->assertEqual( count($r['test_1']), 1 );
		$this->assertEqual( $r['test_1'][0]['queries'],
			array( 'ALTER TABLE test_1 CHANGE COLUMN e e ENUM( \'a_new\', \'b\' )',
				'UPDATE test_1 SET e = \'a_new\' WHERE e IS NULL',
				'ALTER TABLE test_1 CHANGE COLUMN e e ENUM( \'a_new\', \'b\' ) NOT NULL' ) );
	}


	/**
	 * Test if the itemlist returned by db_delta() is ordered (0, 1, 2, ..)
	 */
	function test_db_delta_ordered_itemlist()
	{
		$this->test_DB->query( "
			CREATE TABLE test_1 (
				i INTEGER
			)" );

		$r = $this->db_delta_wrapper( "
			CREATE TABLE test_1 (
				i VARCHAR(32),
				i2 INT,
				PRIMARY KEY( i )
			)", /* exclude type: */ array('add_column') );

		$this->assertEqual( count($r), 1 );
		$this->assertEqual( count($r['test_1']), 2 );
		$this->assertTrue( isset( $r['test_1'][1] ) );
	}


	/**
	 * Test if with "inline PK" it also gets dropped correctly.
	 */
	function test_db_delta_change_PK_inline()
	{
		$this->test_DB->query( "
			CREATE TABLE test_1 (
				i INTEGER PRIMARY KEY
			)" );

		$r = $this->db_delta_wrapper( "
			CREATE TABLE test_1 (
				i2 INTEGER PRIMARY KEY
			)", /* exclude defaults: */ array('drop_column', 'drop_index') );

		$this->assertEqual( count($r), 1 );
		$this->assertEqual( count($r['test_1']), 1 );
		$this->assertEqual( $r['test_1'][0]['queries'][0], 'ALTER TABLE test_1 ADD COLUMN i2 INTEGER PRIMARY KEY FIRST, DROP PRIMARY KEY' );
	}


	/**
	 * Test if with "inline PK" it also gets dropped correctly.
	 */
	function test_db_delta_change_PK_inline_two()
	{
		$this->test_DB->query( "
			CREATE TABLE test_1 (
				i INTEGER,
				i2 INTEGER,
				PRIMARY KEY test( i, i2 )
			)" );

		$r = $this->db_delta_wrapper( "
			CREATE TABLE test_1 (
				i3 INTEGER PRIMARY KEY
			)", /* exclude defaults: */ array('drop_column', 'drop_index') );

		$this->assertEqual( count($r), 1 );
		$this->assertEqual( count($r['test_1']), 1 );
		$this->assertEqual( $r['test_1'][0]['queries'][0], 'ALTER TABLE test_1 ADD COLUMN i3 INTEGER PRIMARY KEY FIRST, DROP PRIMARY KEY' );
	}


	/**
	 * Test if with "inline PK" it also gets dropped correctly.
	 */
	function test_db_delta_change_PK_inline_auto()
	{
		$this->test_DB->query( "
			CREATE TABLE test_1 (
				i INTEGER PRIMARY KEY AUTO_INCREMENT,
				dummy INT
			)" );

		$r = $this->db_delta_wrapper( "
			CREATE TABLE test_1 (
				i2 INTEGER PRIMARY KEY,
				dummy INT
			)", /* exclude defaults: */ array('drop_column', 'drop_index') );

		$this->assertEqual( count($r), 1 );
		$this->assertEqual( count($r['test_1']), 1 );
		$this->assertEqual( $r['test_1'][0]['queries'][0], 'ALTER TABLE test_1 ADD COLUMN i2 INTEGER PRIMARY KEY FIRST, DROP PRIMARY KEY, MODIFY COLUMN i int(11) NOT NULL' );

		$this->test_DB->query( "
			CREATE TABLE test_2 (
				i INTEGER PRIMARY KEY AUTO_INCREMENT,
				dummy INT
			)" );

		$r = $this->db_delta_wrapper( "
			CREATE TABLE test_2 (
				i2 INTEGER PRIMARY KEY,
				dummy INT
			)", /* no exclude: */ array() );
		$this->assertEqual( count($r), 1 );
		$this->assertEqual( count($r['test_2']), 2 );
		$this->assertEqual( $r['test_2'][0]['queries'][0], 'ALTER TABLE test_2 DROP COLUMN i' );
		$this->assertEqual( $r['test_2'][1]['queries'][0], 'ALTER TABLE test_2 ADD COLUMN i2 INTEGER PRIMARY KEY FIRST' );
	}


	function test_db_delta_handle_PK_with_col_change()
	{
		$this->test_DB->query( "
			CREATE TABLE test_1 (
				v VARCHAR(32) PRIMARY KEY
			)" );

		$r = $this->db_delta_wrapper( "
			CREATE TABLE test_1 (
				v VARCHAR(33) PRIMARY KEY
			)", /* exclude defaults: */ array('drop_column', 'drop_index') );

		$this->assertEqual( count($r), 1 );
		$this->assertEqual( count($r['test_1']), 1 );
		$this->assertEqual( 'ALTER TABLE test_1 DROP PRIMARY KEY, CHANGE COLUMN v v VARCHAR(33) PRIMARY KEY', $r['test_1'][0]['queries'][0] );
	}


	function test_db_delta_ignore_length_param() // for "numbers"
	{
		$this->test_DB->query( "
			CREATE TABLE test_1 (
				t1 TINYINT(3),
				t2 TINYINT
			)" );

		$r = $this->db_delta_wrapper( "
			CREATE TABLE test_1 (
				t1 TINYINT,
				t2 TINYINT(2)
			)", /* exclude defaults: */ array('drop_column', 'drop_index') );

		$this->assertEqual( count($r), 0 );
	}


}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new UpgradeFuncsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
