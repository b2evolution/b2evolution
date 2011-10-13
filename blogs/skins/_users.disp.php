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

if( !isset( $disp ) )
{
	$disp = 'users';
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
		require $inc_path.'users/views/_user_list_short.view.php';
		break;

	default:
		debug_die( "Unknown user tab" );
}

/**
 * $Log$
 * Revision 1.4  2011/10/13 17:40:53  fplanque
 * no message
 *
 * Revision 1.3  2011/10/05 07:54:51  efy-yurybakh
 * User directory (fix error if accessed anonymously)
 *
 * Revision 1.2  2011/10/03 13:37:49  efy-yurybakh
 * User directory
 *
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