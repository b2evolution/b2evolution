<?php
/**
 * This file initializes everything BUT the blog!
 *
 * It is useful when you want to do very customized templates!
 * It is also called by more complete initializers.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package evocore
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


$Timer->resume( '_init_login' );


/*
 * Login procedure:
 * TODO: dh> if a "logged in"-session exists (in most cases) it should not trigger parsing the meat of this code.
 * fp> mind you, most hits will be on the font end and will not be loggedin sessions
 *     However, I agree that the login stuff should only be included when the user is actually attempting to log in.
 */
if( !isset($login_required) )
{
	$login_required = false;
}
if( !isset($validate_required) )
{
	$validate_required = param( 'validate_required', 'boolean', false );
}

global $login_error;
/**
 * Login error message
 * @global string
 */
$login_error = '';

$login = NULL;
$pass = NULL;
$pass_md5 = NULL;
$email_login = false;
$check_login_crumb = true;
$report_wrong_pass_hashing = true;

if( isset( $_POST[ $dummy_fields[ 'login' ] ] ) && isset( $_POST[ $dummy_fields[ 'pwd' ] ] ) )
{	// Trying to log in with a POST:
	$login_mode = 'post_form';
	$login = $_POST[ $dummy_fields[ 'login' ] ];
	$pass = $_POST[ $dummy_fields[ 'pwd' ] ];
	unset( $_POST[ $dummy_fields[ 'pwd' ] ] ); // password will be hashed below
}
elseif( isset( $_GET[ $dummy_fields[ 'login' ] ] ) )
{	// Trying to log in with a GET; we might only provide a user here.
	$login_mode = 'get_request';
	$login = $_GET[ $dummy_fields[ 'login' ] ];
	$pass = isset( $_GET[ $dummy_fields[ 'pwd' ] ] ) ? $_GET[ $dummy_fields[ 'pwd' ] ] : '';
	unset( $_GET[ $dummy_fields[ 'pwd' ] ] ); // password will be hashed below
}
elseif( empty( $disable_http_auth ) && $Settings->get( 'http_auth_accept' ) && ! $Session->has_User() && isset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) )
{	// Trying to log in with HTTP basic authentication:
	$login_mode = 'http_basic_auth';
	$login = $_SERVER['PHP_AUTH_USER'];
	$pass = $_SERVER['PHP_AUTH_PW'];
	// Don't check crumb because it is impossible to send by this auth method:
	$check_login_crumb = false;
	// Don't report about not hashing password because it is impossible to send by this auth method:
	$report_wrong_pass_hashing = false;
	// Set action to simulate a form submit button like '<input type="submit" name="login_action[login]" >' for correct redirect after successful login:
	$login_action = array( 'login' => '' );
}

$Debuglog->add( 'Login: login: '.var_export( htmlspecialchars( $login, ENT_COMPAT, $evo_charset ), true ), '_init_login' );
$Debuglog->add( 'Login: pass: '.( empty( $pass ) ? '' : 'not' ).' empty', '_init_login' );

// either 'login' (normal) or 'redirect_to_backoffice' may be set here. This also helps to display the login form again, if either login or pass were empty.
$login_action = param_arrayindex( 'login_action' );

if( ( $login != NULL ) && ( ! is_string( $login ) ) )
{ // Login must be string
	$login = NULL;
	if( ! empty( $login_action ) )
	{ // This was a login request with an invalid login parameter type, so it must be a doctored request
		debug_die('The type of the received login parameter is invalid!');
	}
}
if( ( $pass != NULL ) && ( ! is_string( $pass ) ) )
{ // Password must be string
	$pass = NULL;
	if( ! empty( $login_action ) )
	{ // This was a login request with an invalid pwd parameter type, so it must be a doctored request
		debug_die('The type of the received password parameter is invalid!');
	}
}

$UserCache = & get_UserCache();

if( ! empty($login_action) || (! empty($login) && ! empty($pass)) )
{ // User is trying to login right now

	// Stop a request from the blocked IP addresses or Domains
	antispam_block_request();

	global $action;
	// Set $action so it can be recorded in the hitlog:
	$action = 'login';

	$Debuglog->add( 'Login: User is trying to log in.', '_init_login' );

	header_nocache();		// Don't take risks here :p

	if( $check_login_crumb )
	{	// Check that this login request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'loginform' );
		// fp> NOTE: TODO: now that we require going through the login form (instead of URL params), all the login logic that is here can probably be moved to login.php ?
	}

	// Note: login and password cannot include ' or " or > or <
	// Note: login cannot include @
	$login = utf8_strtolower( utf8_strip_tags( remove_magic_quotes( $login ) ) );
	$pass = utf8_strip_tags( remove_magic_quotes( $pass ) );
	$pass_md5 = md5( $pass );


	/*
	 * Handle javascript-hashed password:
	 * If possible, the login form will hash the entered password with a salt that changes everytime.
	 */
	param( 'pwd_salt', 'string', '' ); // just for comparison with the one from Session
	$pwd_salt_sess = $Session->get('core.pwd_salt');

	// $Debuglog->add( 'Login: salt: '.var_export($pwd_salt, true).', session salt: '.var_export($pwd_salt_sess, true), '_init_login' );

	if( can_use_hashed_password() )
	{
		param( 'pwd_hashed', 'array:string', array() );
	}
	else
	{ // at least one plugin requests the password un-hashed:
		$pwd_hashed = array();
	}

	// $Debuglog->add( 'Login: pwd_hashed: '.var_export($pwd_hashed, true).', pass: '.var_export($pass, true), '_init_login' );

	$pass_ok = false;
	// Trigger Plugin event, which could create the user, according to another database:
	if( $Plugins->trigger_event( 'LoginAttempt', array(
			'login' => & $login,
			'pass' => & $pass,
			'pass_md5' => & $pass_md5,
			'pass_salt' => & $pwd_salt_sess,
			'pass_hashed' => & $pwd_hashed,
			'pass_ok' => & $pass_ok ) ) )
	{ // clear the UserCache, if a plugin has been called - it may have changed user(s)
		$UserCache->clear();
	}

	if( ! empty( $login_error ) )
	{ // A plugin has thrown a login error..
		// Do nothing, the error will get displayed in the login form..

		// TODO: dh> make sure that the user gets logged out?! (a Plugin might have logged him in and another one thrown an error)
	}
	else
	{ // Check login and password
		if( is_email( $login ) )
		{ // we have an email address instead of login name
			// get user by email and password
			list( $User, $exists_more ) = $UserCache->get_by_emailAndPwd( $login, $pass, $pwd_hashed, $pwd_salt_sess );
			if( $User )
			{ // user was found
				$email_login = $User->get( 'login' );
			}
		}
		elseif( param_check_valid_login( $dummy_fields[ 'login' ] ) )
		{ // Make sure that we can load the user:
			$User = & $UserCache->get_by_login($login);
		}
		else
		{
			$User = false;
		}

		if( $User )
		{ // Check user login attempts
			$login_attempts = $UserSettings->get( 'login_attempts', $User->ID );
			$login_attempts = empty( $login_attempts ) ? array() : explode( ';', $login_attempts );
			if( $failed_logins_lockout > 0 && count( $login_attempts ) == 9 )
			{ // User already has a maximum value of the attempts
				$first_attempt = explode( '|', $login_attempts[0] );
				if( $localtimenow - $first_attempt[0] < $failed_logins_lockout )
				{ // User has used 9 attempts during X minutes, Display error and Refuse login
					$login_error = sprintf( T_('There have been too many failed login attempts. This account is temporarily locked. Try again in %s minutes.'), ceil( $failed_logins_lockout / 60 ) );
				}
			}
		}

		if( $User && ! $pass_ok && empty( $login_error ) )
		{ // check the password, if no plugin has said "it's ok":
			if( ! empty($pwd_hashed) )
			{ // password hashed by JavaScript:

				$Debuglog->add( 'Login: Hashed password available.', '_init_login' );

				if( empty($pwd_salt_sess) )
				{ // no salt stored in session: either cookie problem or the user had already tried logging in (from another window for example)
					$Debuglog->add( 'Login: Empty salt_sess!', '_init_login' );
					if( ($pos = strpos( $pass, '_hashed_' ) ) && substr($pass, $pos+8) == $Session->ID )
					{ // session ID matches, no cookie problem
						$login_error = T_('The login window has expired. Please try again.');
						$Debuglog->add( 'Login: Session ID matches.', '_init_login' );
					}
					else
					{ // more general error:
						$login_error = T_('Either you have not enabled cookies or this login window has expired.');
						$Debuglog->add( 'Login: Session ID does not match.', '_init_login' );
					}
				}
				elseif( $pwd_salt != $pwd_salt_sess )
				{ // submitted salt differs from the one stored in the session
					$login_error = T_('The login window has expired. Please try again.');
					$Debuglog->add( 'Login: Submitted salt and salt from Session do not match.', '_init_login' );
				}
				else
				{ // compare the password, using the salt stored in the Session:
					#pre_dump( sha1($User->pass.$pwd_salt), $pwd_hashed );
					foreach( $pwd_hashed as $encrypted_password )
					{
						$pass_ok = ( sha1( bin2hex( $User->pass ).$pwd_salt_sess ) == $encrypted_password );
						if( $pass_ok )
						{ // Break after the first matching password
							break;
						}
					}
					$Session->delete('core.pwd_salt');
					$Debuglog->add( 'Login: Compared hashed passwords. Result: '.(int)$pass_ok, '_init_login' );
				}
			}
			else
			{	// Password NOT hashed by Javascript:
				$pass_ok = ( $User->pass == md5( $User->salt.$pass, true ) );
				$Debuglog->add( 'Login: Compared raw passwords. Result: '.(int)$pass_ok, '_init_login' );
				if( $report_wrong_pass_hashing && $pass_ok && can_use_hashed_password() )
				{	// Report about this unsecure login action:
					syslog_insert( sprintf( 'User %s logged in without password hashing.', $User->login ), 'error', 'user', $User->ID, 'core', NULL, $User->ID );
					$Messages->add( T_('WARNING: password hashing did not work. You just logged in insecurely. Please report this to your administrator.'), 'error' );
				}
			}
		}
	}

	if( $pass_ok )
	{ // Login succeeded, set cookies
		$Debuglog->add( 'Login: User successfully logged in with username and password...', '_init_login');
		// set the user from the login that succeeded
		if( $email_login )
		{
			$login = $email_login;
		}
		$current_User = & $UserCache->get_by_login($login);
		// check and don't login if the current user account was closed
		if( $current_User->check_status( 'is_closed' ) )
		{ // user account was closed
			unset( $current_User );
			$login_error = T_('This account is closed. You cannot log in.');
		}
		elseif( $Settings->get('system_lock') && !$current_User->check_perm( 'users', 'edit' ) )
		{ // System is locked for maintenance and current user has no permission to log in this mode
			unset( $current_User );
			$login_error = T_('You cannot log in at this time because the system is under maintenance. Please try again in a few moments.');
		}
		else
		{ // save the user for later hits
			$Session->set_User( $current_User );

			if( empty( $current_User->salt ) )
			{
				$Messages->add( sprintf( T_('For best security, we recommend you <a %s>change your password now</a>. This will allow to re-encrypt your password in our database in a more secure way.'), 'href="'.get_user_pwdchange_url().'"' ), 'warning' );
			}

			$current_user_locale = $current_User->get( 'locale' );
			if( ( ! isset( $locales[$current_user_locale] ) ) || ( ! $locales[$current_user_locale]['enabled'] ) )
			{ // Current user locale doesn't exists or it is not enabled, update to the default locale if it is valid
				$def_locale = $Settings->get( 'default_locale' );
				if( isset( $locales[$def_locale] ) && $locales[$def_locale]['enabled'] )
				{ // Update user locale to the default, and add warning message about the update
					$current_User->set( 'locale', $def_locale );
					$current_User->dbupdate();
					$Messages->add( T_('Your preferred language/locale is no longer available on this site. You are now using the default language/locale.'), 'warning' );
				}
			}

			if( $Settings->get('system_lock') && $current_User->check_perm( 'users', 'edit' ) )
			{ // System is locked for maintenance but current user has permission to log in, Display a message about this mode
				$system_lock_url = ' href="'.$admin_url.'?ctrl=tools"';
				$Messages->add( sprintf( T_('The site is currently locked for maintenance. Click <a %s>here</a> to access lock settings.'), $system_lock_url ), 'warning' );
			}
		}

		if( ! empty( $login_attempts ) )
		{ // Display all attempts on success login
			$current_ip = array_key_exists( 'REMOTE_ADDR', $_SERVER ) ? $_SERVER['REMOTE_ADDR'] : '';
			if( ! isset( $Plugins ) )
			{
				$Plugins = new Plugins();
			}
			// Initialize GeoIP plugin
			$geoip_Plugin = & $Plugins->get_by_code( 'evo_GeoIP' );

			foreach( $login_attempts as $attempt )
			{
				$attempt = explode( '|', $attempt );
				$attempt_ip = $attempt[1];

				$plugin_country_by_IP = '';
				if( ! empty( $geoip_Plugin ) && $Country = $geoip_Plugin->get_country_by_IP( $attempt_ip ) )
				{ // Get country by IP if plugin is enabled
					$plugin_country_by_IP = ' ('.$Country->get_name().')';
				}

				if( $attempt_ip != $current_ip )
				{ // Get DNS by IP if current IP is different from attempt IP
					$attempt_ip .= ' '.gethostbyaddr( $attempt_ip );
				}

				$Messages->add_to_group( sprintf( T_('Someone tried to log in to your account with a wrong password on %s from %s%s'),
						date( locale_datefmt().' '.locale_timefmt(), $attempt[0] ),
						$attempt_ip,
						$plugin_country_by_IP
					), 'error', T_('Invalid login attempts:') );
			}
			// Clear the attempts list
			$UserSettings->delete( 'login_attempts', $current_User->ID );
			$UserSettings->dbupdate();
		}
	}
	elseif( empty( $login_error ) )
	{ // if the login_error wasn't set yet, add the default one:
		// This will cause the login screen to "popup" (again)
		if( $login_mode == 'http_basic_auth' )
		{	// If wrong login from HTTP basic authentication
			if( ! empty( $is_login_page ) )
			{	// Display this error and login form only if user really is requesting a login page:
				$login_error = T_('Wrong Login/Password provided by browser (HTTP Auth).');
			}
		}
		else
		{	// If wrong login from standard POST forms or GET request:
			$login_error = T_('The Login/Password you entered is wrong.');
		}

		$current_login_pass = $login.':'.( empty( $pwd_hashed ) ? $pass : implode( '', $pwd_hashed ) );

		if( isset( $login_attempts ) && $current_login_pass != $Session->get( 'wrong_loginpass' ) )
		{	// Save new login attempt into DB only if previous login data were different:
			if( count( $login_attempts ) == 9 )
			{	// Unset first attempt to clear a space for new attempt:
				unset( $login_attempts[0] );
			}
			$login_attempts[] = $localtimenow.'|'.( array_key_exists( 'REMOTE_ADDR', $_SERVER ) ? $_SERVER['REMOTE_ADDR'] : '' );
			$UserSettings->set( 'login_attempts', implode( ';', $login_attempts ), $User->ID );
			$UserSettings->dbupdate();
		}

		// Save current wrong login/pass in session to know on next login trying that we get new data:
		$Session->set( 'wrong_loginpass', $current_login_pass );
	}

}
elseif( $Session->has_User() /* logged in */
	&& /* No login param given or the same as current user: */
	( empty($login) || ( ( $tmp_User = & $UserCache->get_by_ID($Session->user_ID) ) && $login == $tmp_User->login ) ) )
{ /* if the session has a user assigned to it:
	 * User was not trying to log in, but he was already logged in:
	 */
	// get the user ID from the session and set up the user again
	$current_User = & $UserCache->get_by_ID( $Session->user_ID );

	if( $Settings->get('system_lock') )
	{ // System is locked for maintenance
		if( $current_User->check_perm( 'users', 'edit' ) )
		{ // Current user is a "super admin"
			if( ! $Messages->count() )
			{ // If there are no other messages yet, display a warning about the system lock
				$system_lock_url = ' href="'.$admin_url.'?ctrl=tools"';
				$Messages->add( sprintf( T_('The site is currently locked for maintenance. Click <a %s>here</a> to access lock settings.'), $system_lock_url ), 'warning' );
			}
		}
		else
		{ // Current user has no permission to be logged in on this mode, we must logout it
			logout();
			$login_error = T_('You have been logged out because the system is under maintenance. Please log in again in a few moments.');
		}
	}
	else
	{
		$Debuglog->add( 'Login: Was already logged in... ['.$current_User->get('login').']', '_init_login' );
	}
}
else
{ // The Session has no user or $login is given (and differs from current user), allow alternate authentication through Plugin:
	if( ($event_return = $Plugins->trigger_event_first_true( 'AlternateAuthentication' ))
	    && $Session->has_User()  # the plugin should have attached the user to $Session
	)
	{
		$Debuglog->add( 'Login: User has been authenticated through plugin #'.$event_return['plugin_ID'].' (AlternateAuthentication)', '_init_login' );
		$current_User = & $UserCache->get_by_ID( $Session->user_ID );
	}
	elseif( $login_required )
	{	/*
		 * ---------------------------------------------------------
		 * User was not logged in at all, but login is required
		 * ---------------------------------------------------------
		 */
		// echo ' NOT logged in...';
		$Debuglog->add( 'Login: NOT logged in... (did not try)', '_init_login' );
	}
}
unset($pass);

$action = param( 'action', 'string', NULL );
// Check if the user needs to be validated, but is not yet:
if( check_user_status( 'can_be_validated' ) // user is logged in but not validated and validation is required
	&& $action != 'logout'
	&& $action != 'req_activate_email' && $action != 'activateacc_sec' && $validate_required )
{ // we're not in that action already:
	$action = 'req_activate_email'; // for login.php
	if( $is_admin_page )
	{
		$login_error = T_('In order to access the admin interface, you must first activate your account by clicking on the activation link in the email we sent you. <b>See below:</b>');
	}
}
// asimo> If login action is not empty and there was no login error, and action is not logut the we must log in
if( !empty($login_action) && empty( $login_error ) && ( $action != 'logout' ) )
{ // Trigger plugin event that allows the plugins to re-act on the login event:
	// TODO: dh> these events should provide a flag "login_attempt_failed".
	if( empty($current_User) )
	{
		$Plugins->trigger_event( 'AfterLoginAnonymousUser', array() );
	}
	else
	{
		$Plugins->trigger_event( 'AfterLoginRegisteredUser', array() );

		if( ! empty( $login_action ) )
		{ // We're coming from the Login form and need to redirect to the requested page:
			$redirect_to = param( 'redirect_to', 'url', $baseurl );
			if( empty( $redirect_to ) ||
				preg_match( '#/login.php([&?].*)?$#', $redirect_to ) ||
				preg_match( '#/register.php([&?].*)?$#', $redirect_to ) ||
				preg_match( '#disp=(login|register|lostpassword)#', $redirect_to ) )
			{ // avoid redirect back to login/register screen. This shouldn't occur.
				$redirect_to = $baseurl;
			}

			if( $email_login )
			{
				$Messages->add( sprintf( T_( 'You are now logged in as <b>%s</b>' ), $login ), ( $exists_more ? 'error' : 'success' ) );
			}

			header_redirect( $redirect_to );
			exit(0);
		}
	}
}

if( ! empty( $login_error ) || ( $login_required && ! is_logged_in() ) )
{ // ----- LOGIN FAILED ----- OR Login is required and user is not logged in yet
	$Debuglog->add( 'Login error: '.$login_error, '_init_login' );
	// inskin param is set when the login request come from the front office
	// we need this to decide if we should use display in-skin login from or not
	param( 'inskin', 'boolean', 0 );
	$Debuglog->add( 'Param inskin: '.$inskin, '_init_login' );
	if( $inskin || use_in_skin_login() )
	{ // Use in-skin login
		$Debuglog->add( 'Trying to use in-skin login', '_init_login' );

		if( is_logged_in() )
		{ // user is logged in, but the email address is not validated yet
			$login = $current_User->login;
			$email = $current_User->email;
		}

		if( empty( $Blog ) && init_requested_blog() )
		{ // $blog is set, init $Blog also
			$BlogCache = & get_BlogCache();
			$Collection = $Blog = $BlogCache->get_by_ID( $blog, false, false );
		}

		$blog_skin_ID = NULL;
		if( !empty( $Blog ) )
		{ // Blog was set
			$blog_skin_ID = $Blog->get_skin_ID();
		}

		if( !empty( $blog_skin_ID ) )
		{ // Blog exists and skin ID is set
			// Init charset handling:
			init_charsets( $current_charset );
			locale_activate( $Blog->get('locale') );
			$Messages->add( $login_error );
			$SkinCache = & get_SkinCache();
			$Skin = & $SkinCache->get_by_ID( $blog_skin_ID );
			$skin = $Skin->folder;
			$disp = 'login';
			// fp> We ABSOLUTELY want to recover the previous redirect_to so that after a new login attempt that may be successful,
			// we will finally reach our intended destination. This is paramount with emails telling people to come back to the site
			// to read a message or sth like that. They must log in first and they may enter the wrong password multiple times.
			param( 'redirect_to', 'url', $Blog->gen_blogurl() );
			$ads_current_skin_path = $skins_path.$skin.'/';
			require $ads_current_skin_path.'index.main.php';
			exit(0);
			// --- EXITED !! ---
		}

		$Debuglog->add( 'we have NO valid blog to use for inskin login', '_init_login' );
	}

	// Use standard login
	$Debuglog->add( 'Using standard login', '_init_login' );
	// Init charset handling:
	init_charsets( $current_charset );
	require $htsrv_path.'login.php';
	exit(0);
	// --- EXITED !! ---
}

$Timer->pause( '_init_login' );

?>