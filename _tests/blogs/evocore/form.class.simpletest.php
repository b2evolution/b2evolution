<?php
/**
 * Tests for the {@link Form} class.
 * @package tests
 */

/**
 * SimpleTest config
 */
require_once dirname(__FILE__).'/../../config.simpletest.php';


load_class( '_core/ui/forms/_form.class.php', 'Form' );


Mock::generatePartial('Form', 'MockFormHidden', array('hidden'));


/**
 * Tests for the {@link Form} class.
 * @package tests
 */
class FormTestCase extends EvoUnitTestCase
{
	function __construct()
	{
		parent::__construct( 'Form class tests' );
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

		$Form = new MockFormHidden( $this );
		$Form->output = true;

		$Form->expectCallCount( 'hidden', 6 );
		$Form->expectAt( 0, 'hidden', array( 'a', '1' ) );
		$Form->expectAt( 1, 'hidden', array( 'arr[arr1]', '2' ) );
		$Form->expectAt( 2, 'hidden', array( 'arr[arr2arr][arr2arr1]', '3' ) );
		$Form->expectAt( 3, 'hidden', array( 'arr[arr2arr][arr2arr2arr][arr2arr2arr1]', '4' ) );
		$Form->expectAt( 4, 'hidden', array( 'arr[arr3]', '5' ) );
		$Form->expectAt( 5, 'hidden', array( 'b', '6' ) );

		// Test:
		$Form->hiddens_by_key( $hiddens );

		// check that $form->output got not changed:
		$this->assertEqual( $Form->output, true );
	}


	/**
	 * Test if params from "action" get handled correctly as hiddens.
	 */
	function test_hiddens_from_action()
	{
		// GET:
		$Form = new MockFormHidden( $this );
		$Form->expectCallCount( 'hidden', 3 );
		$Form->expectAt( 0, 'hidden', array( 'name', '1' ) );
		$Form->expectAt( 1, 'hidden', array( 'name[]', '2' ) );
		$Form->expectAt( 2, 'hidden', array( 'name[]', '3' ) );

		$Form->Form('?name=1&name[]=2&name[]=3&k=v&k[]=v', '', 'get');
		$Form->output = false;
		$Form->begin_form();
		// Add a field with one of the action param names, which should skip "k" from being used as hidden.
		$Form->text_input('k', 'v2', 1, 'label');
		$Form->text_input('k[]', '1', 1, 'label');
		$Form->end_form();
		$this->assertEqual($Form->form_action, '');


		// POST:
		// No call to hidden should get performed and params should stay in form_action.
		$Form = new MockFormHidden( $this );
		$Form->expectCallCount( 'hidden', 0 );

		$Form->Form('?name=1&name[]=2&name[]=3', '', 'post');
		$Form->begin_form();
		$Form->end_form();

		$this->assertEqual($Form->form_action, '?name=1&name[]=2&name[]=3');
	}

}


if( !isset( $this ) )
{ // Called directly, run the TestCase alone
	$test = new FormTestCase();
	$test->run_html_or_cli();
	unset( $test );
}
?>
