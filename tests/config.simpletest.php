<?php
/**
 * Config file for SimpleTest tests
 * @package tests
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
	if( is_dir( TESTSDIR.'simpletest/symlink' ) )
	{
		/**
		 * The SimpleTest directory.
		 */
		define( 'SIMPLETEST_DIR', TESTSDIR.'simpletest/symlink/' );
	}
	else
	{
		/**
		 * The SimpleTest directory.
		 */
		define( 'SIMPLETEST_DIR', TESTSDIR.'simpletest/' );
	}
}


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
Mock::generate( 'DB' );

?>
