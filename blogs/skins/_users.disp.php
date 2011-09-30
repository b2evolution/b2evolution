<?php
/**
 * This is the template that displays users
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 */

// Load classes
load_class( 'users/model/_user.class.php', 'User' );

// init variables
global $inc_path;
global $Users;
global $edited_User;

global $Skin;
if( !empty( $Skin ) ) {
	$display_params = array_merge( $Skin->get_template( 'Results' ), $Skin->get_template( 'users' ) );
} else {
	$display_params = NULL;
}

if( !is_logged_in() )
{
	debug_die( 'You are not logged in!' );
}

if( !isset( $disp ) )
{
	$disp = 'users';
}

// Get action parameter from request:
$action = param_action( 'view' );

// Preload users to show theirs avatars
load_messaging_threads_recipients( $current_User->ID );

switch( $action )
{
	case 'new':
		// Check permission:
		$current_User->check_perm( 'perm_edit', 'reply', true );

		// We don't have a model to use, start with blank object:
		$edited_User = new User();
		break;

	default:
		// Check permission:
		$current_User->check_perm( 'perm_messaging', 'reply', true );
		break;
}

// ----------------------- End Init variables --------------------------

echo '<br />';

// set params
if( !isset( $params ) )
{
	$params = array();
}
$params = array_merge( array(
	'form_class' => 'bComment',
	'form_title' => '',
	'form_action' => '',// TODO: ??? $samedomain_htsrv_url.'messaging.php',
	'form_name' => '',
	'form_layout' => NULL,
	'cols' => 40
	), $params );

switch( $disp )
{
	case 'users':
		require $inc_path.'users/views/_user_list.view.php';
		break;

	default:
		debug_die( "Unknown user tab" );
}

/**
 * $Log$
 * Revision 1.1  2011/09/30 12:24:56  efy-yurybakh
 * User directory
 *
 * Revision 1.4  2011/09/26 14:53:27  efy-asimo
 * Login problems with multidomain installs - fix
 * Insert globals: samedomain_htsrv_url, secure_htsrv_url;
 *
 * Revision 1.3  2011/09/22 08:55:00  efy-asimo
 * Login problems with multidomain installs - fix
 *
 * Revision 1.2  2011/09/04 22:13:24  fplanque
 * copyright 2011
 *
 * Revision 1.1  2011/08/11 09:05:10  efy-asimo
 * Messaging in front office
 *
 */
?>