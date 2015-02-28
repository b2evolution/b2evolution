<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'items/model/_itemtype.class.php', 'ItemType' );

/**
 * @var Itemtype
 */
global $edited_Itemtype;

// Determine if we are creating or updating...
global $action;

$creating = is_create_action( $action );

$Form = new Form( NULL, 'itemtype_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Delete this item type!'), 'delete', regenerate_url( 'action', 'action=delete' ) );
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ?  T_('New item type') : T_('Item type') );

	$Form->add_crumb( 'itemtype' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',ptyp_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

if( $creating )
{
	$Form->text_input( 'new_ptyp_ID', '', 8, T_('ID'), '', array( 'maxlength'=> 10, 'required'=>true ) );
}
else
{
	$Form->hidden( 'ptyp_ID', $edited_Itemtype->ID );
}

$Form->text_input( 'ptyp_name', $edited_Itemtype->name, 50, T_('Name'), '', array( 'maxlength'=> 255, 'required'=>true ) );

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