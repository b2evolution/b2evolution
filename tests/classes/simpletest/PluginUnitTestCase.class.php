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
	 *
	 *
	 * @return
	 */
	function & get_fake_Plugin( $classname )
	{
		$Plugins = new Plugins_no_DB();

		$real_Plugin = new $classname();

		// Fake DB entry:
		$Plugins->index_ID_rows[1] = array(
			'plug_ID' => 1,
			'plug_priority' => 50,
			'plug_classname' => 'auto_p_plugin',
			'plug_code' => 'fake',
			'plug_apply_rendering' => 'always',
			'plug_status' => 'enabled',
			'plug_version' => $real_Plugin->version );
		$Plugins->register( 'auto_p_plugin', /* fake DB entry: */ 1 );
		$Plugin = & $Plugins->get_next();

		return $Plugin;
	}
}

?>
