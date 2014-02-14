<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2013 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @author fplanque: Francois Planque.
 *
 * @version $Id$
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $users_all, $users_active, $users_newsletter;

load_funcs( 'newsletter/model/_newsletter.funcs.php' );

$current_User->check_perm( 'users', 'edit', true );

// Get users info for newsletter
$users_numbers = get_newsletter_users_numbers();

if( $users_numbers['all'] == 0 )
{	// No users in the filterset, Redirect to users list
	$Messages->add( T_('No found accounts in filterset. Please try to change the filter of users list.'), 'error' );
	header_redirect( $admin_url.'?ctrl=users' );
}

if( $users_numbers['newsletter'] == 0 )
{	// No users for newsletter
	$Messages->add( T_('No found active accounts which accept newsletter email. Please try to change the filter of users list.'), 'note' );
}

param_action();

/*
 * Perform actions:
 */
switch( $action )
{
	case 'preview':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'newsletter' );

		param( 'title', 'string' );
		param_check_not_empty( 'title', T_('Please enter a title.') );

		param( 'message', 'text' );
		param_check_not_empty( 'message', T_('Please enter a message.') );
		
		$Session->set( 'newsletter_title', $title );
		$Session->set( 'newsletter_message', $message );
		$Session->dbsave();

		if( $Messages->has_errors() )
		{	// Errors
			header_redirect( $admin_url.'?ctrl=newsletter' );
		}
		break;

	case 'send':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'newsletter' );

		$Messages->add( T_('Newsletter is sending now, please see a report below...'), 'success' );
		break;
}

$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
$AdminUI->breadcrumbpath_add( T_('List'), '?ctrl=users' );
$AdminUI->breadcrumbpath_add( T_('Newsletter'), '?ctrl=newsletter' );
$AdminUI->set_path( 'users', 'users' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

$AdminUI->disp_payload_begin();

/*
 * Display appropriate payload:
 */
switch( $action )
{
	case 'preview':
		$email_newsletter_params = array(
				'message' => $Session->get( 'newsletter_message' )
			);
		$newsletter = array(
				'title' => mail_autoinsert_user_data( $Session->get( 'newsletter_title' ), $current_User ),
				'html'  => mail_autoinsert_user_data( mail_template( 'newsletter', 'html', $email_newsletter_params, $current_User ), $current_User ),
				'text'  => mail_autoinsert_user_data( mail_template( 'newsletter', 'text', $email_newsletter_params, $current_User ), $current_User )
			);
		$AdminUI->disp_view( 'newsletter/views/_newsletter_preview.view.php' );
		break;

	case 'send':
		$AdminUI->disp_view( 'newsletter/views/_newsletter_report.view.php' );
		break;

	default:
		$AdminUI->disp_view( 'newsletter/views/_newsletter.form.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>