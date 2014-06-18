<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-bogdan: Evo Factory / Bogdan.
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: registration.ctrl.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Check minimum permission:
$current_User->check_perm( 'users', 'view', true );

$AdminUI->set_path( 'users', 'usersettings', 'registration' );

param_action();

switch ( $action )
{
	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'registration' );

		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		// keep old newusers_canregister setting value to check if we need to invalidate pagecaches
		$old_newusers_canregister = $Settings->get( 'newusers_canregister' );

		// UPDATE general settings:
		param( 'newusers_canregister', 'integer', 0 );
		param( 'registration_is_public', 'integer', 0 );
		param( 'newusers_grp_ID', 'integer', true );

		param_integer_range( 'newusers_level', 0, 9, T_('User level must be between %d and %d.') );

		// UPDATE default user settings
		param( 'enable_PM', 'integer', 0 );
		param( 'enable_email', 'integer', 0 );
		param( 'notify_messages', 'integer', 0 );
		param( 'notify_unread_messages', 'integer', 0 );
		param( 'notify_published_comments', 'integer', 0 );
		param( 'notify_comment_moderation', 'integer', 0 );
		param( 'notify_post_moderation', 'integer', 0 );
		param( 'newsletter_news', 'integer', 0 );
		param( 'newsletter_ads', 'integer', 0 );
		param_integer_range( 'notification_email_limit', 0, 999, T_('Notificaiton email limit must be between %d and %d.') );
		param_integer_range( 'newsletter_limit', 0, 999, T_('Newsletter limit must be between %d and %d.') );

		// UPDATE account activation by email
		param( 'newusers_mustvalidate', 'integer', 0 );
		param( 'newusers_revalidate_emailchg', 'integer', 0 );
		param( 'validation_process', 'string', true );
		$activate_requests_limit = param_duration( 'activate_requests_limit' );
		param( 'newusers_findcomments', 'integer', 0 );

		$after_email_validation = param( 'after_email_validation', 'string', 'return_to_original' );
		if( $after_email_validation != 'return_to_original' )
		{
			$after_email_validation = param( 'specific_after_validation_url', 'string', NULL );
			param_check_url( 'specific_after_validation_url', 'email_validation' );
		}

		$after_registration = param( 'after_registration', 'string', 'return_to_original' );
		if( $after_registration != 'return_to_original' )
		{
			$after_registration = param( 'specific_after_registration_url', 'string', NULL );
			param_check_url( 'specific_after_registration_url', 'after_registration' );
		}

		param_integer_range( 'user_minpwdlen', 1, 32, T_('Minimum password length must be between %d and %d.') );

		param( 'js_passwd_hashing', 'integer', 0 );
		param( 'passwd_special', 'integer', 0 );
		param( 'strict_logins', 'integer', 0 );
		param( 'registration_require_country', 'integer', 0 );
		param( 'registration_require_firstname', 'integer', 0 );
		param( 'registration_ask_locale', 'integer', 0 );
		param( 'registration_require_gender', 'string', '' );

		// We are about to allow non-ASCII logins
		// Let's check if there are users with logins starting with reserved prefix usr_
		if( ! $strict_logins && $invalid_users = find_logins_with_reserved_prefix() )
		{
			// Enforce strict logins until all invalid logins are changed
			$strict_logins = true;

			$user_edit_url = regenerate_url( 'ctrl,action', 'ctrl=user&amp;user_tab=profile&amp;user_ID=' );
			foreach( $invalid_users as $inv_user )
			{
				$msg[] = '<li>'.action_icon( T_('Edit this user...'), 'edit', $user_edit_url.$inv_user->user_ID, 5, 0 ).' [ '.$inv_user->user_login.' ]</li>';
			}

			if( !empty($msg) )
			{
				$Messages->add( T_('The following user logins must be changed in order for you to disable "Require strict logins" setting:')
							.'<ol style="list-style-type:decimal; list-style-position: inside">'.implode( "\n", $msg ).'</ol>', 'note' );
			}
		}

		$Settings->set_array( array(
					 array( 'newusers_canregister', $newusers_canregister ),
					 array( 'registration_is_public', $registration_is_public ),
					 array( 'newusers_grp_ID', $newusers_grp_ID ),
					 array( 'newusers_level', $newusers_level ),
					 array( 'def_enable_PM', $enable_PM ),
					 array( 'def_enable_email', $enable_email ),
					 array( 'def_notify_messages', $notify_messages ),
					 array( 'def_notify_unread_messages', $notify_unread_messages ),
					 array( 'def_notify_published_comments', $notify_published_comments ),
					 array( 'def_notify_comment_moderation', $notify_comment_moderation ),
					 array( 'def_notify_post_moderation', $notify_post_moderation ),
					 array( 'def_newsletter_news', $newsletter_news ),
					 array( 'def_newsletter_ads', $newsletter_ads ),
					 array( 'def_notification_email_limit', $notification_email_limit ),
					 array( 'def_newsletter_limit', $newsletter_limit ),
					 array( 'newusers_mustvalidate', $newusers_mustvalidate ),
					 array( 'newusers_revalidate_emailchg', $newusers_revalidate_emailchg ),
					 array( 'activate_requests_limit', $activate_requests_limit ),
					 array( 'validation_process', $validation_process ),
					 array( 'newusers_findcomments', $newusers_findcomments ),
					 array( 'after_email_validation', $after_email_validation ),
					 array( 'after_registration', $after_registration ),
					 array( 'user_minpwdlen', $user_minpwdlen ),
					 array( 'js_passwd_hashing', $js_passwd_hashing ),
					 array( 'passwd_special', $passwd_special ),
					 array( 'strict_logins', $strict_logins ),
					 array( 'registration_require_country', $registration_require_country ),
					 array( 'registration_require_firstname', $registration_require_firstname ),
					 array( 'registration_ask_locale', $registration_ask_locale ),
					 array( 'registration_require_gender', $registration_require_gender )
				) );

		if( ! $Messages->has_errors() )
		{
			if( $Settings->dbupdate() )
			{ // update was successful
				if( $old_newusers_canregister != $newusers_canregister )
				{ // invalidate all PageCaches
					invalidate_pagecaches();
				}
				$Messages->add( T_('General settings updated.'), 'success' );
				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( '?ctrl=registration', 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
		}

		break;
}


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=usersettings' );
$AdminUI->breadcrumbpath_add( T_('Registration'), '?ctrl=registration' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'users/views/_registration.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>