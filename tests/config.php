<?php
/**
 * Config file for tests.
 */

/**
 * Create it locally and define EVODIR or other constants there.
 * You can also override array indexes of {@link $testDB_conf} there.
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
 * These settings override the defaults from {@link $EvoConfig->DB}
 *
 * This is used to create a {@link $DB DB object} in the class {@link DbUnitTestCase},
 * which gets used for tests that need a real database connection.
 *
 * @global array $testDB_conf
 */

if( !isset($testDB_conf) || !is_array($testDB_conf) )
{
	$testDB_conf = array();
}

$testDB_conf = array_merge( array(
		'user' => 'demouser',          // your MySQL username
		'password' => 'demopass',      // ...and password
		#'name' => 'b2evolution_tests', // the name of the database
		'host' => 'localhost',         // MySQL Server (typically 'localhost')

		'table_options' => '',
		// Recommended settings:
		# 'table_options' => ' ENGINE=InnoDB ',
		// Development settings:
		# 'table_options' => ' ENGINE=InnoDB DEFAULT CHARSET=utf8 ',
	),
	$testDB_conf );


if( !isset( $testDB_conf['name'] ) )
{
	die( 'Please set the DB name to use for tests in '.__FILE__.'. See $testDB_conf there..' );
}

?>
