<?php
/**
 * The unit testcase class for install tests.
 */

/**
 * The DB class for the internal DB object.
 */
require_once( dirname(__FILE__).'/DbUnitTestCase.class.php' );


global $install_subdir, $basepath;

require_once $basepath.$install_subdir.'_functions_install.php';
require_once $basepath.$install_subdir.'_functions_evoupgrade.php';
require_once $inc_path.'_misc/_upgrade.funcs.php';


/**
 * The base class for all /install tests.
 */
class InstallUnitTestCase extends DbUnitTestCase
{
	/**
	 * Number of basic plugins to test for being installed.
	 */
	var $nr_of_basic_plugins = 9;


	/**
	 * Setup globals according to /install/index.php
	 */
	function setUp()
	{
		global $timestamp, $test_DB;
		global $db_config;

		$timestamp = time() - 120; // We start dates 2 minutes ago because their dates increase 1 second at a time and we want everything to be visible when the user watches the blogs right after install :P

		// Rebuild $db_config['aliases'] as used with the SQL dumps:
		$tableprefix = 'evo_';
		$this->old_db_config_aliases_InstallUnitTestCase = $db_config['aliases'];
		$db_config['aliases'] = array(
				'T_antispam'           => $tableprefix.'antispam',
				'T_basedomains'        => $tableprefix.'basedomains',
				'T_blogs'              => $tableprefix.'blogs',
				'T_categories'         => $tableprefix.'categories',
				'T_coll_group_perms'   => $tableprefix.'bloggroups',
				'T_coll_user_perms'    => $tableprefix.'blogusers',
				'T_coll_settings'      => $tableprefix.'coll_settings',
				'T_comments'           => $tableprefix.'comments',
				'T_cron__log'          => $tableprefix.'cron__log',
				'T_cron__task'         => $tableprefix.'cron__task',
				'T_files'              => $tableprefix.'files',
				'T_filetypes'          => $tableprefix.'filetypes',
				'T_groups'             => $tableprefix.'groups',
				'T_hitlog'             => $tableprefix.'hitlog',
				'T_itemstatuses'       => $tableprefix.'poststatuses',
				'T_itemtypes'          => $tableprefix.'posttypes',
				'T_item__prerendering' => $tableprefix.'item__prerendering',
				'T_links'              => $tableprefix.'links',
				'T_locales'            => $tableprefix.'locales',
				'T_plugins'            => $tableprefix.'plugins',
				'T_pluginevents'       => $tableprefix.'pluginevents',
				'T_pluginsettings'     => $tableprefix.'pluginsettings',
				'T_pluginusersettings' => $tableprefix.'pluginusersettings',
				'T_postcats'           => $tableprefix.'postcats',
				'T_posts'              => $tableprefix.'posts',
				'T_sessions'           => $tableprefix.'sessions',
				'T_settings'           => $tableprefix.'settings',
				'T_subscriptions'      => $tableprefix.'subscriptions',
				'T_users'              => $tableprefix.'users',
				'T_useragents'         => $tableprefix.'useragents',
				'T_usersettings'       => $tableprefix.'usersettings',
			);

		parent::setUp();
	}

	/**
	 * General after-install checks.
	 */
	function tearDown()
	{
		global $db_config;

		// Test if item types (which get installed for Phoenix-Alpha) are present:
		$this->assertEqual(
			$this->test_DB->get_col( 'SELECT ptyp_name FROM T_itemtypes' ), array('Post', 'Link') );

		$db_config['aliases'] = $this->old_db_config_aliases_InstallUnitTestCase;

		parent::tearDown();
	}
}

?>
