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
 * @var CollGroup
 */
global $edited_CollGroup;

// Determine if we are creating or updating...
global $action;

$creating = is_create_action( $action );

$Form = new Form( NULL, 'collgroup_checkchanges', 'post', 'compact' );

// Get permission to edit the collection group:
$perm_blog_group_edit = $current_User->check_perm( 'blog_group', 'edit', false, $edited_CollGroup->ID );

if( ! $creating && $perm_blog_group_edit )
{	// Display a link to delete the collection group only if Current user has no permission to edit it:
	$Form->global_icon( T_('Delete this collection group!'), 'delete', regenerate_url( 'action', 'action=delete_collgroup&amp;'.url_crumb( 'collgroup' ) ) );
}
$Form->global_icon( T_('Cancel editing!'), 'close', '?ctrl=dashboard' );

$Form->begin_form( 'fform', $creating ?  T_('New collection group') : T_('Collection group') );

	$Form->add_crumb( 'collgroup' );

	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',cgrp_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$Form->hidden( 'cgrp_ID', $edited_CollGroup->ID );

	// Name:
	if( $perm_blog_group_edit )
	{
		$Form->text_input( 'cgrp_name', $edited_CollGroup->get( 'name' ), 50, T_('Name'), '', array( 'maxlength' => 255, 'required' => true ) );
	}
	else
	{
		$Form->info( T_('Name'), $edited_CollGroup->get( 'name' ) );
	}

	// Owner:
	$owner_User = & $edited_CollGroup->get_owner_User();
	if( $perm_blog_group_edit )
	{
		$Form->username( 'cgrp_owner_login', $owner_User, T_('Owner'), T_('Login of this collection group\'s owner.'), '', array( 'required' => true ) );
	}
	else
	{
		$Form->info( T_('Owner'), $owner_User->get_identity_link() );
	}

	// Order:
	if( $perm_blog_group_edit )
	{
		$Form->text_input( 'cgrp_order', $edited_CollGroup->get( 'order' ), 5, T_('Order number'), '', array( 'maxlength' => 11, 'required' => true ) );
	}
	else
	{
		$Form->info( T_('Order number'), $edited_CollGroup->get( 'order' ) );
	}

if( ! $perm_blog_group_edit )
{	// Don't display a submit button if Current user has no permission to edit this colleciton group:
	$Form->end_form();
}
elseif( $creating )
{	// Display a button to create new collection group:
	$Form->end_form( array( array( 'submit', 'actionArray[create_collgroup]', T_('Record'), 'SaveButton' ) ) );
}
else
{	// Display a button to update the collection group:
	$Form->end_form( array( array( 'submit', 'actionArray[update_collgroup]', T_('Save Changes!'), 'SaveButton' ) ) );
}

?>