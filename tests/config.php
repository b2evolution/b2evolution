<?php
/**
 * Config file for tests.
 */

/**
 * Create it locally and define EVODIR or other constants there.
 */
@include_once( dirname(__FILE__).'/config.OVERRIDE.php' );


if( !defined( 'TESTSDIR' ) )
{
	/**
	* The tests root directory.
	 */
	define( 'TESTSDIR', dirname(__FILE__).'/' );
}
if( !defined( 'EVODIR' ) )
{
	/**
	* The evo directory (where /blogs is).
	 */
	define( 'EVODIR', dirname(__FILE__).'/../' );
}
if( !defined( 'TMPDIR' ) )
{
	/**
	 * A temp directory where we can create temporary files.
	 */
	define( 'TMPDIR', dirname(__FILE__).'/temp/' );
}



/**
 * MySQL settings for the tests.
 *
 * WARNING: Tables in this DB that are used for the tests will be
 *          dropped during the tests.
 *          BE SURE to use a test DB here.
 *
 * @global array
 */
$testDB_conf = array(
	'DB_USER' => 'demouser',         // your MySQL username
	'DB_PASSWORD' => 'demopass',     // ...and password
	'DB_NAME' => 'b2evolution_test', // the name of the database
	'DB_HOST' => 'localhost',        // MySQL Server (typically 'localhost')

	'db_table_options' => '',
	// Recommended settings:
	# 'db_table_options' => ' ENGINE=InnoDB ',
	// Development settings:
	# 'db_table_options' => ' ENGINE=InnoDB DEFAULT CHARSET=utf8 ',
);


if( !isset( $testDB_conf['DB_NAME'] ) )
{
	die( 'Please set the DB name to use for tests in '.__FILE__.'..' );
}

?>
