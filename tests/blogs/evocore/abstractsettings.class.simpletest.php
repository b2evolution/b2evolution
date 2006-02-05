<?php
/**
 * Tests for the {@link AbstractSettings} class.
 */

/**
 * SimpleTest config
 */
require_once( dirname(__FILE__).'/../../config.simpletest.php' );

require_once( EVODIR.'blogs/evocore/_abstractsettings.class.php' );


/**
 * @package tests
 */
class AbstractSettingsTestCase extends FilemanUnitTestCase
{
	function AbstractSettingsTestCase()
	{
		$this->FilemanUnitTestCase( 'AbstractSettings class test' );
	}


	function setUp()
	{
		parent::setup();

		$this->MockDB =& new MockDB($this);
		$this->TestSettings =& new AbstractSettings( 'testtable', array( 'test_name' ), 'test_value' );
		$this->TestSettings->DB =& $this->MockDB;
	}


	function tearDown()
	{
		parent::tearDown();
	}


	function testLoad()
	{
		$this->MockDB->expectOnce( 'get_results', array( new WantedPatternExpectation('/SELECT test_name, test_value\s+FROM testtable/i') ), 'DB select ok.' );
		$this->TestSettings->_load();
		$this->TestSettings->_load();
		$this->MockDB->tally();
	}


	/**
	 * Check if we get defaults right.
	 */
	function testDefaults()
	{
		$this->TestSettings->_defaults = array(
			'default_1' => '1',
			'default_abc' => 'abc',
		);

		$this->TestSettings->_load();
		$this->assertEqual( 'abc', $this->TestSettings->get_default( 'default_abc' ) );
	}


	/**
	 * Tests AbstractSettings::set()
	 */
	function testPreferExplicitSet()
	{
		$this->MockDB->expectOnce( 'get_results', array( new WantedPatternExpectation('/SELECT test_name, test_value\s+FROM testtable/i') ), 'DB select ok.' );
		$this->TestSettings->set( 'lala', 1 );

		$this->MockDB->expectNever( 'get_results', false, 'Did not reload settings from DB.' );
		$this->TestSettings->_load();

		$this->assertEqual( $this->TestSettings->get( 'lala' ), 1, 'Prefer setting which was set before explicit load().' );
		$this->assertNull( $this->TestSettings->get( 'lala_notset' ), 'Return NULL for non-existing setting.' );

		$this->MockDB->tally();
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new AbstractSettingsTestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
