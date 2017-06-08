<?php
/**
 * This is the login screen. It also handles actions related to logging in/out, registering, changing password and closing account.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package htsrv
 */

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

/**
 * @global boolean Is this a login page?
 */
$is_login_page = true;

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
// Used to ABORT login
param( 'return_to', 'url', $ReqURI );

switch( $action )
{
	case 'logout':
		// Log the current user out:

		logout(); // logout $Session and set $current_User = NULL

		// TODO: to give the user feedback through $Messages, we would need to start a new $Session here and append $Messages to it.

		// Redirect to $baseurl on logout if redirect URI is not set. Temporarily fix until we remove actions from redirect URIs
		if( $redirect_to == $ReqURI )
		{
			$redirect_to = $baseurl;
		}

		header_redirect(); // defaults to redirect_to param and exits
		/* exited */
		break;

	case 'closeaccount': 
		// Close current user account and log out:

		global $Session, $Messages, $UserSettings;
		$Session->assert_received_crumb( 'closeaccountform' );

		// Check if User has provided a reason for closing his account:
		$reasons = trim( $Settings->get( 'account_close_reasons' ) );
		param( 'account_close_type', 'string', '' );

		if( ! empty( $reasons ) && empty( $account_close_type ) )
		{ // Don't submit a form without a selected reason
			$Messages->add( T_( 'Please quickly select a reason for closing your account.' ) );
			// Set this session var only to repopulate other reason textarea input
			$Session->set( 'account_close_reason', param( 'account_close_reason', 'text', '' ) );
			// Redirect to show the errors:
			header_redirect(); // Will EXIT
			// We have EXITed already at this point!!
		}

		if( is_logged_in() && $current_User->check_perm( 'users', 'edit', false ) )
		{ // Admins cannot close own accounts
			$Messages->add( T_( 'Since you are an Admin with User management privileges, you cannot close your own account!' ) );
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

			// Set this session var only to know when display a good-bye message:
			$Session->set( 'account_closing_success', true );
		}
		else
		{ // db update was unsuccessful
			$Messages->add( T_( 'Unable to close your account. Please contact to system administrator.' ) );
		}

		header_redirect();
		/* exited */
		break;

	case 'resetpassword': 
		// Send password reset request by email:

		global $servertimenow;
		$login_required = true; // Do not display "Without login.." link on the form

		if( empty( $login ) )
		{ // Don't allow empty request
			param_error( $dummy_fields['login'], T_('You must enter your username or your email address so we know where to send the password reset email.'), '' );
			// Set this var to know after redirection if error was here
			$lostpassword_error = true;
			$action = 'lostpassword';
			break;
		}

		// Check if a password reset email was already requested recently and block too frequent requests:
		$request_ts_login = $Session->get( 'core.changepwd.request_ts_login' );
		if( $request_ts_login != NULL )
		{
			list( $last_request_ts, $last_request_login ) = preg_split( '~_~', $request_ts_login );
			if( ( $login == $last_request_login ) && ( ( $servertimenow - $pwdchange_request_delay ) < $last_request_ts ) )
			{ // the same request was sent from the same session in the last $pwdchange_request_delay seconds ( 5 minutes by default )
				$Messages->add( sprintf( T_('We have already sent you a password reset email at %s. Please allow %d minutes for delivery before requesting a new one.' ), date( locale_datetimefmt(), $last_request_ts ), $pwdchange_request_delay / 60 ) );
				// Go back to login page:
				$action = 'req_login';
				break;
			}
		}

		$UserCache = & get_UserCache();
		$UserCache->clear();
		if( is_email( $login ) )
		{ // User gave an email address, get matching User accounts for this email address and also get first activated one as recipient for the reset email:
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
		{ // User gave a login, get hat User by login:
			$forgetful_User = & $UserCache->get_by_login( $login );
		}

		if( ! $forgetful_User )
		{	// User does not exist
			// pretend that the email is sent to make it harder for attackers to guess user logins
			$Messages->add( T_('If you correctly entered your login or email address, a link to reset your password has been sent to your registered email address.' ), 'success' );
			// Go back to login page:
			$action = 'req_login';
			break;
		}

		locale_temp_switch( $forgetful_User->locale );

		if( $demo_mode )
		{
			$Messages->add( T_('You cannot reset passwords in demo mode.'), 'error' );
			// Go back to login page:
			$action = 'req_login';
			break;
		}

		if( empty( $forgetful_User->email ) )
		{
			$Messages->add( T_('Your user account has no associated email address; therefore we cannot reset your password.')
				.' '.T_('Please try contacting the admin.'), 'error' );
		}
		else
		{
			// Generate a Random key to include in the password reset link that will be sent by email:
			$request_id = generate_random_key(22); // 22 to make it not too long for URL but unique/safe enough

			// Count how many users match the reset request ( It can be more than 1 in case an email address was provided instead of a unique login )
			$user_ids = $UserCache->get_ID_array();
			$user_count = count( $user_ids );

			// Set blog param for email link:
			$blog_param = '';
			if( !empty( $blog ) )
			{
				$blog_param = '&inskin=true&blog='.$blog;
			}

			// Other params for the email:
			$subject = sprintf( T_( 'Password reset request for %s' ), $login );
			$email_template_params = array(
					'user_count'     => $user_count,
					'request_id'     => $request_id,
					'blog_param'     => $blog_param,
				);

			// Send email to User by using a text/html template:
			// In case several Users have the same email address, the email will allow the recipient to choose which account he wants to reset:
			if( ! send_mail_to_User( $forgetful_User->ID, $subject, 'account_password_reset', $email_template_params, true ) )
			{
				$Messages->add( T_('Sorry, the email with the link to reset your password could not be sent.')
					.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
			}
			else
			{
				// Prevent too many identical password recovery emails:
				$Session->set( 'core.changepwd.request_ts_login', $servertimenow.'_'.$login, $pwdchange_request_delay + 60 ); // Session var expires 60 seconds after the allowed delay.
				// Secret key that will be included into the reset email in order to validate that it gets to the owner of the email address:
				$Session->set( 'core.changepwd.request_id', $request_id, 86400 * 2 ); // Session var expires in two days (or when password changed)
				// Target of the request (can be a login or an email address):
				$Session->set( 'core.changepwd.request_for', $login, 86400 * 2 ); // Session var expires in two days (or when password changed)
				$Session->dbsave(); // save immediately

				$Messages->add( T_('If you correctly entered your login or email address, a link to reset your password has been sent to your registered email address.' ), 'success' );

				syslog_insert( 'User requested password reset', 'info', 'user', $forgetful_User->ID );
			}
		}

		locale_restore_previous();

		$action = 'req_login';
		break;


	case 'changepwd': 
		// User clicked "Reset password NOW" link from an password reset email:

		param( 'reqID', 'string', '' );

		$UserCache = & get_UserCache();
		$forgetful_User = & $UserCache->get_by_login( $login );

		// Validate params against session vars:
		if( ! validate_pwd_reset_session( $reqID, $forgetful_User ) )
		{
			$Messages->add( T_('Invalid password change request! Remember you must use the same session (by means of your session cookie) as when you have requested the action. Please try again...'), 'error' );
			locale_restore_previous();
			$action = 'lostpassword';
			$login_required = true; // Do not display "Without login.." link on the form
			break;
		}

  		// Link User to Session and Log in:
		$Session->set_user_ID( $forgetful_User->ID );
		$current_User = & $forgetful_User;

		// Add Message to change the password:
		$Messages->add( T_( 'Please choose a new password now...' ), 'note' );

		// Redirect to the user's change password tab
		$changepwd_url = NULL;
		if( !empty( $blog ) )
		{ // blog is set, redirect to in-skin change password form
			$BlogCache = & get_BlogCache();
			$Collection = $Blog = $BlogCache->get_by_ID( $blog );
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
		{ // redirect will save $Messages into Session:
			header_redirect( $changepwd_url ); // display user's change password tab
			/* exited */
		}
		break;


	case 'updatepwd':
		// User is updating his password (submit action of the above reset password form):

		param( 'reqID', 'string', '' );

		if( ! is_logged_in() )
		{ // Don't allow not logged in user here, because it must be logged in on the action 'changepwd' above
			$Messages->add( T_('Invalid password change request! Please try again...'), 'error' );
			$action = 'lostpassword';
			$login_required = true; // Do not display "Without login.." link on the form
			break;
		}

		$forgetful_User = & $current_User;

		// Validate params against session vars:
		if( ! validate_pwd_reset_session( $reqID, $forgetful_User ) )
		{
			$Messages->add( T_('Invalid password change request! Remember you must use the same session (by means of your session cookie) as when you have requested the action. Please try again...'), 'error' );
			locale_restore_previous();
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

		// Clean up session variables:
		$Session->delete( 'core.changepwd.request_ts_login' );
		$Session->delete( 'core.changepwd.request_id' );
		$Session->delete( 'core.changepwd.request_for' );
		$Session->dbsave(); // save immediately

		locale_restore_previous();

		// redirect Will save $Messages into Session:
		header_redirect( $baseurl ); // display user's change password tab
		/* exited */
		break;


	case 'activateacc_ez': 
		// User clicked 'Activate NOW' or 'Reactivate NOW' from an account activation email with EASY activation process (first email or reminder):
	
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
				if( is_logged_in() )
				{	// Redirect to base url if user is already logged in:
					header_redirect( $baseurl );
					/* exited */
				}
				else
				{	// Display a login form if user is not logged in yet:
					$action = 'req_login';
					break;
				}
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
			$action = 'req_activate_email';
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

	case 'activateacc_sec': 
		// User clicked 'Activate NOW' or 'Reactivate NOW' from an account activation email with SECURE activation process (first email or reminder):
		// fp> NOTE: I am not sure secure process works allows reminders.

		// Stop a request from the blocked IP addresses or Domains
		antispam_block_request();

		param( 'reqID', 'string', '' );
		param( 'sessID', 'integer', '' );

		if( check_user_status( 'is_validated' ) )
		{ // Already validated, e.g. clicked on an obsolete email link:
			$Messages->add( T_('Your account has already been activated.'), 'note' );
			// no break: cleanup & redirect below
		}
		else
		{
			// Check valid format:
			if( empty($reqID) )
			{ // This was not requested
				$Messages->add( T_('Invalid account activation request!'), 'error' );
				$action = 'req_activate_email';
				break;
			}

			// Check valid session (format only, meant as help for the user):
			if( $sessID != $Session->ID )
			{ // Another session ID than for requesting account validation link used!
				$Messages->add( T_('You have to use the same session (by means of your session cookie) as when you have requested the action. Please try again...'), 'error' );
				$action = 'req_activate_email';
				break;
			}

			// Validate provided reqID against the one stored in the user's session
			$request_ids = $Session->get( 'core.activateacc.request_ids' );
			if( ( ! is_array($request_ids) || ! in_array( $reqID, $request_ids ) )
				&& ! ( isset($current_User) && $current_User->grp_ID == 1 && $reqID == 1 /* admin users can validate themselves by a button click */ ) )
			{
				$Messages->add( T_('Invalid account activation request!'), 'error' );
				$action = 'req_activate_email';
				$login_required = true; // Do not display "Without login.." link on the form
				break;
			}

			if( ! is_logged_in() )
			{ // this can happen, if a new user registers and clicks on the "validate by email" link, without logging in first
				// Note: we reuse $reqID and $sessID in the form to come back here.

				$Messages->add( T_('Please log in to activate your account.'), 'error' );
				break;
			}

			// activate user account
			$current_User->activate_from_Request();

			$Messages->add( T_( 'Your account is now activated.' ), 'success' );
		}

		// init redirect_to
		$redirect_to = redirect_after_account_activation();

		// Cleanup:
		$Session->delete('core.activateacc.request_ids');
		$Session->delete('core.activateacc.redirect_to');

		// redirect Will save $Messages into Session:
		header_redirect( $redirect_to );
		/* exited */
		break;

} // switch( $action ) (1st)



/* For actions that other delegate to from the switch above: */
switch( $action )
{
	case 'req_activate_email':
		// User wants to request a new activation link by email (initial form and action):

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

		param( 'req_activate_email_submit', 'integer', 0 ); // has the form been submitted
		$email = utf8_strtolower( param( $dummy_fields['email'], 'string', $current_User->email ) ); // the email address is editable

		if( $req_activate_email_submit )
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

			// Call plugin event to allow catching input in general and validating own things from DisplayRegisterFormFieldset event:
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
				$Messages->add( sprintf( /* TRANS: %s gets replaced by the user's email address */ T_('An email has been sent to your email address (%s). Please click on the link therein to activate your account.'), $current_User->dget('email') ), 'success' );
			}
			elseif( $demo_mode )
			{
				$Messages->add( 'Sorry, could not send email. Sending email in demo mode is disabled.', 'error' );
			}
			else
			{
				$Messages->add( T_('Sorry, the email with the link to activate your account could not be sent.')
							.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
			}
		}
		else
		{ // Form not yet submitted:
			// Add a note, if we have already sent validation links:
			$request_ids = $Session->get( 'core.activateacc.request_ids' );
			if( is_array($request_ids) && count($request_ids) )
			{
				$Messages->add( sprintf( T_('We have already sent you %d email(s) with an activation link.'), count($request_ids) ), 'note' );
			}

			if( empty($current_User->email) )
			{ // add (error) note to be displayed in the form
				$Messages->add( T_('Your user account has no associated email address; therefore we cannot activate it. Please provide your email address below.'), 'error' );
			}
		}
		break;
}


if( strlen( $redirect_to ) )
{ // Make it relative to the form's target, in case it has been set absolute (and can be made relative).
	$redirect_to = url_rel_to_same_host( $redirect_to, get_htsrv_url( true ) );
}
if( preg_match( '#/login.php([&?].*)?$#', $redirect_to ) )
{ // avoid "endless loops"
	$redirect_to = $baseurl;
}
// Remove login and pwd parameters from URL, so that they do not trigger the login screen again:
$redirect_to = preg_replace( '~(?<=\?|&) (login|pwd) = [^&]+ ~x', '', $redirect_to );
$Debuglog->add( 'redirect_to: '.$redirect_to );

if( strlen( $return_to ) )
{ // Make it relative to the form's target, in case it has been set absolute (and can be made relative).
	$return_to = url_rel_to_same_host( $return_to, get_htsrv_url( true ) );
}
if( preg_match( '#/login.php([&?].*)?$#', $return_to ) )
{ // avoid "endless loops"
	$redirect_to = $baseurl;
}
// Remove login and pwd parameters from URL, so that they do not trigger the login screen again:
$return_to = preg_replace( '~(?<=\?|&) (login|pwd) = [^&]+ ~x', '', $return_to );
$Debuglog->add( 'return_to: '.$return_to );


/*
 * Display in-skin login if it's supported
 */
if( $inskin && use_in_skin_login() )
{ // in-skin display:
	$BlogCache = & get_BlogCache();
	$Collection = $Blog = $BlogCache->get_by_ID( $blog, false, false );
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
		// check if action was req_activate_email
		if( ( $action == 'req_activate_email' ) && !empty( $current_User ) )
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
		$redirect = url_add_param( $redirect, 'redirect_to='.rawurlencode( $redirect_to ), '&' );
		header_redirect( $redirect );
		// already exited here
		exit(0);
	}
}


/**
 * Display one of the standard login management screens:
 */
switch( $action )
{
	case 'lostpassword':
		// Lost password:
		$page_title = T_('Lost your password?');
		$hidden_params = array( 'redirect_to' => url_rel_to_same_host( $redirect_to, get_htsrv_url( true ) ) );
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

	case 'changepwd':
		// Display form to reset password: (after 'lostpassword' form has been submitted and email has been received+clicked)
		require $adminskins_path.'login/_reset_pwd_form.main.php';
		break;

	case 'req_activate_email':
		// Send activation link by email (initial form and action)
		// Display validation form:
		require $adminskins_path.'login/_validate_form.main.php';
		break;

	default:
		// Display login form:

		if( $Settings->get( 'http_auth_require' ) && ! isset( $_SERVER['PHP_AUTH_USER'] ) )
		{	// Require HTTP authentication:
			header( 'WWW-Authenticate: Basic realm="b2evolution"' );
			header( 'HTTP/1.0 401 Unauthorized' );
		}

		require $adminskins_path.'login/_login_form.main.php';
}

exit(0);
?>