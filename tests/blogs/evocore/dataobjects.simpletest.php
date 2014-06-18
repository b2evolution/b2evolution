<?php
/**
 * Tests for the {@link DataObject} and {@link DataObjectCache} classes.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );


load_class('_core/model/dataobjects/_dataobject.class.php', 'DataObject');
load_class('_core/model/dataobjects/_dataobjectcache.class.php', 'DataObjectCache');


class TestDataObjectCache extends DataObjectCache
{
	function __construct()
	{
		parent::__construct('TestDataObject', false, 'T_foobar', '', 'ID');
	}
}
class TestDataObject extends DataObject
{
	function __construct($db_row = NULL)
	{
		parent::__construct('T_foobar', '', 'ID');
		if( $db_row )
		{
			foreach($db_row as $k => $v)
				$this->$k = $v;
		}
	}
}


/**
 * @package tests
 */
class DataobjectsTestCase extends EvoMockDbUnitTestCase
{
	var $mocked_DB_methods = array('get_results', 'query');

	function __construct()
	{
		parent::__construct( 'Dataobject tests' );
	}


	function setUp()
	{
		parent::setup();

		$this->TestSettings = new AbstractSettings( 'testtable', array( 'test_name' ), 'test_value' );
	}


	function test_dataobjectcache_getnext()
	{
		$this->MockDB->expectOnce( 'get_results', array(new PatternExpectation('/SELECT \*\s+FROM T_foobar\s+ORDER BY ID/'), '*', '*') );

		$r = array(
			(object)array('ID' => 1, 'key2' => 2,  'key3' => 3, 'val' => 4 ),
			(object)array('ID' => 2, 'key2' => 22, 'key3' => 3, 'val' => 44),
			(object)array('ID' => 3, 'key2' => 2,  'key3' => 2, 'val' => 2 ));
		$this->MockDB->setReturnValueAt(0, 'get_results', $r);

		$DC = new TestDataObjectCache();

		$this->assertTrue($DC->load_all());

		$o = $DC->get_next();
		$this->assertEqual($o->ID, 1);
		$o = $DC->get_next();
		$this->assertEqual($o->ID, 2);
		$o = $DC->get_next();
		$this->assertEqual($o->ID, 3);
		$o = $DC->get_next();
		$this->assertEqual($o, false);
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new DataobjectsTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
