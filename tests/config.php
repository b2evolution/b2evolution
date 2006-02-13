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


// Load b2evo config:
/**
 * TODO: not sure, if we should really load everything. We'd need at least
 *       to define EVO_MAIN_INIT to load single class files, ...
 */
define( 'EVO_MAIN_INIT', 'SIMPLETEST' );
require_once( EVODIR.'blogs/conf/_config.php' );
require_once( EVODIR.'blogs/evocore/_misc.funcs.php' );
require_once( EVODIR.'blogs/evocore/_blog.funcs.php' );
require_once( EVODIR.'blogs/evocore/_category.funcs.php' );
require_once( EVODIR.'blogs/evocore/_item.funcs.php' );
require_once( EVODIR.'blogs/evocore/_dataobject.class.php' );
require_once( EVODIR.'blogs/evocore/_log.class.php' );

require_once( EVODIR.'blogs/evocore/_abstractsettings.class.php' );
require_once( EVODIR.'blogs/evocore/_generalsettings.class.php' );

require_once( EVODIR.'blogs/evocore/_filecache.class.php' );
require_once( EVODIR.'blogs/evocore/_item.class.php' );
require_once( EVODIR.'blogs/evocore/_fileroot.class.php' );
require_once( EVODIR.'blogs/evocore/_filerootcache.class.php' );
require_once( EVODIR.'blogs/evocore/_filetype.class.php' );
require_once( EVODIR.'blogs/evocore/_filetypecache.class.php' );

require_once( EVODIR.'blogs/evocore/_usercache.class.php' );
require_once( EVODIR.'blogs/evocore/_user.class.php' );
require_once( EVODIR.'blogs/evocore/_group.class.php' );
require_once( EVODIR.'blogs/evocore/_results.class.php' );
require_once( EVODIR.'blogs/evocore/_timer.class.php' );

require_once( EVODIR.'blogs/evocore/_db.class.php' );

require_once( EVODIR.'blogs/evocore/_plugins.class.php' );

#require_once( EVODIR.'blogs/evocore/_main.inc.php' );


/**
 * MySQL settings for the tests.
 *
 * WARNING: Tables in this DB that are used for the tests will be
 *          dropped during the tests.
 *          BE SURE to use a test DB here.
 *
 * These settings override the defaults from {@link $EvoConfig->DB}
 *
 * This is used to create {@link DbUnitTestCase::test_DB the test DB object}
 * in the class {@link DbUnitTestCase}, which gets used for tests that
 * need a real database connection.
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
		'new_link' => true,            // Create a new link to the DB! This is required to not interfere with the normal connection (used DB).
	),
	$testDB_conf );

// Use default aliases, if not set
$testDB_conf['aliases'] = array_merge( $EvoConfig->DB['aliases'], isset($testDB_conf['aliases']) ? $testDB_conf['aliases'] : array() );
?>
