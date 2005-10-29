<?php
/**
 * This file implements the DbUnitTestCase class, which
 * provides DB handling functions for the test DB.
 */

/**
 * The DB class for the internal DB object.
 */
require_once( EVODIR.'blogs/'.$core_subdir.'_db.class.php' );

/**
 * The base class for all unit tests that use the test DB.
 */
class DbUnitTestCase extends EvoUnitTestCase
{
	/**
	 * Constructor
	 */
	function DbUnitTestCase()
	{
		global $testDB_conf;

		$this->DB =& new DB( $testDB_conf );
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

			$this->DB->query( $lQuery );
		}
	}


	/**
	 * DROPS all tables in the test DB that might be there from previous tests.
	 */
	function dropTestDbTables()
	{
		global $EvoConfig;

		$testDbTables = array_keys($EvoConfig->DB['aliases']);

		$drop_query = 'DROP TABLE IF EXISTS '.implode( ', ', $testDbTables );

		$this->DB->query( $drop_query );
	}


	/**
	 * Create table for the current version.
	 *
	 * @return
	 */
	function create_current_tables()
	{
		require_once( EVODIR.'blogs/install/_functions_create.php' );

		$this->dropTestDbTables();

		create_b2evo_tables();
	}


	/**
	 * Creates the default tables for b2evolution 0.9.0.11.
	 */
	function createTablesFor0_9_0_11()
	{
		$this->executeQueriesFromFile( TESTSDIR.'install/sql/b2evolution_0_9_0_11.default.sql' );
	}
}

?>
