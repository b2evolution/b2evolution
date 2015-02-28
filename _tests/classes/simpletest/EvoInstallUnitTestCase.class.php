<?php
/**
 * The unit testcase class for install tests.
 */

/**
 * The DB class for the internal DB object.
 */
require_once( dirname(__FILE__).'/EvoDbUnitTestCase.class.php' );


global $install_subdir, $basepath;

require_once $basepath.$install_subdir.'_functions_install.php';
require_once $basepath.$install_subdir.'_functions_evoupgrade.php';
load_funcs('_core/model/db/_upgrade.funcs.php');


/**
 * The base class for all /install tests.
 */
class EvoInstallUnitTestCase extends EvoDbUnitTestCase
{
	/**
	 * List of basic plugins to test for being installed, sorted.
	 *
	 * @see install_basic_plugins()
	 */
	var $basic_plugins = array(
		'archives_plugin',
		'auto_p_plugin',
		'autolinks_plugin',
		'calendar_plugin',
		'flowplayer_plugin',
	//	'google_maps_plugin', // optional
		'ping_b2evonet_plugin',
		'ping_pingomatic_plugin',
		'quicktags_plugin',
		'smilies_plugin',
		'texturize_plugin',
		'tinymce_plugin',
		'twitter_plugin',
		'videoplug_plugin',
	);


	/**
	 * Setup globals according to /install/index.php
	 */
	function setUp()
	{
		global $timestamp, $test_DB;

		$timestamp = time() - 120; // We start dates 2 minutes ago because their dates increase 1 second at a time and we want everything to be visible when the user watches the blogs right after install :P

		$GLOBALS['script_start_time'] = time(); // used by installer

		parent::setUp();
	}

	/**
	 * General after-install checks.
	 */
	function tearDown()
	{
		// Test if item types (which get installed for Phoenix-Alpha) are present:
		// fp> What is this test good for?
		$this->assertEqual( $this->test_DB->get_var( 'SELECT ptyp_name FROM T_items__type ORDER BY ptyp_ID' ), 'Post' );

		// Check if all basic plugins have been installed.
		$installed_plugins = $this->test_DB->get_col( 'SELECT plug_classname FROM T_plugins ORDER BY plug_classname ASC' );

		// Make sure it's sorted in the same way.
		sort($installed_plugins);
		sort($this->basic_plugins);

		if( ! $this->assertFalse( array_diff($this->basic_plugins, $installed_plugins) ) )
		{
			echo 'Missing plugins: '.implode( ', ', array_diff($this->basic_plugins, $installed_plugins) )."<br />\n";
			echo 'Installed, but unexpected plugins: '.implode( ', ', array_diff($installed_plugins, $this->basic_plugins) )."<br />\n";
		}

		parent::tearDown();
	}
}

?>
