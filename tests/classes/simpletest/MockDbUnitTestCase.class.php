<?php
/**
 * This file implements the MockDbUnitTestCase class, which
 * setups the global $DB as a mocked DB object.
 */


/**
 * The base class for all unit tests that use a mocked DB object.
 *
 * It sets the global $DB to a mocked one.
 */
class MockDbUnitTestCase extends EvoUnitTestCase
{
	/**
	 * @var DB mocked DB object
	 */
	var $MockDB;

	/**
	 * @var array List of methods that should get mocked
	 */
	var $mocked_DB_methods = array();


	/**
	 *
	 */
	function MockDBUnitTestCase( $title )
	{
		Mock::generatePartial('DB', 'MockDbUnitTestCase_DB_'.get_class($this), $this->mocked_DB_methods);
		parent::EvoUnitTestCase($title);
	}


	/**
	 * Setup global $DB as reference to a mocked one.
	 */
	function setUp()
	{
		global $testdb_conf;
		parent::setup();

		$classname = 'MockDbUnitTestCase_DB_'.get_class($this);
		$this->MockDB = new $classname($this);
		$this->MockDB->DB( $testdb_conf );

		$this->old_DB_MockDbUnitTestCache = & $GLOBALS['DB'];
		$GLOBALS['DB'] = & $this->MockDB;
	}


	/**
	 * Setup global $DB as original DB object again.
	 */
	function tearDown()
	{
		$GLOBALS['DB'] = $this->old_DB_MockDbUnitTestCache;

		parent::tearDown();
	}
}

?>
