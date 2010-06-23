<?php
/**
 * General EvoUnitTestCase.
 *
 * Implements common methods for testing.
 */


/**
 * We use a derived reporter, which shows passes
 */
//require_once( dirname(__FILE__).'/HtmlReporterShowPasses.class.php' );

load_class( '_core/model/_log.class.php', 'Log' );
load_class( 'files/model/_filerootcache.class.php', 'FileRootCache' );
load_class( 'files/model/_filecache.class.php', 'FileCache' );
load_class( 'files/model/_filetypecache.class.php', 'FileTypeCache' );
load_class( '_core/model/_timer.class.php', 'Timer' );
load_class( 'plugins/model/_plugins_admin.class.php', 'Plugins_admin' );
load_class( 'plugins/model/_plugins_admin_no_db.class.php', 'Plugins_admin_no_DB' );
load_class( 'settings/model/_generalsettings.class.php', 'GeneralSettings' );
load_class( 'users/model/_usersettings.class.php', 'UserSettings' );
load_class( 'users/model/_user.class.php', 'User' );
load_class( 'users/model/_usercache.class.php', 'UserCache' );
load_class( 'files/model/_file.class.php', 'File' );
load_class( 'files/model/_filetype.class.php', 'FileType' );


/**
 * Class EvoUnitTestCase
 */
class EvoUnitTestCase extends UnitTestCase
{
	/**
	 * Is this a slow / long running testcase?
	 * This might be useful to skip later on.
	 */
	const slow_testcase = NULL;


	/**
	 * Setup required globals
	 */
	function setUp()
	{
		global $FileRootCache, $FiletypeCache, $FileCache, $GroupCache, $DB, $db_config, $Debuglog, $Messages, $UserCache, $Timer, $Plugins, $Settings, $UserSettings;

		parent::setUp(); // just because..

		$Debuglog = new Log('note');
		$Messages = new Log('error');
		$FileRootCache = new FileRootCache();
		$UserCache = new UserCache();
		$FileCache = new FileCache();
		$FileRootCache = new FileRootCache();
		$FiletypeCache = new FiletypeCache();
		$GroupCache = new DataObjectCache( 'Group', true, 'T_groups', 'grp_', 'grp_ID' );
		$Timer = new Timer();
		$Plugins = new Plugins_admin_no_DB();

		$db_params = $db_config;
		$db_params['new_link'] = true; // needed to not interfere with the DB connection to the test DB (setup in EvoDbUnitTestCase).
		$DB = new DB( $db_params );

		$Settings = new GeneralSettings();
		$UserSettings = new UserSettings();

		// Reload conf and vars.
		require EVODIR.'blogs/conf/_config.php';
		require EVODIR.'blogs/inc/_main.inc.php';

		$this->_saved_globals = array();
		$this->_saved_server = array();
	}


	/**
	 * Revert any changed globals.
	 * @see change_global()
	 * @see change_server()
	 */
	function tearDown()
	{
		parent::tearDown();
		foreach( $this->_saved_globals as $k => $v )
		{
			$GLOBALS[$k] = $v;
		}
		foreach( $this->_saved_server as $k => $v )
		{
			$_SERVER[$k] = $v;
		}
	}


	/**
	 * Temporarily change a global, which gets reset in {@link tearDown()}.
	 * @param string Name of global
	 * @param mixed value
	 * @return
	 */
	function change_global($global, $value)
	{
		if( ! isset($this->_saved_globals[$global]) )
		{
			$this->_saved_globals[$global] = $GLOBALS[$global];
		}
		$GLOBALS[$global] = $value;
	}


	/**
	 * Temporarily change a $_SERVER setting, which gets reset in {@link tearDown()}.
	 * @param string Name of $_SERVER setting, e.g. 'HTTP_ACCEPT_LANGUAGE'
	 * @param mixed value
	 * @return
	 */
	function change_server($key, $value)
	{
		if( ! isset($this->_saved_server[$key]) )
		{
			$this->_saved_server[$key] = $_SERVER[$key];
		}
		$_SERVER[$key] = $value;
	}


	/**
	 * Extend run() method to recognize cli mode.
	 *
	 * @param SimpleReporter Reporter for HTML mode
	 * @param SimpleReporter Reporter for CLI mode
	 * @access public
	 */
	function run_html_or_cli()
	{
		if( EvoTextReporter::inCli() )
		{
			exit( parent::run( new EvoTextReporter() ) ? 0 : 1 );
		}
		parent::run( new EvoHtmlReporter() );
	}


	/**
	 * Custom method to print a skip message.
	 */
	function my_skip_message($message)
	{
		$this->reporter->paintSkip($message . $this->getAssertionLine());
	}


	/**
	 * Get all files below a path, excluding symlinks.
	 * @return array
	 */
	function get_files_without_symlinks( $path, $pattern = '~\.(php|inc|html?)$~' )
	{
		$r = array();
		foreach( get_filenames( $path, false, true, true, false ) as $dir )
		{
			if( is_link($dir) )
			{
				continue;
			}

			// files:
			$files = get_filenames( $dir, true, false, true, false );
			if( $pattern )
			{
				foreach( $files as $k => $v )
				{
					if( ! preg_match( $pattern, $v ) )
					{ // Not a PHP file (include HTM(L), because it gets parsed sometimes, too)
						unset($files[$k]);
					}
				}
			}
			$r = array_merge( $r, $files );

			// subdirs:
			$r = array_merge( $r, $this->get_files_without_symlinks($dir));
		}
		return $r;
	}
}


#
# WORK IN PROGRESS: dh> an attempt to silence the noise from test, especially install tests.
#

/**
 * A reporter that is meant to suppress any output from the test methods.
 * Unfortunately, this silences simpletests error reporting, too.. :/
 * @todo dh> find another way.
 */
class EvoTextReporter extends TextReporter
{
	function paintMethodStart($test_name)
	{
		parent::paintMethodStart($test_name);
		//ob_start();
	}

	function paintMethodEnd($test_name)
	{
		//while(ob_get_status())
		//	ob_end_clean();
		parent::paintMethodEnd($test_name);
	}
}

/**
 * A reporter that is meant to suppress any output from the test methods.
 * Unfortunately, this silences simpletests error reporting, too.. :/
 * @todo dh> find another way.
 */
class EvoHtmlReporter extends HtmlReporter
{
	function __construct($charset = NULL)
	{
		if( is_null($charset) )
		{
			global $io_charset;
			$charset = $io_charset;
		}
		parent::__construct($charset);
	}

	function paintMethodStart($test_name)
	{
		parent::paintMethodStart($test_name);
		//ob_start();
	}

	function paintMethodEnd($test_name)
	{
		//while(ob_get_status())
		//	ob_end_clean();
		parent::paintMethodEnd($test_name);
	}
}

?>
