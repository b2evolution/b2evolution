<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'users/model/_userfieldgroup.class.php', 'UserfieldGroup' );

/**
 * @var Userfield
 */
global $edited_UserfieldGroup;

// Determine if we are creating or updating...
global $action;

$creating = is_create_action( $action );

$Form = new Form( NULL, 'userfieldgroup_checkchanges', 'post', 'compact' );

$Form->global_icon( TB_('Delete this user field group!'), 'delete', regenerate_url( 'action', 'action=delete&amp;'.url_crumb('userfieldgroup') ) );
$Form->global_icon( TB_('Cancel editing').'!', 'close', '?ctrl=userfields' );

$Form->begin_form( 'fform', $creating ?  TB_('New user field group') : TB_('User field group') );

	$Form->add_crumb( 'userfieldgroup' );

	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',ufgp_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$Form->hidden( 'ufgp_ID', $edited_UserfieldGroup->ID );

	$Form->text_input( 'ufgp_name', $edited_UserfieldGroup->name, 50, TB_('Name'), '', array( 'maxlength'=> 255, 'required'=>true ) );

	$Form->text_input( 'ufgp_order', $edited_UserfieldGroup->order, 5, TB_('Order number'), '', array( 'maxlength'=> 11, 'required'=>true ) );

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'actionArray[create]', TB_('Record'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_new]', TB_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_copy]', TB_('Record, then Create Similar'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'actionArray[update]', TB_('Save Changes!'), 'SaveButton' ) ) );
}

?>