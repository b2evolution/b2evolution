<?php
/**
 * Tests for the upgrade functions, mailny {@link db_delta()}.
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
		$r = db_delta("
			CREATE TABLE IF NOT EXISTS test_1 (
				set_name VARCHAR(30) NOT  NULL,
				set_value VARCHAR(255)   NULL,
				cpt_timestamp TIMESTAMP NOT NULL,
				set_enum ENUM( 'stealth', 'always' , 'opt-out'  ,'opt-in','lazy' , 'never' ) NOT NULL DEFAULT 'never',
				PRIMARY KEY keyname(set_name) )" );

		$this->assertIdentical( $r, array(), 'Table has been detected as equal.' );

		$r = db_delta("
			CREATE TABLE IF NOT EXISTS test_1 (
				set_name VARCHAR(30) NOT  NULL,
				set_value VARCHAR(255)   NULL,
				PRIMARY KEY keyname(set_name) )" );
		$this->assertIdentical( $r, array() );

	}


	/**
	 * db_delta(): Case sensitiveness of values.
	 */
	function test_db_delta_case_sensitiveness()
	{
		$this->test_DB->query("
			CREATE TABLE test_1 (
				set_enum ENUM( 'foo', 'bar' )
				)" );
		$r = db_delta("
			CREATE TABLE test_1 (
				set_enum ENUM( 'Foo', 'bar' )
				)" );

		$this->assertIdentical( count($r), 1 );
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
		$r = db_delta("
			CREATE TABLE test_1 (
				set_enum ENUM( 'A', 'B', 'C' ) NOT NULL DEFAULT 'B'
			)" );
		$this->assertNotIdentical( $r, array() );

		// test "implicit NULL" => DEFAULT
		$this->test_DB->query("
			CREATE TABLE test_2 (
				i INTEGER
			)" );
		$r = db_delta("
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
		$r = db_delta("
			CREATE TABLE test_1 (
				i INTEGER NULL
			)" );
		$this->assertNotIdentical( $r, array() );

		// test DEFAULT => "implicit NULL"
		$this->test_DB->query("
			CREATE TABLE test_2 (
				i INTEGER DEFAULT 1
			)" );
		$r = db_delta("
			CREATE TABLE test_2 (
				i INTEGER
			)" );
		$this->assertNotIdentical( $r, array() );

		// test "NOT NULL" => "implicit NULL"
		$this->test_DB->query("
			CREATE TABLE test_3 (
				i INTEGER NOT NULL
			)" );
		$r = db_delta("
			CREATE TABLE test_4 (
				i INTEGER
			)" );
		$this->assertNotIdentical( $r, array() );

		// test DEFAULT => "implicit NULL"
		$this->test_DB->query("
			CREATE TABLE test_4 (
				i INTEGER
			)" );
		$r = db_delta("
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
		$r = db_delta("
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
			$r = db_delta( $query_info[1] );

			if( ! empty($r) )
			{
				pre_dump( $query_info[1], $r );
			}

			$this->assertIdentical( $r, array() );
		}
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new UpgradeFuncsTestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
