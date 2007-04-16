<?php
/**
 * This file implements the PluginUnitTestCase class, which
 * provides common methods for plugin tests.
 */


/**
 */
class PluginUnitTestCase extends EvoUnitTestCase
{
	/**
	 * Replace global $DB by MockDB instance
	 */
	function setUp()
	{
		parent::setUp();

		$this->old_PluginUnitTestCase_DB = $GLOBALS['DB'];
		$GLOBALS['DB'] = new MockDB( $this );
	}

	/**
	 * Restore global $DB
	 */
	function tearDown()
	{
		$GLOBALS['DB'] = $this->old_PluginUnitTestCase_DB;

		parent::tearDown();
	}


	/**
	 * @return Plugin
	 */
	function & get_fake_Plugin( $classname )
	{
		$GLOBALS['Plugins_admin'] = new Plugins_admin_no_DB(); // needs to be named "Plugins_admin", so it gets used by get_Cache()!

		$real_Plugin = new $classname();

		// Fake DB entry:
		$GLOBALS['Plugins_admin']->index_ID_rows[1] = array(
			'plug_ID' => 1,
			'plug_priority' => 50,
			'plug_classname' => $classname,
			'plug_name' => $real_Plugin->name,
			'plug_shortdesc' => $real_Plugin->short_desc,
			'plug_code' => 'fake',
			'plug_apply_rendering' => 'always',
			'plug_status' => 'enabled',
			'plug_version' => $real_Plugin->version );
		$GLOBALS['Plugins_admin']->register( 'auto_p_plugin', /* fake DB entry: */ 1 );
		$Plugin = & $GLOBALS['Plugins_admin']->get_next();

		return $Plugin;
	}
}

?>
