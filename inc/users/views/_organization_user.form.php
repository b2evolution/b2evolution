<?php
/**
 * This file implements the form to add user to organization.
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

$Form = new Form( NULL, 'orguser_checkchanges' );

$Form->begin_form( 'fform' );

	$Form->add_crumb( 'organization' );

	$Form->hiddens_by_key( get_memorized( 'action' ) ); // (this allows to come back to the right list order & page)

	$User = NULL;
	$Form->username( 'user_login', $User, T_('Username'), '', '', array( 'required' => true ) );

	$Form->radio( 'accepted', '1',
				array(
					array( '1', T_('Accepted') ),
					array( '0', T_('Not Accepted') ),
			), T_('Membership'), true );

	$Form->text_input( 'role', '', 32, T_('Role'), '', array( 'maxlength' => 255 ) );

$buttons = array();
if( $current_User->check_perm( 'orgs', 'edit', false, $edited_Organization ) )
{	// Display a button to update the poll question only if current user has a permission:
	$buttons[] = array( 'submit', 'actionArray[link_user]', T_('Add'), 'SaveButton' );
}
$Form->end_form( $buttons );
?>