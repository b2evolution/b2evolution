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
 *
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
		$this->TestSettings = new AbstractSettings( 'testtable', array( 'test_name' ), 'test_value' );
		$this->TestSettings->DB =& $this->MockDB;
	}


	function tearDown()
	{
		parent::tearDown();
	}


	function testLoad()
	{
		$this->MockDB->expectOnce( 'get_results', array( new WantedPatternExpectation('/SELECT test_name, test_value FROM testtable/i') ), 'DB select ok.' );
		$this->TestSettings->load();
		$this->TestSettings->load();
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

		$this->TestSettings->load();
		$this->assertEqual( 'abc', $this->TestSettings->getDefault( 'default_abc' ) );
	}
}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new AbstractSettingsTestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
