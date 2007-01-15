<?php
/**
 * This file implements the DbUnitTestCase class, which
 * provides DB handling functions for the test DB.
 */


/**
 * We use create_tables() in {@link create_current_tables()}.
 */
require_once $basepath.$install_subdir.'_functions_create.php';


/**
 * The base class for all unit tests that use the test DB.
 *
 * It sets the global $DB to the test-DB object and creates a fresh installation in {@link setUp()}.
 */
class DbUnitTestCase extends EvoUnitTestCase
{
	/**
	 * A database object connected to the test DB.
	 * @var DB
	 * @see $testDB_conf
	 */
	var $test_DB;


	/**
	 * Setup global $DB as reference to member test_DB (test DB).
	 */
	function setUp()
	{
		global $testDB_conf;

		if( !isset( $testDB_conf['name'] ) )
		{
			die( 'Please set the DB name to use for tests in /tests/config.php or /tests/config.OVERRIDE.php. See $testDB_conf there..' );
		}

		$this->test_DB = new DbUnitTestCase_DB( $testDB_conf );
		$this->test_DB->halt_on_error = false;


		parent::setUp();


		$this->old_DB = & $GLOBALS['DB'];
		$GLOBALS['DB'] = & $this->test_DB;

		// reset any error (and catch it in tearDown())
		$this->test_DB->error = false;

		$this->test_DB->begin();
	}


	/**
	 * Setup global $DB as original DB object again.
	 */
	function tearDown()
	{
		$this->test_DB->commit();

		if( $this->test_DB->error )
		{
			$this->fail( 'There has been a DB error.' );
		}

		$GLOBALS['DB'] = & $this->old_DB;

		parent::tearDown();
	}


	/**
	 * Reads a file (SQL dump), splits the several queries and executes them.
	 */
	function executeQueriesFromFile( $filename )
	{
		$buffer = file_get_contents( $filename );

		foreach( preg_split( '#;(\r?\n|\r)#', $buffer, -1 ) as $lQuery )
		{
			if( !($lQuery = trim($lQuery)) )
			{ // no empty queries
				continue;
			}

			$this->test_DB->query( $lQuery );
		}
	}


	/**
	 * DROPS all tables in the test DB that might be there from previous tests.
	 */
	function dropTestDbTables()
	{
		global $db_config;

		$testDbTables = array_keys($db_config['aliases']);

		if( $test_tables = $this->test_DB->get_col( 'SHOW TABLES LIKE "test%"' ) )
		{
			$testDbTables = array_merge( $testDbTables, $test_tables );
		}
		if( $test_tables = $this->test_DB->get_col( 'SHOW TABLES LIKE "b2%"' ) )
		{
			$testDbTables = array_merge( $testDbTables, $test_tables );
		}

		$old_fk_check = $this->test_DB->get_var( 'SELECT @@FOREIGN_KEY_CHECKS' );
		$this->test_DB->query( 'SET FOREIGN_KEY_CHECKS = 0;' );

		$drop_query = 'DROP TABLE IF EXISTS '.implode( ', ', $testDbTables );
		$this->test_DB->query( $drop_query );

		if( ! empty($old_fk_check) )
		{
			$this->test_DB->query( 'SET FOREIGN_KEY_CHECKS = '.$old_fk_check.';' );
		}
	}


	/**
	 * Create table for the current version.
	 */
	function create_current_tables()
	{
		$this->dropTestDbTables();

		create_tables();
	}


	/**
	 * Creates the default tables for b2evolution 0.9.0.11.
	 */
	function createTablesFor0_9_0_11()
	{
		$this->executeQueriesFromFile( TESTSDIR.'install/sql/b2evolution_0_9_0_11.default.sql' );
	}


	/**
	 * Creates the default tables for b2evolution 1.6.
	 */
	function createTablesFor1_6()
	{
		$this->executeQueriesFromFile( TESTSDIR.'install/sql/b2evolution_v-1-6.default.sql' );
	}


	/**
	 * Creates the default tables for b2evolution 1.8.
	 */
	function createTablesFor1_8()
	{
		$this->executeQueriesFromFile( TESTSDIR.'install/sql/b2evolution_v-1-8.default.sql' );
	}
}


/**
 * Extends {@link DB} class to output backtraces in case of error
 */
class DbUnitTestCase_DB extends DB
{
	/**
	 * Append a backtrace to any query errors.
	 */
	function print_error( $str = '', $query_title = '' )
	{
		parent::print_error($str, $query_title);

		echo debug_get_backtrace(NULL, array( 'function' => 'print_error' ));
	}
}

?>