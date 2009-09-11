<?php
/**
 * This file implements the User field form.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $edited_Userfield;

global $Settings;

global $dispatcher;

// Determine if we are creating or updating...
global $action;
$creating = is_create_action( $action );

global $new_ufdf_ID;

$Form = & new Form( NULL, 'userfield_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Delete this userfield!'), 'delete', regenerate_url( 'action', 'action=delete' ) );
$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', $creating ?  T_('New user field') : T_('User field') );

	$Form->hidden( 'action', $creating ? 'create' : 'update' );
	$Form->hiddens_by_key( get_memorized( 'action,ufdf_ID' ) ); // (this allows to come back to the right list order & page)

	if( $creating )
	{
		$Form->text_input( 'new_ufdf_ID', $new_ufdf_ID, 8, T_('ID'), '', array( 'maxlength'=> 10, 'required'=>true ) );
	}
	else
	{
		$Form->hidden( 'ufdf_ID', $edited_Userfield->ID );
	}

	$Form->select_input_array( 'ufdf_type', $edited_Userfield->type, array(
		'email' => T_('Email address'),
		'word' => T_('Single word'),
		'number' => T_('Number'),
		'phone' => T_('Phone number'),
		'url' => T_('URL'),
		'text' => T_('Text'),
		 ), T_('Type'), '', array( 'required'=>true ) );
	$Form->text_input( 'ufdf_name', $edited_Userfield->name, 50, T_('Name'), '', array( 'maxlength'=> 255, 'required'=>true ) );

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Record'), 'SaveButton' ),
													array( 'submit', 'submit', T_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'submit', T_('Record, then Create Similar'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Update'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}


?>