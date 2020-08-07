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

load_class( '_core/model/_module.class.php', 'Module' );
foreach( $modules as $module )
{
	require_once $inc_path.$module.'/_'.$module.'.init.php';
}

// Let the modules load/register what they need:
modules_call_method( 'init' );

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
		global $allow_evodb_reset;

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
		$db_params['use_persistent'] = false; // needed to not interfere with the DB connection to the test DB (setup in EvoDbUnitTestCase).
		$DB = new DB( $db_params );

		/*
		if( $DB->query('SHOW TABLES LIKE "T_settings"') && ! get_db_version() )
		{	// DB tables created, but no data loaded
			// Let's delete it and reinstall

			echo '<h2>'.T_('Deleting b2evolution tables from the database...').'</h2>';
			evo_flush();

			if( $allow_evodb_reset != 1 )
			{
				echo T_('If you have installed b2evolution tables before and wish to start anew, you must delete the b2evolution tables before you can start a new installation. b2evolution can delete its own tables for you, but for obvious security reasons, this feature is disabled by default.');
				echo '<p>'.sprintf( T_('To enable it, please go to the %s file and change: %s to %s'), '/conf/_basic_config.php', '<pre>$allow_evodb_reset = 0;</pre>', '<pre>$allow_evodb_reset = 1;</pre>' ).'</p>';
				echo '<p>'.T_('Then reload this page and a reset option will appear.').'</p>';
				echo '<p><a href="index.php?locale='.$default_locale.'">&laquo; '.T_('Back to install menu').'</a></p>';

				die();
			}

		//	$this->create_current_tables();
			db_delete();

			echo '<h2>Tables deleted</h2>';
		}


		if( ! $DB->query('SHOW TABLES LIKE "T_settings"') && ! get_db_version() )
		{	// b2evolution is not installed, let's run the installer
			global $inc_path, $modules, $default_locale, $evo_charset, $current_charset, $current_locale;

			// This initializes table name aliases and is required before trying to connect to the DB.
			load_class( '_core/model/_module.class.php', 'Module' );
			foreach( $modules as $module )
			{
				require_once $inc_path.$module.'/_'.$module.'.init.php';
			}

			// Let the modules load/register what they need:
			modules_call_method( 'init' );

			// Load all available locale defintions:
			locales_load_available_defs();

			// Activate default locale:
			if( ! locale_activate( $default_locale ) )
			{	// Could not activate locale (non-existent?), fallback to en-US:
				$default_locale = 'en-US';
				locale_activate( 'en-US' );
			}

			init_charsets( $current_charset );

			// fp> TODO: this test should probably be made more generic and applied to upgrade too.
			$expected_connection_charset = $DB->php_to_mysql_charmap($evo_charset);
			if( $DB->connection_charset != $expected_connection_charset )
			{
				echo '<div class="error"><p class="error">'.sprintf( T_('In order to install b2evolution with the %s locale, your MySQL needs to support the %s connection charset.').' (mysqli::set_charset(%s))',
					$current_locale, $evo_charset, $expected_connection_charset ).'</p></div>';
				// sam2kb> TODO: If something is not supported we can display a message saying "do this and that, enable extension X etc. etc... or switch to a better hosting".
				die();
			}

			//$this->create_current_tables();
			// NOTE: this is the same as with install action "newdb":
			echo '<h2>Manual</h2>';
			//install_newdb();
		}
		*/

		// Check if settings table exists then DB is installed
		$db_is_installed = (boolean) $DB->get_var( 'SHOW TABLES LIKE "T_settings"' );

		if( ! $db_is_installed )
		{ // A dirty workaround for GeneralSettings::_construct
			// where it checks $new_db_version against unexisting db_version because b2evo is not installed yet
			$tmp = $GLOBALS['new_db_version'];
			$GLOBALS['new_db_version'] = false;
		}

		$Settings = new GeneralSettings();
		$UserSettings = new UserSettings();

		if( ! $db_is_installed )
		{ // Revert $new_db_version to real value after dirty hack above
			$GLOBALS['new_db_version'] = $tmp;
		}

		// Reload conf and vars.
		require EVODIR.'conf/_config.php';
		require EVODIR.'inc/_main.inc.php';

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
		foreach( get_filenames( $path, array( 'inc_files' => false, 'recurse' => false ) ) as $dir )
		{
			if( is_link($dir) )
			{
				continue;
			}

			// files:
			$files = get_filenames( $dir, array( 'inc_dirs' => false, 'recurse' => false ) );
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