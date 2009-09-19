<?php
/**
 * This file implements the EvoPluginUnitTestCase class, which
 * provides common methods for plugin tests.
 */


/**
 */
class EvoPluginUnitTestCase extends EvoUnitTestCase
{
	/**
	 * Replace global $DB by MockDB instance
	 */
	function setUp()
	{
		parent::setUp();

		$this->old_EvoPluginUnitTestCase_DB = $GLOBALS['DB'];
		$GLOBALS['DB'] = new MockDB( $this );
	}

	/**
	 * Restore global $DB
	 */
	function tearDown()
	{
		$GLOBALS['DB'] = $this->old_EvoPluginUnitTestCase_DB;

		parent::tearDown();
	}


	/**
	 * @return Plugin
	 */
	function get_Plugin( $classname )
	{
		if( ! class_exists($classname) )
		{
			global $Plugins;
			require_once $Plugins->get_classfile_path($classname);
		}
		$real_Plugin = new $classname();
		return $real_Plugin;
	}


	/**
	 * @return Plugin
	 */
	function & get_fake_Plugin( $classname )
	{
		$GLOBALS['Plugins'] = new Plugins_admin_no_DB();

		$real_Plugin = $this->get_Plugin($classname);

		// Fake DB entry:
		$GLOBALS['Plugins']->index_ID_rows[1] = array(
			'plug_ID' => 1,
			'plug_priority' => 50,
			'plug_classname' => $classname,
			'plug_name' => $real_Plugin->name,
			'plug_shortdesc' => $real_Plugin->short_desc,
			'plug_code' => 'fake',
			'plug_apply_rendering' => 'always',
			'plug_status' => 'enabled',
			'plug_version' => $real_Plugin->version );
		$GLOBALS['Plugins']->register( 'auto_p_plugin', /* fake DB entry: */ 1 );
		$Plugin = & $GLOBALS['Plugins']->get_next();

		return $Plugin;
	}
}

?>
