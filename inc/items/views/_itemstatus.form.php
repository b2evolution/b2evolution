<?php
/**
 * This file display the item status form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var ItemStatus
 */
global $edited_ItemStatus;

global $action;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = new Form( NULL, 'itemstatus_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', ( $creating ?  T_('New post status') : T_('Post status') ).get_manual_link( 'managing-item-statuses-form' ) );

	$Form->add_crumb( 'itemstatus' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',pst_ID' : '' ) ) );

	$Form->text_input( 'pst_name', $edited_ItemStatus->get( 'name' ), 30, T_('Name'), '', array( 'required' => true ) );

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_new]', T_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_copy]', T_('Record, then Create Similar'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ) ) );
}
?>