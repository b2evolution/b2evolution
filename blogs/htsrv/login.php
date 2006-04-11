<?php
/**
 * This is the login screen. It also handles actions related to loggin in and registering.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Matt FOLLETT grants Francois PLANQUE the right to license
 * Matt FOLLETT's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE
 * @author mfollett: Matt FOLLETT.
 *
 * @version $Id$
 */

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'_main.inc.php';

param( 'action', 'string', '' );
param( 'mode', 'string', '' );
// bookmarklet stuff:
param( 'text', 'html', '' );
param( 'popupurl', 'string', '' );
param( 'popuptitle', 'string', '' );

param( 'login', 'string', '', false, true ); // override
// echo 'login: ', $login;


switch( $action )
{
	case 'logout':
		logout();          // logout $Session and set $current_User = false

		// TODO: to give the user feedback through Messages, we would need to start a new $Session here and append $Messages to it.

		header_nocache();  // defaults to redirect_to param
		header_redirect(); // exits
		/* exited */


	case 'lostpassword': // Lost password:
		param( 'redirect_to', 'string', $admin_url );

		// Display retrieval form:
		require $view_path.'login/_lostpass_form.php';

		exit();


	case 'retrievepassword': // Send passwort change request by mail
		$login_required = true; // Do not display "Without login.." link on the form
		param( 'redirect_to', 'string', $admin_url );

		$ForgetfulUser = & $UserCache->get_by_login( $login );

		if( $ForgetfulUser )
		{ // User exists
			// echo 'email: ', $ForgetfulUser->email;
			// echo 'locale: '.$ForgetfulUser->locale;

			if( $demo_mode && ($ForgetfulUser->login == 'demouser' || $ForgetfulUser->ID == 1) )
			{
				$Messages->add( T_('You cannot reset this account in demo mode.'), 'error' );
				break;
			}

			locale_temp_switch( $ForgetfulUser->locale );

			// DEBUG!
			// echo $message.' (password not set yet, only when sending email does not fail);

			if( empty( $ForgetfulUser->email ) )
			{
				$Messages->add( T_('You have no email address with your profile, therefore we cannot reset your password.')
					.' '.T_('Please try contacting the admin.'), 'error' );
			}
			else
			{
				$request_id = generate_random_key(22);

				$message = T_( 'Somebody (presumably you) has requested a password change for your account.' )
					."\n\n"
					.T_('Login:')." $login\n"
					.T_('Link to change your password:')
					."\n"
					.$htsrv_url.'login.php?action=changepwd'
						.'&login='.rawurlencode( $ForgetfulUser->login )
						.'&reqID='.$request_id
						.'&sessID='.$Session->ID  // used to detect cookie problems
					."\n\n"
					.T_('Please note:')
					.' '.T_('For security reasons the link is only valid for your current session (by means of your session cookie).')
					."\n\n"
					.T_('If it was not you that requested this password change, simply ignore this mail.');

				if( ! send_mail( $ForgetfulUser->email, sprintf( T_('Password change request for %s'), $ForgetfulUser->login ), $message, $notify_from ) )
				{
					$Messages->add( T_('The email could not be sent.')
						.'<br />'.T_('Possible reason: your host may have disabled the mail() function...'), 'error' );
				}
				else
				{
					$Session->set( 'core.changepwd.request_id', $request_id, 86400 * 2 ); // expires in two days (or when clicked)
					$Session->dbsave(); // save immediately

					$Messages->add( T_('A link to change your password has been sent to your email address.' ), 'success' );
				}
			}

			locale_restore_previous();
		}
		else
		{ // pretend that the email is sent for avoiding guessing user_login
			$Messages->add( T_('A link to change your password has been sent to your email address.' ), 'success' );
		}
		break;


	case 'changepwd': // Clicked "Change password request" link from a mail
		param( 'redirect_to', 'string', $admin_url );
		param( 'reqID', 'string', '' );
		param( 'sessID', 'integer', '' );

		$ForgetfulUser = & $UserCache->get_by_login($login);

		if( ! $ForgetfulUser || empty($reqID) )
		{ // This was not requested
			$Messages->add( T_('Invalid password change request!'), 'error' );
			break;
		}

		if( $sessID != $Session->ID )
		{ // Another session ID than for requesting password change link used!
			$Messages->add( sprintf( T_('You will have to accept cookies in order to log in.') ), 'error' );
			break;
		}

		// Validate provided reqID against the one stored in the user's session
		if( $Session->get( 'core.changepwd.request_id' ) != $reqID )
		{
			$Messages->add( T_('Invalid password change request!'), 'error' );
			$Messages->add(
				sprintf( T_('You can <a href="%s">send yourself a new link</a>.'),
				$htsrv_url.'login.php?action=retrievepassword&amp;login='.$login.'&amp;redirect_to='.rawurlencode( $redirect_to ) ), 'note' );

			$login_required = true; // Do not display "Without login.." link on the form
			break;
		}

		// Link User to Session:
		$Session->set_user_ID( $ForgetfulUser->ID );

		// Add Message to change the password:
		$action_Log = & new Log();
		$action_Log->add( T_( 'Please change your password to something you remember now.' ), 'success' );
		$Session->set( 'Messages', $action_Log );
		$Session->dbsave();

		// Redirect to the user's profile in the "users" controller:
		header_nocache();
		header_redirect( url_add_param( $admin_url, 'ctrl=users&user_ID='.$ForgetfulUser->ID, '&' ) ); // display user's profile

		exit();

		break;

} // switch


// Default: login form
require $view_path.'login/_login_form.php';
exit();

/*
 * $Log$
 * Revision 1.52  2006/04/11 21:22:25  fplanque
 * partial cleanup
 *
 */
?>