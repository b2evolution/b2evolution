<?php
/**
 * Config file for SimpleTest tests
 */

/**
 * Create it locally and define SIMPLETEST_DIR or other constants there.
 */
@include_once( dirname(__FILE__).'/config.simpletest.OVERRIDE.php' );

/**
 * The global config file for all tests.
 */
require_once( dirname(__FILE__).'/config.php' );


if( !defined( 'SIMPLETEST_DIR' ) )
{
	/**
	 * The SimpleTest directory.
	 */
	define( 'SIMPLETEST_DIR', TESTSDIR.'simpletest/' );
}


// Load b2evo config:
/**
 * TODO: not sure, if we should really load everything. We'd need at least
 *       to define EVO_MAIN_INIT to load single class files, ...
 */
#define( 'EVO_MAIN_INIT', 'SIMPLETEST' );
#require_once( EVODIR.'blogs/conf/_config.php' );
require_once( EVODIR.'blogs/evocore/_main.inc.php' );

$testDB_conf = array_merge( $EvoConfig->DB, $testDB_conf );


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
require_once( dirname(__FILE__).'/classes/simpletest/EvoUnitTestCase.class.php' );
require_once( dirname(__FILE__).'/classes/simpletest/FilemanUnitTestCase.class.php' );
require_once( dirname(__FILE__).'/classes/simpletest/EvoGroupTest.class.php' );
require_once( dirname(__FILE__).'/classes/simpletest/InstallUnitTestCase.class.php' );
/**#@-*/



/**
 * Create a DB Mockobject
 */
require_once( EVODIR.'/blogs/evocore/_db.class.php' );
Mock::generate( 'DB' );

?>
