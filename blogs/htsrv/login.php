<?php
/**
 * This is the login screen. It also handles actions related to loggin in and registering.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
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

param( 'action', 'string', 'req_login' );
param( 'mode', 'string', '' );
param( 'login', 'string', '' );
// echo 'login: ', $login;

// gets used by header_redirect();
// TODO: dh> problem here is that $ReqURI won't include the e.g. "ctrl" param in a POSTed form and therefor the user lands on the default admin page after logging in (again)
// fp> I think this will fix itself when we do another improvement: 303 redirect after each POST so that we never have an issue with people trying to reload a post
param( 'redirect_to', 'string', $ReqURI );

switch( $action )
{
	case 'logout':
		logout();          // logout $Session and set $current_User = NULL

		// TODO: to give the user feedback through Messages, we would need to start a new $Session here and append $Messages to it.

		header_nocache();
		header_redirect(); // defaults to redirect_to param and exits
		/* exited */
		break;


	case 'retrievepassword': // Send passwort change request by mail
		$login_required = true; // Do not display "Without login.." link on the form

		$UserCache = & get_UserCache();
		$ForgetfulUser = & $UserCache->get_by_login( $login );

		if( ! $ForgetfulUser )
		{ // User does not exist
			// pretend that the email is sent for avoiding guessing user_login
			$Messages->add( T_('If you correctly typed in your login, a link to change your password has been sent to your registered email address.' ), 'success' );
			$action = 'req_login';
			break;
		}

		// echo 'email: ', $ForgetfulUser->email;
		// echo 'locale: '.$ForgetfulUser->locale;

		if( $demo_mode && ($ForgetfulUser->login == 'demouser' || $ForgetfulUser->ID == 1) )
		{
			$Messages->add( T_('You cannot reset this account in demo mode.'), 'error' );
			$action = 'req_login';
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
			$request_id = generate_random_key(22); // 22 to make it not too long for URL but unique/safe enough

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

			if( ! send_mail( $ForgetfulUser->email, NULL, sprintf( T_('Password change request for %s'), $ForgetfulUser->login ), $message, $notify_from ) )
			{
				$Messages->add( T_('Sorry, the email with the link to reset your password could not be sent.')
					.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
			}
			else
			{
				$Session->set( 'core.changepwd.request_id', $request_id, 86400 * 2 ); // expires in two days (or when clicked)
				$Session->dbsave(); // save immediately

				$Messages->add( T_('If you correctly typed in your login, a link to change your password has been sent to your registered email address.' ), 'success' );
			}
		}

		locale_restore_previous();

		$action = 'req_login';
		break;


	case 'changepwd': // Clicked "Change password request" link from a mail
		param( 'reqID', 'string', '' );
		param( 'sessID', 'integer', '' );

		$UserCache = & get_UserCache();
		$ForgetfulUser = & $UserCache->get_by_login($login);

		if( ! $ForgetfulUser || empty($reqID) )
		{ // This was not requested
			$Messages->add( T_('Invalid password change request! Please try again...'), 'error' );
			$action = 'lostpassword';
			$login_required = true; // Do not display "Without login.." link on the form
			break;
		}

		if( $sessID != $Session->ID )
		{ // Another session ID than for requesting password change link used!
			$Messages->add( T_('You have to use the same session (by means of your session cookie) as when you have requested the action. Please try again...'), 'error' );
			$action = 'lostpassword';
			$login_required = true; // Do not display "Without login.." link on the form
			break;
		}

		// Validate provided reqID against the one stored in the user's session
		if( $Session->get( 'core.changepwd.request_id' ) != $reqID )
		{
			$Messages->add( T_('Invalid password change request! Please try again...'), 'error' );
			$action = 'lostpassword';
			$login_required = true; // Do not display "Without login.." link on the form
			break;
		}

		// Link User to Session:
		$Session->set_user_ID( $ForgetfulUser->ID );

		// Add Message to change the password:
		$Messages->add( T_( 'Please change your password to something you remember now.' ), 'success' );

		// Note: the 'core.changepwd.request_id' Session setting gets removed in b2users.php

		// Redirect to the user's profile in the "users" controller:
		// TODO: This will probably fail if the user has no admin-access permission! Redirect to profile page in blog instead!?
		header_nocache();
		// redirect Will save $Messages into Session:
		header_redirect( url_add_param( $admin_url, 'ctrl=users&user_ID='.$ForgetfulUser->ID, '&' ) ); // display user's profile
		/* exited */
		break;


	case 'validatemail': // Clicked "Validate email" link from a mail
		param( 'reqID', 'string', '' );
		param( 'sessID', 'integer', '' );

		if( is_logged_in() && $current_User->validated )
		{ // Already validated, e.g. clicked on an obsolete email link:
			$Messages->add( T_('Your account has already been validated.'), 'note' );
			// no break: cleanup & redirect below
		}
		else
		{
			// Check valid format:
			if( empty($reqID) )
			{ // This was not requested
				$Messages->add( T_('Invalid email address validation request!'), 'error' );
				$action = 'req_validatemail';
				break;
			}

			// Check valid session (format only, meant as help for the user):
			if( $sessID != $Session->ID )
			{ // Another session ID than for requesting account validation link used!
				$Messages->add( T_('You have to use the same session (by means of your session cookie) as when you have requested the action. Please try again...'), 'error' );
				$action = 'req_validatemail';
				break;
			}

			// Validate provided reqID against the one stored in the user's session
			$request_ids = $Session->get( 'core.validatemail.request_ids' );
			if( ( ! is_array($request_ids) || ! in_array( $reqID, $request_ids ) )
				&& ! ( isset($current_User) && $current_User->group_ID == 1 && $reqID == 1 /* admin users can validate themselves by a button click */ ) )
			{
				$Messages->add( T_('Invalid email address validation request!'), 'error' );
				$action = 'req_validatemail';
				$login_required = true; // Do not display "Without login.." link on the form
				break;
			}

			if( ! is_logged_in() )
			{ // this can happen, if a new user registers and clicks on the "validate by email" link, without logging in first
				// Note: we reuse $reqID and $sessID in the form to come back here.

				$Messages->add( T_('Please login to validate your account.'), 'error' );
				break;
			}

			// Validate user:

			$current_User->set( 'validated', 1 );
			$current_User->dbupdate();

			$Messages->add( T_( 'Your email address has been validated.' ), 'success' );
		}

		$redirect_to = $Session->get( 'core.validatemail.redirect_to' );

		if( empty($redirect_to) && $current_User->check_perm('admin') )
		{ // User can access backoffice
			$redirect_to = $admin_url;
		}

		// Cleanup:
		$Session->delete('core.validatemail.request_ids');
		$Session->delete('core.validatemail.redirect_to');

		header_nocache();
		// redirect Will save $Messages into Session:
		header_redirect();
		/* exited */
		break;

} // switch( $action ) (1st)



/* For actions that other delegate to from the switch above: */
switch( $action )
{
	case 'req_validatemail': // Send email validation link by mail (initial form and action)
		if( ! is_logged_in() )
		{
			$Messages->add( T_('You have to be logged in to request an account validation link.'), 'error' );
			$action = '';
			break;
		}

		if( $current_User->validated || ! $Settings->get('newusers_mustvalidate') )
		{ // validating emails is not activated/necessary (check this after login, so it gets not "announced")
			$action = '';
			break;
		}

		param( 'req_validatemail_submit', 'integer', 0 ); // has the form been submitted
		param( 'email', 'string', $current_User->email ); // the email address is editable

		if( $req_validatemail_submit )
		{ // Form has been submitted
			param_check_email( 'email', true );

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

			if( $current_User->send_validate_email($redirect_to) )
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
			// Add a note, if we have already sent validation links:
			$request_ids = $Session->get( 'core.validatemail.request_ids' );
			if( is_array($request_ids) && count($request_ids) )
			{
				$Messages->add( sprintf( T_('We have already sent you %d email(s) with a validation link.'), count($request_ids) ), 'note' );
			}

			if( empty($current_User->email) )
			{ // add (error) note to be displayed in the form
				$Messages->add( T_('You have no email address with your profile, therefore we cannot validate it. Please give your email address below.'), 'error' );
			}
		}
		break;
}


if( ! defined( 'EVO_MAIN_INIT' ) )
{	// Do not check this if the form was included inside of _main.inc
	// echo $htsrv_url_sensitive.'login.php';
	// echo '<br>'.$ReqHost.$ReqPath;
	if( $ReqHost.$ReqPath != $htsrv_url_sensitive.'login.php' )
	{
		$Messages->add( sprintf( T_('WARNING: you are trying to log in on <strong>%s</strong> but we expect you to log in on <strong>%s</strong>. If this is due to an automatic redirect, this will prevent you from successfully loging in. You must either fix your webserver configuration, or your %s configuration in order for these two URLs to match.'), $ReqHost.$ReqPath, $htsrv_url_sensitive.'login.php', $app_name ), 'error' );
	}
}


// Test if cookie_domain matches the URL where we want to redirect to:
if( strlen($redirect_to) )
{
	// Make it relative to the form's target, in case it has been set absolute (and can be made relative).
	// Just in case it gets sent absolute. This should not trigger this warning then..!
	$redirect_to = url_rel_to_same_host($redirect_to, $htsrv_url_sensitive);

	$cookie_domain_match = false;
	if( $redirect_to[0] == '/' )
	{ // relative => ok
		$cookie_domain_match = true;
	}
	else
	{
		$parsed_redirect_to = @parse_url($redirect_to);
		if( isset($parsed_redirect_to['host']) )
		{
			if( $cookie_domain == $parsed_redirect_to['host'] )
			{
				$cookie_domain_match = true;
			}
			elseif( $cookie_domain[0] == '.'
				&& substr($cookie_domain,1) == substr($parsed_redirect_to['host'], 1-strlen($cookie_domain)) )
			{ // cookie domain includes subdomains and matches the last part of where we want to redirect to
				$cookie_domain_match = true;
			}
		}
		else
		{
			$cookie_domain_match = preg_match( '#^https?://[a-z\-.]*'.str_replace( '.', '\.', $cookie_domain ).'#i', $redirect_to );
		}
	}
	if( ! $cookie_domain_match )
	{
		$Messages->add( sprintf( T_('WARNING: you are trying to log in to <strong>%s</strong> but your cookie domain is <strong>%s</strong>. You will not be able to successfully log in to the requested domain until you fix your cookie domain in your %s configuration.'), $redirect_to, $cookie_domain, $app_name ), 'error' );
	}
}


if( preg_match( '#/login.php([&?].*)?$#', $redirect_to ) )
{ // avoid "endless loops"
	$redirect_to = $admin_url;
}

// Remove login and pwd parameters from URL, so that they do not trigger the login screen again:
$redirect_to = preg_replace( '~(?<=\?|&) (login|pwd) = [^&]+ ~x', '', $redirect_to );
$Debuglog->add( 'redirect_to: '.$redirect_to );


/**
 * Display:
 */
switch( $action )
{
	case 'lostpassword':
		// Lost password:
		// Display retrieval form:
		require $adminskins_path.'login/_lostpass_form.main.php';
		break;

	case 'req_validatemail':
		// Send email validation link by mail (initial form and action)
		// Display validation form:
		require $adminskins_path.'login/_validate_form.main.php';
		break;

	default:
		// Display login form
		require $adminskins_path.'login/_login_form.main.php';
}

exit(0);


/*
 * $Log$
 * Revision 1.108  2009/09/26 12:00:42  tblue246
 * Minor/coding style
 *
 * Revision 1.107  2009/09/25 07:32:51  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.106  2009/05/28 20:57:22  blueyed
 * Rolling back additional activation of locale in htsrv (register, login). http://forums.b2evolution.net/viewtopic.php?p=92006#92006
 *
 * Revision 1.105  2009/03/08 23:57:36  fplanque
 * 2009
 *
 * Revision 1.104  2009/02/24 00:46:11  sam2kb
 * Activate the default locale - same as on reg page
 * See http://forums.b2evolution.net/viewtopic.php?t=13770
 *
 * Revision 1.103  2009/02/23 07:59:46  sam2kb
 * Activate default locale
 *
 * Revision 1.102  2008/12/21 18:43:11  blueyed
 * Fix E_NOTICE with new cookie_domain handling
 *
 * Revision 1.101  2008/12/21 17:51:23  blueyed
 * Merge fix for cookie domain warning from whissip branch. This fixes the warning for cookie_domain=.example.com when logging into example.com.
 *
 * Revision 1.100  2008/04/13 15:15:59  fplanque
 * attempt to fix email headers for non latin charsets
 *
 * Revision 1.99  2008/02/19 11:11:16  fplanque
 * no message
 *
 * Revision 1.98  2008/01/21 09:35:23  fplanque
 * (c) 2008
 *
 * Revision 1.97  2008/01/14 23:41:48  fplanque
 * cleanup load_funcs( urls ) in main because it is ubiquitously used
 *
 * Revision 1.96  2008/01/06 16:42:27  blueyed
 * Fix call to undefined function when accessing admin.php and _url.funcs.php has not been loaded
 *
 * Revision 1.95  2007/12/10 01:05:23  blueyed
 * Improve check performance
 *
 * Revision 1.94  2007/06/25 10:58:49  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.93  2007/05/15 18:35:03  blueyed
 * Use the same string when faking a success message!
 *
 * Revision 1.92  2007/04/26 00:11:14  fplanque
 * (c) 2007
 *
 * Revision 1.91  2007/02/26 03:41:16  fplanque
 * doc
 *
 * Revision 1.90  2007/02/21 23:52:26  fplanque
 * doc
 *
 * Revision 1.89  2007/02/21 21:16:14  blueyed
 * todo
 *
 * Revision 1.88  2007/02/13 21:03:40  blueyed
 * Improved login/register/validation process:
// So seriously now:  "been validated already" and then "already been validated" on the same line!!! I don't think this is funny any longer. ("already been" is better)
 * - "Your account has been validated already." if an account had already been validated
 * - "We have already sent you %d email(s) with a validation link." note
 * - Autologin the user after he has registered (he just typed his credentials!)
 *
 * Revision 1.87  2007/02/03 19:48:55  blueyed
 * Fixed possible E_NOTICE
 *
 * Revision 1.86  2007/01/26 18:40:43  blueyed
 * Saner order of validate-email-link error message handling.
 *
 * Revision 1.85  2007/01/19 03:06:57  fplanque
 * Changed many little thinsg in the login procedure.
 * There may be new bugs, sorry. I tested this for several hours though.
 * More refactoring to be done.
 *
 * Revision 1.84  2007/01/18 23:59:29  fplanque
 * Re: Secunia. Proper sanitization.
 *
 * Revision 1.82  2007/01/17 23:54:54  blueyed
 * fixed "empty $redirect_to" regression
 *
 * Revision 1.81  2006/12/28 19:18:49  fplanque
 * trap yet another login/cookie caveat
 *
 * Revision 1.80  2006/12/28 15:44:31  fplanque
 * login refactoring / simplified
 *
 * Revision 1.79  2006/12/06 23:25:32  blueyed
 * Fixed bookmarklet plugins (props Danny); removed unneeded bookmarklet handling in core
 *
 * Revision 1.78  2006/12/06 22:30:07  fplanque
 * Fixed this use case:
 * Users cannot register themselves.
 * Admin creates users that are validated by default. (they don't have to validate)
 * Admin can invalidate a user. (his email, address actually)
 *
 * Revision 1.77  2006/11/26 02:30:38  fplanque
 * doc / todo
 *
 * Revision 1.76  2006/11/24 18:27:22  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.75  2006/11/24 18:06:02  blueyed
 * Handle saving of $Messages centrally in header_redirect()
 *
 * Revision 1.74  2006/10/23 22:19:02  blueyed
 * Fixed/unified encoding of redirect_to param. Use just rawurlencode() and no funky &amp; replacements
 *
 * Revision 1.73  2006/10/12 23:48:15  blueyed
 * Fix for if redirect_to is relative
 *
 * Revision 1.72  2006/08/21 19:07:52  blueyed
 * doc
 *
 * Revision 1.71  2006/08/21 16:07:43  fplanque
 * refactoring
 *
 * Revision 1.70  2006/08/20 22:25:20  fplanque
 * param_() refactoring part 2
 *
 * Revision 1.69  2006/08/19 07:56:29  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.68  2006/07/26 20:19:15  blueyed
 * Set $current_User = NULL on logout (not false!)
 *
 * Revision 1.67  2006/07/17 01:33:13  blueyed
 * Fixed account validation by email for users who registered themselves
 *
 * Revision 1.66  2006/07/08 17:04:18  fplanque
 * minor
 *
 * Revision 1.65  2006/07/08 13:33:54  blueyed
 * Autovalidate admin group instead of primary admin user only.
 * Also delegate to req_validatemail action on failure directly instead of providing a link.
 *
 * Revision 1.64  2006/07/04 23:38:08  blueyed
 * Validate email: admin user (#1) has an extra button to validate him/herself through the form; store multiple req_validatemail keys in the user's session.
 *
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
