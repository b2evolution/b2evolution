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


/**
 * @var Section
 */
global $edited_Section;

// Determine if we are creating or updating...
global $action;

$creating = is_create_action( $action );

$Form = new Form( NULL, 'section_checkchanges', 'post', 'compact' );

// Get permission to edit the section:
$perm_blog_group_edit = $current_User->check_perm( 'section', 'edit', false, $edited_Section->ID );

if( ! $creating && $perm_blog_group_edit && $edited_Section->ID != 1 )
{	// Display a link to delete the section only if Current user has no permission to edit it:
	$Form->global_icon( T_('Delete this section!'), 'delete', regenerate_url( 'action', 'action=delete_section&amp;'.url_crumb( 'section' ) ) );
}
$Form->global_icon( T_('Cancel editing!'), 'close', '?ctrl=dashboard' );

$Form->begin_form( 'fform', $creating ?  T_('New section') : T_('Section') );

	$Form->add_crumb( 'section' );

	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',sec_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$Form->hidden( 'sec_ID', $edited_Section->ID );

	// Name:
	if( $perm_blog_group_edit && $edited_Section->ID != 1 )
	{
		$Form->text_input( 'sec_name', $edited_Section->get( 'name' ), 50, T_('Name'), '', array( 'maxlength' => 255, 'required' => true ) );
	}
	else
	{
		$Form->info( T_('Name'), $edited_Section->get( 'name' ) );
	}

	// Owner:
	$owner_User = & $edited_Section->get_owner_User();
	if( $perm_blog_group_edit )
	{
		$Form->username( 'sec_owner_login', $owner_User, T_('Owner'), T_('Login of this section\'s owner.'), '', array( 'required' => true ) );
	}
	else
	{
		$Form->info( T_('Owner'), $owner_User->get_identity_link() );
	}

	// Order:
	if( $perm_blog_group_edit )
	{
		$Form->text_input( 'sec_order', $edited_Section->get( 'order' ), 5, T_('Order number'), '', array( 'maxlength' => 11, 'required' => true ) );
	}
	else
	{
		$Form->info( T_('Order number'), $edited_Section->get( 'order' ) );
	}

if( ! $perm_blog_group_edit )
{	// Don't display a submit button if Current user has no permission to edit this colleciton group:
	$Form->end_form();
}
elseif( $creating )
{	// Display a button to create new section:
	$Form->end_form( array( array( 'submit', 'actionArray[create_section]', T_('Record'), 'SaveButton' ) ) );
}
else
{	// Display a button to update the section:
	$Form->end_form( array( array( 'submit', 'actionArray[update_section]', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>