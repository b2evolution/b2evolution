<?php
/**
 * This is the login screen. It also handles actions related to loggin in and registering.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
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

$Request->param( 'action', 'string', '' );
$Request->param( 'mode', 'string', '' );
// bookmarklet stuff:
$Request->param( 'text', 'html', '' );
$Request->param( 'popupurl', 'string', '' );
$Request->param( 'popuptitle', 'string', '' );

$Request->param( 'login', 'string', '' );
// echo 'login: ', $login;

$Request->param( 'redirect_to', 'string', '' ); // gets used by header_redirect(); if appropriate (perms) we let it default to $admin_url


switch( $action )
{
	case 'logout':
		logout();          // logout $Session and set $current_User = false

		// TODO: to give the user feedback through Messages, we would need to start a new $Session here and append $Messages to it.

		header_nocache();
		header_redirect(); // defaults to redirect_to param and exits
		/* exited */


	case 'lostpassword': // Lost password:
		// Display retrieval form:
		require $view_path.'login/_lostpass_form.php';

		exit();


	case 'retrievepassword': // Send passwort change request by mail
		$login_required = true; // Do not display "Without login.." link on the form

		$ForgetfulUser = & $UserCache->get_by_login( $login );

		if( ! $ForgetfulUser )
		{ // User does not exist
			// pretend that the email is sent for avoiding guessing user_login
			$Messages->add( T_('A link to change your password has been sent to your email address.' ), 'success' );
			break;
		}

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
				.$htsrv_url_sensitive.'login.php?action=changepwd'
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
				$Messages->add( T_('Sorry, the email with the link to reset your password could not be sent.')
					.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
			}
			else
			{
				$Session->set( 'core.changepwd.request_id', $request_id, 86400 * 2 ); // expires in two days (or when clicked)
				$Session->dbsave(); // save immediately

				$Messages->add( T_('A link to change your password has been sent to your email address.' ), 'success' );
			}
		}

		locale_restore_previous();

		break;


	case 'changepwd': // Clicked "Change password request" link from a mail
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
			$Messages->add( T_('You have to use the same session (by means of your session cookie) as when you have requested the action.'), 'error' );
			break;
		}

		// Validate provided reqID against the one stored in the user's session
		if( $Session->get( 'core.changepwd.request_id' ) != $reqID )
		{
			$Messages->add( T_('Invalid password change request!'), 'error' );
			$Messages->add(
				sprintf( T_('You can <a href="%s">send yourself a new link</a>.'),
				$htsrv_url_sensitive.'login.php?action=retrievepassword&amp;login='.rawurlencode($login) ), 'note' );

			$login_required = true; // Do not display "Without login.." link on the form
			break;
		}

		// Link User to Session:
		$Session->set_user_ID( $ForgetfulUser->ID );

		// Add Message to change the password:
		$Messages->add( T_( 'Please change your password to something you remember now.' ), 'success' );
		$Session->set( 'Messages', $Messages );
		$Session->dbsave(); // If we don't save now, we run the risk that the redirect goes faster than the PHP script shutdown.

		// Note: the 'core.changepwd.request_id' Session setting gets removed in b2users.php

		// Redirect to the user's profile in the "users" controller:
		// TODO: This will probably fail if the user has no admin-access permission! Redirect to profile page in blog instead!?
		header_nocache();
		header_redirect( url_add_param( $admin_url, 'ctrl=users&user_ID='.$ForgetfulUser->ID, '&' ) ); // display user's profile

		exit();

		break;


	case 'req_validatemail': // Send email validation link by mail (initial form and action)
		if( ! $Settings->get('newusers_mustvalidate') )
		{ // validating emails is not activated/necessary
			break;
		}

		if( ! is_logged_in() )
		{
			$Messages->add( T_('You have to be logged in to request an account validation link.'), 'error' );
			break;
		}

		$Request->param( 'req_validatemail_submit', 'integer', 0 ); // has the form been submitted
		$Request->param( 'email', 'string', $current_User->email ); // the email address is editable

		if( $req_validatemail_submit )
		{ // Form has been submitted
			$Request->param_check_email( 'email', true );

			// Call plugin event to allow catching input in general and validating own things from DisplayRegisterFormFieldset event
			$Plugins->trigger_event( 'ValidateAccountFormSent' );

			if( $Messages->count('error') )
			{
				break;
			}

			// Update user's email:
			$current_User->set_email( $email );
			if( $current_User->dbupdate() )
			{
				$Messages->add( T_('Your profile has been updated.'), 'note' );
			}

			if( $current_User->send_validate_email() )
			{
				$Messages->add( sprintf( /* TRANS: %s gets replaced by the user's email address */ T_('An email has been sent to your email address (%s). Please click on the link therein to validate your account.'), $current_User->dget('email') ), 'success' );
			}
			else
			{
				$Messages->add( T_('Sorry, the email with the link to validate and activate your password could not be sent.')
							.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
			}
		}
		else
		{ // Form not yet submitted:
			if( empty($current_User->email) )
			{ // add (error) note to be displayed in the form
				$Messages->add( T_('You have no email address with your profile, therefore we cannot validate it. Please give your email address below.'), 'error' );
			}
		}

		// Display retrieval form:
		require $view_path.'login/_validate_form.php';

		exit();
		break;


	case 'validatemail': // Clicked "Validate email" link from a mail
		param( 'reqID', 'string', '' );
		param( 'sessID', 'integer', '' );

		if( ! is_logged_in() )
		{
			$Messages->add( T_('You have to be logged in to validate your account.'), 'error' );
			break;
		}

		if( empty($reqID) )
		{ // This was not requested
			$Messages->add( T_('Invalid email address validation request!'), 'error' );
			break;
		}

		if( $sessID != $Session->ID )
		{ // Another session ID than for requesting account validation link used!
			$Messages->add( T_('You have to use the same session (by means of your session cookie) as when you have requested the action.'), 'error' );
			break;
		}

		// Validate provided reqID against the one stored in the user's session
		if( $Session->get( 'core.validatemail.request_id' ) != $reqID )
		{
			$Messages->add( T_('Invalid email address validation request!'), 'error' );
			$Messages->add(
				sprintf( T_('You can <a href="%s">send yourself a new link</a>.'),
				$htsrv_url_sensitive.'login.php?action=req_validatemail' ), 'note' );

			$login_required = true; // Do not display "Without login.." link on the form
			break;
		}

		$current_User->set( 'validated', 1 );
		$current_User->dbupdate();

		$Session->delete( 'core.validatemail.request_id' );

		$Messages->add( T_( 'Your email address has been validated.' ), 'success' );

		if( empty($redirect_to) && $current_User->check_perm('admin') )
		{ // User can access backoffice
			$redirect_to = $admin_url;
		}

		$Session->set( 'Messages', $Messages );
		$Session->dbsave(); // If we don't save now, we run the risk that the redirect goes faster than the PHP script shutdown.

		header_nocache();
		header_redirect();
		exit();

		break;

} // switch


if( empty($redirect_to) )
{ // Use requested URI if nothing provided
	$redirect_to = str_replace( '&', '&amp;', $ReqURI );
}

if( preg_match( '#/login.php([&?].*)?$#', $redirect_to ) )
{ // avoid "endless loops"
	$redirect_to = str_replace( '&', '&amp;', $admin_url );
}

// Remove login and pwd parameters from URL, so that they do not trigger the login screen again:
$redirect_to = preg_replace( '~(?<=\?|&amp;|&) (login|pwd) = [^&]+ (&(amp;)?|\?)?~x', '', $redirect_to );

if( $Session->has_User() )
{ // The user is already logged in...
	$tmp_User = & $Session->get_User();
	if( $tmp_User->validated || ! $Settings->get('newusers_mustvalidate') )
	{
		$Messages->add( sprintf( T_('Note: You are already logged in as %s!'), $tmp_User->get('login') )
			.' <a href="'.$redirect_to.'">'.T_('Continue...').'</a>', 'note' );
	}
	unset($tmp_User);
}

if( strpos( $redirect_to, str_replace('&amp;', '&', $admin_url) ) === 0 )
{ // don't provide link to bypass
	$login_required = true;
}

$Debuglog->add( 'redirect_to: '.$redirect_to );


// Default: login form
require $view_path.'login/_login_form.php';
exit();


/*
 * $Log$
 * Revision 1.63  2006/06/25 23:34:15  blueyed
 * wording pt2
 *
 * Revision 1.62  2006/06/25 23:23:38  blueyed
 * wording
 *
 * Revision 1.61  2006/06/22 22:30:04  blueyed
 * htsrv url for password related scripts (login, register and profile update)
 *
 * Revision 1.60  2006/05/19 18:15:04  blueyed
 * Merged from v-1-8 branch
 *
 * Revision 1.59.2.1  2006/05/19 15:06:23  fplanque
 * dirty sync
 *
 * Revision 1.59  2006/05/05 21:47:42  blueyed
 * consistency
 *
 * Revision 1.58  2006/04/24 20:52:30  fplanque
 * no message
 *
 * Revision 1.57  2006/04/22 02:54:37  blueyed
 * Fixes: Always go to validatemail form; delete used request ID
 *
 * Revision 1.56  2006/04/22 02:36:38  blueyed
 * Validate users on registration through email link (+cleanup around it)
 *
 * Revision 1.55  2006/04/20 12:15:32  fplanque
 * no message
 *
 * Revision 1.54  2006/04/19 23:50:39  blueyed
 * Normalized Messages handling (error displaying and transport in Session)
 *
 * Revision 1.53  2006/04/19 20:13:48  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.52  2006/04/11 21:22:25  fplanque
 * partial cleanup
 *
 */
?>