<?php
/**
 * This is the login screen. It also handles actions related to loggin in and registering.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package htsrv
 */

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'_main.inc.php';

$login = param( $dummy_fields['login'], 'string', '' );
param_action( 'req_login' );
param( 'mode', 'string', '' );
param( 'inskin', 'boolean', false );
if( $inskin )
{
	param( 'blog', 'integer', NULL );
}

// gets used by header_redirect();
param( 'redirect_to', 'url', $ReqURI );

switch( $action )
{
	case 'logout':
		logout();          // logout $Session and set $current_User = NULL

		// TODO: to give the user feedback through Messages, we would need to start a new $Session here and append $Messages to it.

		// Redirect to $baseurl on logout if redirect URI is not set. Temporarily fix until we remove actions from redirect URIs
		if( $redirect_to == $ReqURI )
		{
			$redirect_to = $baseurl;
		}

		header_redirect(); // defaults to redirect_to param and exits
		/* exited */
		break;

	case 'closeaccount': // close user account and log out
		global $Session, $Messages, $UserSettings;
		$Session->assert_received_crumb( 'closeaccountform' );

		$reasons = trim( $Settings->get( 'account_close_reasons' ) );
		param( 'account_close_type', 'string', '' );
		if( ! empty( $reasons ) && empty( $account_close_type ) )
		{ // Don't submit a form without a selected reason
			$Messages->add( T_( 'Please quickly select a reason for closing your account.' ) );
			// Redirect to show the errors:
			header_redirect(); // Will EXIT
			// We have EXITed already at this point!!
		}

		if( is_logged_in() && $current_User->check_perm( 'users', 'edit', false ) )
		{ // Admins cannot close own accounts
			$Messages->add( T_( 'You cannot close your own account!' ) );
			// Redirect to show the errors:
			header_redirect(); // Will EXIT
			// We have EXITed already at this point!!
		}

		if( is_logged_in() && $current_User->update_status_from_Request( true, 'closed' ) )
		{ // user account was closed successful
			// Send notification email about closed account to users with edit users permission
			$email_template_params = array(
					'login'      => $current_User->login,
					'email'      => $current_User->email,
					'reason'     => trim( param( 'account_close_type', 'string', '' ).' '.param( 'account_close_reason', 'text', '' ) ),
					'user_ID'    => $current_User->ID,
					'days_count' => $current_User->get_days_count_close()
				);
			send_admin_notification( NT_('User account closed'), 'account_closed', $email_template_params );

			// Set this session var only to know when display a bye message
			$Session->set( 'account_closing_success', true );
		}
		else
		{ // db update was unsuccessful
			$Messages->add( T_( 'Unable to close your account. Please contact to system administrator.' ) );
		}

		header_redirect();
		/* exited */
		break;

	case 'retrievepassword': // Send password change request by mail
		global $servertimenow;
		$login_required = true; // Do not display "Without login.." link on the form

		if( empty( $login ) )
		{ // Don't allow empty request
			param_error( $dummy_fields['login'], T_('You must enter your username or your email address so we know where to send the password recovery email.'), '' );
			// Set this var to know after redirection if error was here
			$lostpassword_error = true;
			$action = 'lostpassword';
			break;
		}

		$request_ts_login = $Session->get( 'core.changepwd.request_ts_login' );
		if( $request_ts_login != NULL )
		{
			list( $last_request_ts, $last_request_login ) = preg_split( '~_~', $request_ts_login );
			if( ( $login == $last_request_login ) && ( ( $servertimenow - $pwdchange_request_delay ) < $last_request_ts ) )
			{ // the same request was sent from the same session in the last $pwdchange_request_delay seconds ( 5 minutes by default )
				$Messages->add( sprintf( T_('We have already sent you a password recovery email at %s. Please allow %d minutes for delivery before requesting a new one.' ), date( locale_datetimefmt(), $last_request_ts ), $pwdchange_request_delay / 60 ) );
				$action = 'req_login';
				break;
			}
		}

		$UserCache = & get_UserCache();
		$UserCache->clear();
		if( is_email( $login ) )
		{ // user gave an email, get users by email
			$only_activated = false;
			// load all not closed users with this email address
			$login = utf8_strtolower( $login );
			$UserCache->load_where( 'user_email = "'.$login.'" && user_status <> "closed"' );

			$not_activated_Ids = array();
			while( ( $iterator_User = & $UserCache->get_next() ) != NULL )
			{ // Iterate through UserCache
				if( $iterator_User->check_status( 'is_validated' ) )
				{
					$only_activated = true;
				}
				else
				{ // strore not activated user Ids for further use
					$not_activated_Ids[] = $iterator_User->ID;
				}
			}

			// if we have activated users then remove every not activated from the cache
			if( $only_activated && ( !empty( $not_activated_Ids ) ) )
			{
				foreach( $not_activated_Ids as $not_activated_Id )
				{
					$UserCache->remove_by_ID( $not_activated_Id );
				}
			}

			$UserCache->rewind();
			$forgetful_User = & $UserCache->get_next();
			$UserCache->rewind();
		}
		else
		{ // get user by login
			$forgetful_User = & $UserCache->get_by_login( $login );
		}

		if( ! $forgetful_User )
		{ // User does not exist
			// pretend that the email is sent for avoiding guessing user_login
			$Messages->add( T_('If you correctly entered your login or email address, a link to change your password has been sent to your registered email address.' ), 'success' );
			$action = 'req_login';
			break;
		}

		// echo 'email: ', $forgetful_User->email;
		// echo 'locale: '.$forgetful_User->locale;

		if( $demo_mode && ($forgetful_User->ID <= 3) )
		{
			$Messages->add( T_('You cannot reset this account in demo mode.'), 'error' );
			$action = 'req_login';
			break;
		}

		locale_temp_switch( $forgetful_User->locale );

		// DEBUG!
		// echo $message.' (password not set yet, only when sending email does not fail);

		if( empty( $forgetful_User->email ) )
		{
			$Messages->add( T_('You have no email address with your profile, therefore we cannot reset your password.')
				.' '.T_('Please try contacting the admin.'), 'error' );
		}
		else
		{
			$request_id = generate_random_key(22); // 22 to make it not too long for URL but unique/safe enough

			// Count how many users match to this login ( It can be more then one in case of email login )
			$user_ids = $UserCache->get_ID_array();
			$user_count = count( $user_ids );

			// Set blog param for email link
			$blog_param = '';
			if( !empty( $blog ) )
			{
				$blog_param = '&inskin=true&blog='.$blog;
			}

			$subject = sprintf( T_( 'Password change request for %s' ), $login );
			$email_template_params = array(
					'user_count'     => $user_count,
					'request_id'     => $request_id,
					'blog_param'     => $blog_param,
				);

			if( ! send_mail_to_User( $forgetful_User->ID, $subject, 'account_password_reset', $email_template_params, true ) )
			{
				$Messages->add( T_('Sorry, the email with the link to reset your password could not be sent.')
					.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
			}
			else
			{
				$Session->set( 'core.changepwd.request_id', $request_id, 86400 * 2 ); // expires in two days (or when clicked)
				$Session->set( 'core.changepwd.request_ts_login', $servertimenow.'_'.$login, 360 ); // request timestamp and login/email - expires in six minutes
				$Session->dbsave(); // save immediately

				$Messages->add( T_('If you correctly entered your login or email address, a link to change your password has been sent to your registered email address.' ), 'success' );

				syslog_insert( 'User requested password reset', 'info', 'user', $forgetful_User->ID );
			}
		}

		locale_restore_previous();

		$action = 'req_login';
		break;


	case 'changepwd': // Clicked "Change password request" link from a mail
		param( 'reqID', 'string', '' );
		param( 'sessID', 'integer', '' );

		$UserCache = & get_UserCache();
		$forgetful_User = & $UserCache->get_by_login( $login );

		locale_temp_switch( $forgetful_User->locale );

		if( ! $forgetful_User || empty( $reqID ) )
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

		// Link User to Session and Log in:
		$Session->set_user_ID( $forgetful_User->ID );
		$current_User = & $forgetful_User;

		// Add Message to change the password:
		$Messages->add( T_( 'Please change your password to something you remember now.' ), 'success' );

		// Note: the 'core.changepwd.request_id' Session setting gets removed in b2users.php

		// Redirect to the user's change password tab
		$changepwd_url = NULL;
		if( !empty( $blog ) )
		{ // blog is set, redirect to in-skin change password form
			$BlogCache = & get_BlogCache();
			$Blog = $BlogCache->get_by_ID( $blog );
			if( $Blog )
			{
				$changepwd_url = $Blog->get( 'userurl', array( 'url_suffix' => 'disp=pwdchange&reqID='.$reqID, 'glue' => '&' ) );
			}
		}

		locale_restore_previous();

		if( empty( $changepwd_url ) )
		{ // Display standard(non-skin) form to change password
			$action = 'changepwd';
		}
		else
		{ // redirect Will save $Messages into Session:
			header_redirect( $changepwd_url ); // display user's change password tab
			/* exited */
		}
		break;


	case 'updatepwd':
		// Update password(The submit action of the change password form)
		param( 'reqID', 'string', '' );

		if( ! is_logged_in() )
		{ // Don't allow not logged in user here, because it must be logged in on the action 'changepwd' above
			$Messages->add( T_('Invalid password change request! Please try again...'), 'error' );
			$action = 'lostpassword';
			$login_required = true; // Do not display "Without login.." link on the form
			break;
		}

		$forgetful_User = & $current_User;

		locale_temp_switch( $forgetful_User->locale );

		if( ! $forgetful_User || empty( $reqID ) )
		{ // This was not requested
			$Messages->add( T_('Invalid password change request! Please try again...'), 'error' );
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

		$result = $forgetful_User->update_from_request();

		if( $result !== true )
		{ // Some errors exist on form submit, Display the form again to change them
			$action = 'changepwd';
			break;
		}

		locale_restore_previous();

		// redirect Will save $Messages into Session:
		header_redirect( $baseurl ); // display user's change password tab
		/* exited */
		break;


	case 'activateaccount': // Clicked to activate account link from an account activation reminder email
		// Stop a request from the blocked IP addresses or Domains
		antispam_block_request();

		global $UserSettings, $Session, $baseurl;

		// get user id and reminder key
		$userID = param( 'userID', 'integer', '' );
		$reminder_key = param( 'reminderKey', 'string', '' );

		$UserCache = & get_UserCache();
		$User = $UserCache->get_by_ID( $userID );
		$last_reminder_key = $UserSettings->get( 'last_activation_reminder_key', $userID );
		if( !$User->check_status( 'can_be_validated' ) )
		{
			if( $User->check_status( 'is_validated' ) )
			{ // Already activated, e.g. clicked on an obsolete email link:
				$Messages->add( T_('Your account has already been activated.'), 'note' );
				$action = 'req_login';
				break;
			}
			elseif( $User->check_status( 'is_closed' ) )
			{ // Account was closed, don't let to activate the account
				$Messages->add( T_('Your account is closed. You cannot activate it.'), 'error' );
				// redirect to base url
				header_redirect( $baseurl );
				/* exited */
			}
		}
		elseif( empty( $last_reminder_key ) || ( $last_reminder_key != $reminder_key ) )
		{ // the reminder key in db is empty or not equal with the received one
			$Messages->add( T_('Invalid account activation request!'), 'error' );
			$action = 'req_validatemail';
			break;
		}

		// log in with user
		$Session->set_user_ID( $userID );

		// activate user account
		$User->activate_from_Request();
		$Messages->add( T_('Your account is now activated.'), 'success' );

		header_redirect( redirect_after_account_activation() );
		/* exited */
		break;

	case 'validatemail': // Clicked "Validate email" link from a mail
		// Stop a request from the blocked IP addresses or Domains
		antispam_block_request();

		param( 'reqID', 'string', '' );
		param( 'sessID', 'integer', '' );

		if( check_user_status( 'is_validated' ) )
		{ // Already validated, e.g. clicked on an obsolete email link:
			$Messages->add( T_('Your email address has already been validated.'), 'note' );
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
				&& ! ( isset($current_User) && $current_User->grp_ID == 1 && $reqID == 1 /* admin users can validate themselves by a button click */ ) )
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

			// activate user account
			$current_User->activate_from_Request();

			$Messages->add( T_( 'Your email address has been validated.' ), 'success' );
		}

		// init redirect_to
		$redirect_to = redirect_after_account_activation();

		// Cleanup:
		$Session->delete('core.validatemail.request_ids');
		$Session->delete('core.validatemail.redirect_to');

		// redirect Will save $Messages into Session:
		header_redirect( $redirect_to );
		/* exited */
		break;

} // switch( $action ) (1st)



/* For actions that other delegate to from the switch above: */
switch( $action )
{
	case 'req_validatemail':
		// Send activation link by email (initial form and action)
		if( ! is_logged_in() )
		{
			$Messages->add( T_('You have to be logged in to request an account validation link.'), 'error' );
			$action = '';
			break;
		}

		if( check_user_status( 'is_validated' ) )
		{ // Activation not required (check this after login, so it does not get "announced")
			$action = '';
			break;
		}

		param( 'req_validatemail_submit', 'integer', 0 ); // has the form been submitted
		$email = utf8_strtolower( param( $dummy_fields['email'], 'string', $current_User->email ) ); // the email address is editable

		if( $req_validatemail_submit )
		{ // Form has been submitted
			param_check_email( $dummy_fields['email'], true );

			// check if user email was changed
			$email_changed = ( $current_User->get( 'email' ) != $email );

			// check if we really needs to send a new validation email
			if( !$email_changed )
			{ // the email was not changed
				$last_activation_email_date = $UserSettings->get( 'last_activation_email', $current_User->ID );
				if( ! empty( $last_activation_email_date ) )
				{ // at least one validation email was sent
					// convert date to timestamp
					$last_activation_email_ts = mysql2timestamp( $last_activation_email_date );
					$activate_requests_limit = $Settings->get( 'activate_requests_limit' );
					if( $servertimenow - $last_activation_email_ts < $activate_requests_limit )
					{ // a validation email was sent to the same email address less then the x seconds, where x is the "Activation requests limit" value
						// get difference between local time and server time
						$time_difference = $Settings->get('time_difference');
						// get last activation email local date and time
						$last_email_date = date( locale_datetimefmt(), $last_activation_email_ts + $time_difference );
						$Messages->add( sprintf( T_( "We have already sent you an activation message to %s at %s. Please allow %d minutes for delivery before requesting a new one." ), $email, $last_email_date, $activate_requests_limit / 60 ) );
					}
				}
			}

			// Call plugin event to allow catching input in general and validating own things from DisplayRegisterFormFieldset event
			$Plugins->trigger_event( 'ValidateAccountFormSent' );

			if( $Messages->has_errors() )
			{
				break;
			}

			if( $email_changed )
			{ // Update user's email:
				$current_User->set_email( $email );
				if( !$current_User->dbupdate() )
				{ // email address couldn't be updated
					$Messages->add( T_('Could not update your email address.'), 'error' );
					break;
				}
			}

			$inskin_blog = $inskin ? $blog : NULL;
			if( $current_User->send_validate_email( $redirect_to, $inskin_blog, $email_changed ) )
			{
				$Messages->add( sprintf( /* TRANS: %s gets replaced by the user's email address */ T_('An email has been sent to your email address (%s). Please click on the link therein to validate your account.'), $current_User->dget('email') ), 'success' );
			}
			elseif( $demo_mode )
			{
				$Messages->add( 'Sorry, could not send email. Sending email in demo mode is disabled.', 'error' );
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


if( strlen($redirect_to) )
{ // Make it relative to the form's target, in case it has been set absolute (and can be made relative).
	$redirect_to = url_rel_to_same_host( $redirect_to, $secure_htsrv_url );
}


if( preg_match( '#/login.php([&?].*)?$#', $redirect_to ) )
{ // avoid "endless loops"
	$redirect_to = $baseurl;
}

// Remove login and pwd parameters from URL, so that they do not trigger the login screen again:
$redirect_to = preg_replace( '~(?<=\?|&) (login|pwd) = [^&]+ ~x', '', $redirect_to );
$Debuglog->add( 'redirect_to: '.$redirect_to );


/*
 * Display in-skin login if it's supported
 */
if( $inskin && use_in_skin_login() )
{ // in-skin display:
	$BlogCache = & get_BlogCache();
	$Blog = $BlogCache->get_by_ID( $blog, false, false );
	if( ! empty( $Blog ) )
	{
		if( !empty( $login_error ) )
		{
			$Messages->add( $login_error );
		}
		if( empty( $redirect_to ) )
		{
			$redirect_to = $Blog->gen_blogurl();
		}
		// check if action was req_validatemail
		if( ( $action == 'req_validatemail' ) && !empty( $current_User ) )
		{ // redirect to inskin activate account page
			$redirect = url_add_param( $Blog->gen_blogurl(), 'disp=activateinfo', '&' );
			if( $Messages->has_errors() )
			{	// Redirect to a form for requesting an activation again if some errors exist
				$redirect = url_add_param( $redirect, 'force_request=1', '&' );
			}
		}
		elseif( $action == 'lostpassword' )
		{ // redirect to inskin lost password page
			$redirect = $Blog->get( 'lostpasswordurl', array( 'glue' => '&' ) );
			if( ! empty( $lostpassword_error ) )
			{ // Set this param to know after redirection if error was here
				$redirect = url_add_param( $redirect, 'field_error=1', '&' );
			}
		}
		else
		{ // redirect to inskin login page
			$redirect = $Blog->get( 'loginurl', array( 'glue' => '&' ) );
		}
		$redirect = url_add_param( $redirect, 'redirect_to='.$redirect_to, '&' );
		header_redirect( $redirect );
		// already exited here
		exit(0);
	}
}

/**
 * Display standard login screen:
 */
switch( $action )
{
	case 'lostpassword':
		// Lost password:
		$page_title = T_('Lost your password?');
		$hidden_params = array( 'redirect_to' => url_rel_to_same_host( $redirect_to, $secure_htsrv_url ) );
		$wrap_width = '480px';

		// Use the links in the form title
		$use_form_links = true;

		// Include page header:
		require $adminskins_path.'login/_html_header.inc.php';

		// Lost password form
		$params = array(
			'form_title_lostpass'  => $page_title,
			'login_form_inskin'    => false,
			'login_page_class'     => 'evo_panel__login',
			'login_page_before'    => '<div class="evo_panel__lostpass">',
			'login_page_after'     => '</div>',
			'form_class_login'     => 'evo_form__login evo_form__lostpass',
			'lostpass_form_params' => $login_form_params,
			'lostpass_form_footer' => false,
			'abort_link_text'      => '<button type="button" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></button>',
		);
		require skin_fallback_path( '_lostpassword.disp.php', 6 );

		// Include page footer:
		require $adminskins_path.'login/_html_footer.inc.php';
		break;

	case 'req_validatemail':
		// Send activation link by email (initial form and action)
		// Display validation form:
		require $adminskins_path.'login/_validate_form.main.php';
		break;

	case 'changepwd':
		// Display form to change password:
		require $adminskins_path.'login/_reset_pwd_form.main.php';
		break;

	default:
		// Display login form:
		require $adminskins_path.'login/_login_form.main.php';
}

exit(0);
?>