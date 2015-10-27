<?php
/**
 * This file display the poll form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $edited_Poll, $action, $admin_url;

// Determine if we are creating or updating:
$creating = is_create_action( $action );

$Form = new Form( NULL, 'poll_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action,pqst_ID' ) );

$Form->begin_form( 'fform', ( $creating ?  T_('New poll') : T_('Poll') ).get_manual_link( 'poll-form' ) );

	$Form->add_crumb( 'poll' );
	$Form->hidden( 'action',  $creating ? 'create' : 'update' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',pqst_ID' : '' ) ) );

	if( $current_User->check_perm( 'polls', 'edit' ) )
	{	// Allow to change an owner if current user has a permission to edit all polls:
		$Form->username( 'pqst_owner_login', $edited_Poll->get_owner_User(), T_('Owner'), '', '', array( 'required' => true ) );
	}
	else
	{	// Current user has no permission to edit a poll owner, Display the owner as info field:
		$Form->info( T_('Owner'), get_user_identity_link( NULL, $edited_Poll->owner_user_ID ) );
	}

	if( $current_User->check_perm( 'polls', 'edit', false, $edited_Poll ) )
	{
		$Form->text_input( 'pqst_question_text', $edited_Poll->get( 'question_text' ), 10, T_('Question'), '', array( 'maxlength' => 2000, 'required' => true, 'class' => 'large' ) );
	}
	else
	{
		$Form->info( T_('Question'), $edited_Poll->get( 'question_text' ) );
	}

$buttons = array();
if( $creating || $current_User->check_perm( 'polls', 'edit', false, $edited_Poll ) )
{	// Display a button to update the poll question only if current user has a permission:
	$buttons[] = array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' );
}

$Form->end_form( $buttons );
?>