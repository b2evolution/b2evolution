<?php
/**
 * Config file for tests.
 */

set_time_limit(0);

/**
 * Create it locally and define EVODIR or other constants there.
 * You can also override array indexes of {@link $testDB_conf} there.
 */
if( file_exists(dirname(__FILE__).'/config.OVERRIDE.php') )
{
	include_once( dirname(__FILE__).'/config.OVERRIDE.php' );
}


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
 * @global string Prefix for test tables.
 */
$evo_tests_tableprefix = 'evo_tests_';


// Load b2evo config:
/**
 * TODO: not sure, if we should really load everything. We'd need at least
 *       to define EVO_MAIN_INIT to load single class files, ...
 */
define( 'EVO_MAIN_INIT', 'SIMPLETEST' );
require_once EVODIR.'blogs/conf/_config.php';

require_once $inc_path.'_core/_class5.funcs.php';
require_once $inc_path.'_core/_misc.funcs.php';
require_once $inc_path.'locales/_locale.funcs.php';

// Load vars, e.g. $is_cli
// TODO: this should get done in EvoUnitTestCase::setup (clean state), but needs global massaging.
load_class('_core/model/_log.class.php', 'Log_noop');
$Debuglog = new Log_noop;

require_once $inc_path.'_main.inc.php';

// Bootstrap charset handling (e.g. setting $io_charset)
init_charsets('utf-8');


load_class( '_core/model/_module.class.php', 'Module' );
foreach( $modules as $module )
{
        require_once $inc_path.$module.'/_'.$module.'.init.php';
}


/**
 * MySQL settings for the tests.
 *
 * These settings override the defaults from {@link $db_config}
 *
 * This is used to create {@link EvoDbUnitTestCase::test_DB the test DB object}
 * in the class {@link EvoDbUnitTestCase}, which gets used for tests that
 * need a real database connection.
 *
 * @global array $testDB_conf
 */

if( !isset($testDB_conf) || !is_array($testDB_conf) )
{
	$testDB_conf = array(
		'log_queries' => false, // performance
		);
}


// Use $db_config, with overrides from $testDB_conf
$testDB_conf = array_merge( $db_config, $testDB_conf );


// Replace DB table aliases.
foreach($testDB_conf['aliases'] as $k => $v)
{
	$testDB_conf['aliases'][$k] = preg_replace('~^'.preg_quote($tableprefix).'~', $evo_tests_tableprefix, $v);
}
// $tableprefix global follows the $evo_tests_tableprefix override.
$GLOBALS['tableprefix'] = $evo_tests_tableprefix;

?>
