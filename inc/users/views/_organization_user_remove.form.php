<?php
/**
 * This file implements the dialog to confirm removal of user from organization.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * @var Organization
 */
global $edited_Organization;

$UserCache = & get_UserCache();
$edited_User = & $UserCache->get_by_ID( param( 'user_ID', 'integer' ) );

$Form = new Form( NULL, 'orguser_removemembership' );

$Form->begin_form( 'fform' );

	$Form->add_crumb( 'organization' );
	$Form->hiddens_by_key( get_memorized( 'action' ) ); // (this allows to come back to the right list order & page)
	$Form->hidden( 'user_login', $edited_User->get( 'login' ) );

	echo '<p>'.sprintf( T_('This will remove %s from the organization. Would you like to continue?'), $edited_User->get( 'preferredname' ) ).'</p>';

$buttons = array();
if( $current_User->check_perm( 'orgs', 'edit', false, $edited_Organization ) )
{	// Display a button to confirm removal of user from the organization
	$buttons[] = array( 'submit', 'actionArray[unlink_user]', T_('Continue'), 'SaveButton' );
}
$Form->end_form( $buttons );
?>