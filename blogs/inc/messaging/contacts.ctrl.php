<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package messaging
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $DB, $current_User;

// Check minimum permission:
$current_User->check_perm( 'perm_messaging', 'write', true );

// Set options path:
$AdminUI->set_path( 'messaging', 'contacts' );

// Get action parameter from request:
param_action();

// Preload users to show theirs avatars

load_messaging_threads_recipients( $current_User->ID );

switch( $action )
{
	case 'block': // Block selected contact
		$mct_blocked = 1;
		break;

	case 'unblock': // Unblock selected contact
		$mct_blocked = 0;
		break;
}

if( isset( $mct_blocked ) )
{
	// Check permission:
	$current_User->check_perm( 'perm_messaging', 'write', true );

	$sql = 'UPDATE T_messaging__contact
				SET mct_blocked = '.$mct_blocked.'
					WHERE mct_from_user_ID = '.$current_User->ID.'
					AND mct_to_user_ID = '.param( 'user_ID', 'integer' );

	$DB->query( $sql );

	// Redirect so that a reload doesn't write to the DB twice:
	header_redirect( '?ctrl=contacts', 303 ); // Will EXIT
	// We have EXITed already at this point!!
	break;
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

$AdminUI->disp_payload_begin();

/**
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;

	case 'block':
	case 'unblock':
	default:
		// Display contacts:
		$AdminUI->disp_view( 'messaging/views/_contact_list.view.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log$
 * Revision 1.5  2009/10/08 20:05:52  efy-maxim
 * Modular/Pluggable Permissions
 *
 * Revision 1.4  2009/09/19 20:31:38  efy-maxim
 * 'Reply' permission : SQL queries to check permission ; Block/Unblock functionality; Error messages on insert thread/message
 *
 * Revision 1.3  2009/09/19 11:29:05  efy-maxim
 * Refactoring
 *
 * Revision 1.2  2009/09/19 01:15:49  fplanque
 * minor
 *
 */
?>
