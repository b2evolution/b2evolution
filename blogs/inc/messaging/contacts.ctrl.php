<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id: contacts.ctrl.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $DB, $current_User;

// Check minimum permission:
if( !$current_User->check_perm( 'perm_messaging', 'reply' ) )
{
	$Messages->add( 'Sorry, you are not allowed to view cotnacts!' );
	header_redirect( $admin_url );
}

// Set options path:
$AdminUI->set_path( 'messaging', 'contacts' );

// Get action parameter from request:
param_action();

$mct_blocked = NULL;
switch( $action )
{
	case 'block': // Block selected contact
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_contacts' );
		$mct_blocked = 1;
		break;

	case 'unblock': // Unblock selected contact
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_contacts' );
		$mct_blocked = 0;
		break;

	case 'remove_user': // Remove user from contacts group
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_contacts' );

		$user_ID = param( 'user_ID', 'integer', 0 );
		$group_ID = param( 'group_ID', 'integer', 0 );
		if( $user_ID > 0 && $group_ID > 0 )
		{	// Remove user from selected group
			if( remove_contacts_group_user( $group_ID, $user_ID ) )
			{	// User has been removed from the group
				// Redirect to the contacts list
				header_redirect( url_add_param( $admin_url, 'ctrl=contacts', '&' ) );
			}
		}
		break;

	case 'add_group': // Add users to the group
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_contacts' );

		$group = param( 'group', 'string', '' );
		$users = param( 'users', 'string', '' );

		if( $result = create_contacts_group_users( $group, $users ) )
		{	// Users have been added to the group
			$Messages->add( sprintf( T_('%d contacts have been added to the &laquo;%s&raquo; group.'), $result['count_users'], $result['group_name'] ), 'success' );
			header_redirect( url_add_param( $admin_url, 'ctrl=contacts' ) );
		}
		break;

	case 'rename_group': // Rename the group
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_contacts' );

		$group_ID = param( 'group_ID', 'integer', true );

		if( rename_contacts_group( $group_ID ) )
		{
			$Messages->add( T_('The group has been renamed.'), 'success' );
			header_redirect( url_add_param( $admin_url, 'ctrl=contacts&g='.$group_ID, '&' ) );
		}
		break;

	case 'delete_group': // Delete the group
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'messaging_contacts' );

		$group_ID = param( 'group_ID', 'integer', true );

		if( delete_contacts_group( $group_ID ) )
		{
			$Messages->add( T_('The group has been deleted.'), 'success' );
			header_redirect( url_add_param( $admin_url, 'ctrl=contacts', '&' ) );
		}
		break;
}

if( isset( $mct_blocked ) )
{
	set_contact_blocked( param( 'user_ID', 'integer' ), $mct_blocked );

	// Memorize params for the function regenerate_url()
	param( 's', 'string', '', true );
	param( 'g', 'integer', 0, true );
	param( 'results_mct_page', 'integer', 0, true );
	param( 'results_mct_order', 'string', '', true );
	// Redirect so that a reload doesn't write to the DB twice:
	header_redirect( regenerate_url( '', '', '', '&' ), 303 ); // Will EXIT
	// We have EXITed already at this point!!
	break;
}

$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Messages'), '?ctrl=threads' );
$AdminUI->breadcrumbpath_add( T_('Contacts'), '?ctrl=contacts' );

// Display messages depending on user email status
display_user_email_status_message();

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

?>