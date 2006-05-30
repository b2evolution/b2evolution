<?php
/**
 * Tests for the {@link Form} class.
 */

/**
 * SimpleTest config
 */
require_once dirname(__FILE__).'/../../config.simpletest.php';


/**
 * Form class
 */
require_once $GLOBALS['inc_path'].'_misc/_form.class.php';


Mock::generatePartial( 'Form', 'FormTestVersion', array('hidden') );


/**
 * Tests for the {@link Form} class.
 * @package tests
 */
class FormTestCase extends EvoUnitTestCase
{
	function FormTestCase()
	{
		$this->EvoUnitTestCase( 'Form class tests' );
	}


	function setUp()
	{
		parent::setUp();
	}


	function tearDown()
	{
		parent::tearDown();
	}


	/**
	 * Test {@link Form::hiddens_by_key()}
	 */
	function test_hiddens_by_key()
	{
		$hiddens = array(
			'a' => '1',
			'arr' => array(
				'arr1' => '2',
				'arr2arr' => array(
					'arr2arr1' => '3',
					'arr2arr2arr' => array(
						'arr2arr2arr1' => '4',
					),
				),
				'arr3' => '5',
			),
			'b' => '6',
		);

		$Form = new FormTestVersion( $this );
		$Form->output = true;

		$Form->expectCallCount( 'hidden', 6 );
		$Form->expectArgumentsAt( 0, 'hidden', array( 'a', '1' ) );
		$Form->expectArgumentsAt( 1, 'hidden', array( 'arr[arr1]', '2' ) );
		$Form->expectArgumentsAt( 2, 'hidden', array( 'arr[arr2arr][arr2arr1]', '3' ) );
		$Form->expectArgumentsAt( 3, 'hidden', array( 'arr[arr2arr][arr2arr2arr][arr2arr2arr1]', '4' ) );
		$Form->expectArgumentsAt( 4, 'hidden', array( 'arr[arr3]', '5' ) );
		$Form->expectArgumentsAt( 5, 'hidden', array( 'b', '6' ) );

		// Test:
		$Form->hiddens_by_key( $hiddens );

		// check that $form->output got not changed:
		$this->assertEqual( $Form->output, true );
	}

}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new FormTestCase();
	$test->run( new HtmlReporter() );
	unset( $test );
}
?>
