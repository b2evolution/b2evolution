<?php

/**
 * The base class for all /install tests.
 */
class InstallUnitTestCase extends EvoUnitTestCase
{
	/**
	 * Constructor
	 */
	function InstallUnitTestCase()
	{
		global $testDB_conf, $db_aliases;

		$this->DB =& new DB( $testDB_conf['DB_USER'],
													$testDB_conf['DB_PASSWORD'],
													$testDB_conf['DB_NAME'],
													$testDB_conf['DB_HOST'],
													$db_aliases,
													$testDB_conf['db_table_options'],
													true // halt_on_error
												);
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
	 * DROPS all tables in the test DB that might be there.
	 */
	function dropTestDbTables()
	{
		$testDbTables = array( 'T_antispam', 'T_hitlog', 'T_comments', 'T_postcats',
														'T_links', 'T_files', 'T_posts', 'T_categories',
														'T_poststatuses', 'T_posttypes', 'T_usersettings',
														'T_sessions', 'T_blogusers', 'T_users', 'T_groups',
														'T_blogs', 'T_settings', 'T_locales', 'T_usersettings',
														'T_plugins' );

		foreach( $testDbTables as $lTableName )
		{
			$this->DB->query( 'DROP TABLE IF EXISTS '.$lTableName );
		}
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
