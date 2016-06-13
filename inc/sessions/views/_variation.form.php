<?php
/**
 * This file implements the A/B Variation Test form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var VariationTest
 */
global $edited_VariationTest;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

$Form = new Form( NULL, 'vtest_checkchanges', 'post', 'compact' );

if( ! $creating )
{
	$Form->global_icon( T_('Delete this variation test!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb('variationtest') ) );
}
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', ( $creating ?  T_('New Variation Test') : T_('Variation Test') ).get_manual_link( 'variation-test-editing' ) );

	$Form->add_crumb( 'variationtest' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',vtst_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$Form->text_input( 'vtst_name', $edited_VariationTest->name, 40, T_('Name'), '', array( 'maxlength'=> 50, 'required'=>true ) );

	for( $c = 0; $c < 10; $c ++ )
	{
		$char = chr( $c + 65 );
		$field_required = $c < 2;
		$Form->text_input( 'tvar_name[]', $edited_VariationTest->get_variation_name( $c ), 40, sprintf( T_('Name of variation %s'), $char ), '', array( 'maxlength'=> 50, 'required' => $field_required ) );
	}

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_new]', T_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_copy]', T_('Record, then Create Similar'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'actionArray[update]', T_('Update'), 'SaveButton' ) ) );
}

?>