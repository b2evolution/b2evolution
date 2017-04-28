<?php
/**
 * This file implements the form to edit user in organization.
 *
 * Called by {@link b2users.php}
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var Organization
 */
global $edited_Organization;

$org_ID = get_param( 'org_ID' );
$UserCache = & get_UserCache();
$edited_User = & $UserCache->get_by_ID( param( 'user_ID', 'integer' ) );
$org_data = $edited_User->get_organizations_data();

$Form = new Form( NULL, 'orguser_editmembership' );

$Form->begin_form( 'fform' );

	$Form->add_crumb( 'organization' );

	$Form->hiddens_by_key( get_memorized( 'action' ) ); // (this allows to come back to the right list order & page)
	$Form->hidden( 'edit_mode', true ); // this allows the controller to determine if it an edit to the membership information

	$Form->info_field( T_('Username'), $edited_User->get( 'login' ) );
	$Form->hidden( 'user_login', $edited_User->get('login') );
	$Form->radio( 'accepted', $org_data[$org_ID]['accepted'],
				array(
					array( '1', T_('Accepted') ),
					array( '0', T_('Not Accepted') ),
			), T_('Membership'), true );

	if( ( $edited_Organization->owner_user_ID == $current_User->ID ) || ( $edited_Organization->perm_role == 'owner and member' && $org_data[$org_ID]['accepted'] ) )
	{	// Display edit field if current user has a permission to edit role:
		$Form->text_input( 'role', $org_data[$org_ID]['role'], 32, T_('Role'), '', array( 'maxlength' => 255 ) );
	}
	else
	{	// Otherwise display info field with role value:
		$Form->info_field( T_('Role'), $org_data[$org_ID]['role'] );
	}

$buttons = array();
if( $current_User->check_perm( 'orgs', 'edit', false, $edited_Organization ) )
{	// Display a button to update the poll question only if current user has a permission:
	$buttons[] = array( 'submit', 'actionArray[link_user]', T_('Edit'), 'SaveButton' );
}
$Form->end_form( $buttons );
?>