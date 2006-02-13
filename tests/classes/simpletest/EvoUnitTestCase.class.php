<?php
/**
 * General EvoUnitTestCase.
 *
 * Implements common methods for testing.
 */


/**
 * Class EvoUnitTestCase
 */
class EvoUnitTestCase extends UnitTestCase
{
	/**
	 *
	 */
	function setUp()
	{
		global $FileRootCache, $FiletypeCache, $FileCache, $GroupCache, $DB, $EvoConfig, $Debuglog, $Messages, $UserCache, $Timer, $Plugins;

		$Debuglog = new Log('note');
		$Messages = new Log('error');
		$FileRootCache = new FileRootCache();
		$UserCache = new UserCache();
		$FileCache = new FileCache();
		$FileRootCache = new FileRootCache();
		$FiletypeCache = new FiletypeCache();
		$GroupCache = new DataObjectCache( 'Group', true, 'T_groups', 'grp_', 'grp_ID' );
		$Timer = new Timer();
		$Plugins = new Plugins_no_DB();

		$db_params = $EvoConfig->DB;
		$db_params['new_link'] = true; // needed to not interfere with the DB connection to the test DB (setup in DbUnitTestCase).
		$DB = new DB( $db_params );
	}


	/**
	 *
	 */
	function tearDown()
	{

	}
}

?>
