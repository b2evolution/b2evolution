<?php
/**
 * Config file for SimpleTest tests
 */

/**
 * Create it locally and define SIMPLETEST_DIR or other constants.
 */
@include_once( dirname(__FILE__).'/config.simpletest.OVERRIDE.php' );

if( !defined( 'SIMPLETEST_DIR' ) )
{
	/**
	 * The SimpleTest directory.
	 */
	define( 'SIMPLETEST_DIR', dirname(__FILE__).'/simpletest/' );
}
if( !defined( 'EVODIR' ) )
{
	/**
	 * The evo directory.
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
 * TODO: not sure, if we should really load everything. We'd need at least
 *       to define DB_USER to load single class files, ...
 */
#define( 'DB_USER', 'SIMPLETEST' );
#require_once( dirname(__FILE__).'/../blogs/conf/_config.php' );
require_once( dirname(__FILE__).'/../blogs/evocore/_main.inc.php' );


if( !file_exists( SIMPLETEST_DIR.'unit_tester.php' ) )
{
	die( 'SimpleTest framework not found: File '.SIMPLETEST_DIR.'unit_tester.php does not exist.' );
}


/**
 * The SimpleTest UnitTestCase
 */
require_once( SIMPLETEST_DIR.'unit_tester.php' );
/**
 * Mockobject factory
 */
require_once( SIMPLETEST_DIR.'mock_objects.php');


/**#@+
 * Load derived SimpleTest classes
 */
require_once( dirname(__FILE__).'/classes/simpletest/FilemanUnitTestCase.class.php' );
require_once( dirname(__FILE__).'/classes/simpletest/OurGroupTest.class.php' );
/**#@-*/

?>
