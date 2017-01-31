<?php
/**
 * Register a new user.
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

// Login is not required on the register page:
$login_required = false;

global $baseurl;

if( is_logged_in() )
{ // if a user is already logged in don't allow to register
	header_redirect( $baseurl );
}

// Save trigger page
$session_registration_trigger_url = $Session->get( 'registration_trigger_url' );
if( empty( $session_registration_trigger_url ) && isset( $_SERVER['HTTP_REFERER'] ) )
{	// Trigger page still is not defined
	$session_registration_trigger_url = $_SERVER['HTTP_REFERER'];
	$Session->set( 'registration_trigger_url', $session_registration_trigger_url );
}

// Check if country is required
$registration_require_country = (bool)$Settings->get('registration_require_country');
// Check if firstname is required
$registration_require_firstname = (bool)$Settings->get('registration_require_firstname');
// Check if firstname is required (It can be required for quick registration by widget)
$registration_require_lastname = false;
// Check if gender is required
$registration_require_gender = $Settings->get('registration_require_gender');
// Check if registration ask for locale
$registration_ask_locale = $Settings->get('registration_ask_locale');
// Check what subscriptions should be activated (It can be used for quick registration by widget)
$auto_subscribe_posts = false;
$auto_subscribe_comments = false;

$login = param( $dummy_fields[ 'login' ], 'string', '' );
$email = utf8_strtolower( param( $dummy_fields[ 'email' ], 'string', '' ) );
param( 'action', 'string', '' );
param( 'country', 'integer', '' );
param( 'firstname', 'string', '' );
param( 'lastname', 'string', '' );
param( 'gender', 'string', NULL );
param( 'locale', 'string', '' );
param( 'source', 'string', '' );
param( 'redirect_to', 'url', '' ); // do not default to $admin_url; "empty" gets handled better in the end (uses $blogurl, if no admin perms).
param( 'inskin', 'boolean', false, true );

global $Blog;
if( $inskin && empty( $Blog ) )
{
	param( 'blog', 'integer', 0 );

	if( isset( $blog) && $blog > 0 )
	{
		$BlogCache = & get_BlogCache();
		$Blog = $BlogCache->get_by_ID( $blog, false, false );
	}
}

if( $inskin && !empty( $Blog ) )
{ // in-skin register, activate current Blog locale
	locale_activate( $Blog->get('locale') );
}

// Check invitation code if it exists and registration is enabled
$display_invitation = check_invitation_code();

if( $display_invitation == 'deny' )
{ // Registration is disabled
	$action = 'disabled';
}

if( $register_user = $Session->get('core.register_user') )
{	// Get an user data from predefined session (after adding of a comment)
	$login = preg_replace( '/[^a-z0-9 ]/i', '', $register_user['name'] );
	$login = str_replace( ' ', '_', $login );
	$login = utf8_substr( $login, 0, 20 );
	$email = $register_user['email'];

	$Session->delete( 'core.register_user' );
}

switch( $action )
{
	case 'register':
	case 'quick_register':
		// Stop a request from the blocked IP addresses or Domains
		antispam_block_request();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'regform' );

		// Use this boolean var to know when quick registration is used
		$is_quick = ( $action == 'quick_register' );

		if( $is_quick )
		{ // Check if we can use a quick registration now:
			if( $Settings->get( 'newusers_canregister' ) != 'yes' || ! $Settings->get( 'quick_registration' ) )
			{ // Display error message when quick registration is disabled
				$Messages->add( T_('Quick registration is currently disabled on this system.'), 'error' );
				break;
			}

			param( 'widget', 'integer', 0 );

			if( empty( $Blog ) || empty( $widget ) )
			{ // Don't use a quick registration if the request goes from not blog page
				$Messages->add( T_('Quick registration is currently disabled on this system.'), 'error' );
				break;
			}

			$WidgetCache = & get_WidgetCache();
			if( ! $user_register_Widget = & $WidgetCache->get_by_ID( $widget, false, false ) ||
			    $user_register_Widget->code != 'user_register' ||
			    $user_register_Widget->get( 'coll_ID' ) != $Blog->ID )
			{ // Wrong or hacked request!
				$Messages->add( T_('Quick registration is currently disabled on this system.'), 'error' );
				break;
			}

			if( $DB->get_var( 'SELECT user_ID FROM T_users WHERE user_email = '.$DB->quote( utf8_strtolower( $email ) ) ) )
			{ // Don't allow the duplicate emails
				$Messages->add( sprintf( T_('You already registered on this site. You can <a %s>log in here</a>. If you don\'t know or have forgotten it, you can <a %s>set your password here</a>.'),
					'href="'.$Blog->get( 'loginurl' ).'"',
					'href="'.$Blog->get( 'lostpasswordurl' ).'"' ), 'warning' );
				break;
			}

			// Initialize the widget settings
			$user_register_Widget->init_display( array() );

			// Get a source from widget setting
			$source = $user_register_Widget->disp_params['source'];

			// Check what fields should be required by current widget
			$registration_require_country = false;
			$registration_require_gender = false;
			$registration_require_firstname = ( $user_register_Widget->disp_params['ask_firstname'] == 'required' );
			$registration_require_lastname = ( $user_register_Widget->disp_params['ask_lastname'] == 'required' );

			// Check what subscriptions should be activated by current widget
			$auto_subscribe_posts = ! empty( $user_register_Widget->disp_params['subscribe_post'] );
			$auto_subscribe_comments = ! empty( $user_register_Widget->disp_params['subscribe_comment'] );
		}

		if( ! $is_quick )
		{
			/*
			 * Do the registration:
			 */
			$pass1 = param( $dummy_fields['pass1'], 'string', '' );
			$pass2 = param( $dummy_fields['pass2'], 'string', '' );

			// Remove the invalid chars from password vars
			$pass1 = preg_replace( '/[<>&]/', '', $pass1 );
			$pass2 = preg_replace( '/[<>&]/', '', $pass2 );

			// Call plugin event to allow catching input in general and validating own things from DisplayRegisterFormFieldset event
			$Plugins->trigger_event( 'RegisterFormSent', array(
					'login'     => & $login,
					'email'     => & $email,
					'country'   => & $country,
					'firstname' => & $firstname,
					'gender'    => & $gender,
					'locale'    => & $locale,
					'pass1'     => & $pass1,
					'pass2'     => & $pass2,
				) );
		}

		if( $Messages->has_errors() )
		{ // a Plugin has added an error
			break;
		}

		// Set params:
		if( $is_quick )
		{ // For quick registration
			$paramsList = array( 'email' => $email );
		}
		else
		{ // For normal registration
			$paramsList = array(
				'login'   => $login,
				'pass1'   => $pass1,
				'pass2'   => $pass2,
				'email'   => $email,
				'pass_required' => true );
		}

		if( $registration_require_country )
		{
			$paramsList['country'] = $country;
		}

		if( $registration_require_firstname )
		{
			$paramsList['firstname'] = $firstname;
		}

		if( $registration_require_lastname )
		{
			$paramsList['lastname'] = $lastname;
		}

		if( $registration_require_gender == 'required' )
		{
			$paramsList['gender'] = $gender;
		}

		if( $Settings->get( 'newusers_canregister' ) == 'invite' )
		{ // Invitation code must be not empty when user can register ONLY with this code
			$paramsList['invitation'] = get_param( 'invitation' );
		}

		// Check profile params:
		profile_check_params( $paramsList );

		if( $is_quick && ! $Messages->has_errors() )
		{ // Generate a login and password for quick registration
			$pass1 = generate_random_passwd( 10 );

			// Get the login from email address:
			$login = preg_replace( '/^([^@]+)@(.+)$/', '$1', utf8_strtolower( $email ) );
			$login = preg_replace( '/[\'"><@\s]/', '', $login );
			if( $Settings->get( 'strict_logins' ) )
			{ // We allow only the plain ACSII characters, digits, the chars _ and .
				$login = preg_replace( '/[^A-Za-z0-9_.]/', '', $login );
			}
			else
			{ // We allow any character that is not explicitly forbidden in Step 1
				// Enforce additional limitations
				$login = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '', $login ); // Kill octets
				$login = preg_replace( '/&.+?;/', '', $login ); // Kill entities
			}
			$login = preg_replace( '/^usr_/i', '', $login );

			// Check and search free login name if current is busy
			$login_name = $login;
			$login_number = 1;
			$UserCache = & get_UserCache();
			while( empty( $login_name ) || $UserCache->get_by_login( $login_name ) )
			{
				$login_name = $login.$login_number;
				$login_number++;
			}
			$login = $login_name;
		}

		if( ! $is_quick )
		{
			// We want all logins to be lowercase to guarantee uniqueness regardless of the database case handling for UNIQUE indexes:
			$login = utf8_strtolower( $login );

			$UserCache = & get_UserCache();
			if( $UserCache->get_by_login( $login ) )
			{ // The login is already registered
				param_error( $dummy_fields[ 'login' ], sprintf( T_('The login &laquo;%s&raquo; is already registered, please choose another one.'), $login ) );
			}
		}

		if( $Messages->has_errors() )
		{ // Stop registration if the errors exist
			break;
		}

		$DB->begin();

		$new_User = new User();
		$new_User->set( 'login', $login );
		$new_User->set_password( $pass1 );
		$new_User->set( 'ctry_ID', $country );
		$new_User->set( 'firstname', $firstname );
		$new_User->set( 'lastname', $lastname );
		$new_User->set( 'gender', $gender );
		$new_User->set( 'source', $source );
		$new_User->set_email( $email );
		$new_User->set_datecreated( $localtimenow );
		if( $registration_ask_locale )
		{ // set locale if it was prompted, otherwise let default
			$new_User->set( 'locale', $locale );
		}

		if( ! empty( $invitation ) )
		{ // Invitation code was entered on the form
			$SQL = new SQL();
			$SQL->SELECT( 'ivc_source, ivc_grp_ID' );
			$SQL->FROM( 'T_users__invitation_code' );
			$SQL->WHERE( 'ivc_code = '.$DB->quote( $invitation ) );
			$SQL->WHERE_and( 'ivc_expire_ts > '.$DB->quote( date( 'Y-m-d H:i:s', $localtimenow ) ) );
			if( $invitation_code = $DB->get_row( $SQL->get() ) )
			{ // Set source and group from invitation code
				$new_User->set( 'source', $invitation_code->ivc_source );
				$GroupCache = & get_GroupCache();
				if( $new_user_Group = & $GroupCache->get_by_ID( $invitation_code->ivc_grp_ID, false, false ) )
				{
					$new_User->set_Group( $new_user_Group );
				}
			}
		}

		if( $new_User->dbinsert() )
		{ // Insert system log about user's registration
			syslog_insert( 'User registration', 'info', 'user', $new_User->ID );
		}

		$new_user_ID = $new_User->ID; // we need this to "rollback" user creation if there's no DB transaction support

		// TODO: Optionally auto create a blog (handle this together with the LDAP plugin)

		// TODO: Optionally auto assign rights

		// Actions to be appended to the user registration transaction:
		if( $Plugins->trigger_event_first_false( 'AppendUserRegistrTransact', array( 'User' => & $new_User ) ) )
		{
			// TODO: notify the plugins that have been called before about canceling of the event?!
			$DB->rollback();

			// Delete, in case there's no transaction support:
			$new_User->dbdelete( $Debuglog );

			$Messages->add( T_('No user account has been created!'), 'error' );
			break; // break out to _reg_form.php
		}

		// User created:
		$DB->commit();
		$UserCache->add( $new_User );

		$initial_hit = $new_User->get_first_session_hit_params( $Session->ID );
		if( ! empty ( $initial_hit ) )
		{	// Save User Settings
			$UserSettings->set( 'initial_blog_ID' , $initial_hit->hit_coll_ID, $new_User->ID );
			$UserSettings->set( 'initial_URI' , $initial_hit->hit_uri, $new_User->ID );
			$UserSettings->set( 'initial_referer' , $initial_hit->hit_referer , $new_User->ID );
		}
		if( !empty( $session_registration_trigger_url ) )
		{	// Save Trigger page
			$UserSettings->set( 'registration_trigger_url' , $session_registration_trigger_url, $new_User->ID );
		}
		$UserSettings->set( 'created_fromIPv4', ip2int( $Hit->IP ), $new_User->ID );
		$UserSettings->set( 'user_domain', $Hit->get_remote_host( true ), $new_User->ID );
		$UserSettings->set( 'user_browser', substr( $Hit->get_user_agent(), 0 , 200 ), $new_User->ID );
		$UserSettings->dbupdate();

		// Auto subscribe new user to current collection posts/comments:
		if( $auto_subscribe_posts || $auto_subscribe_comments )
		{ // If at least one option is enabled
			$DB->query( 'REPLACE INTO T_subscriptions ( sub_coll_ID, sub_user_ID, sub_items, sub_comments )
					VALUES ( '.$DB->quote( $Blog->ID ).', '.$DB->quote( $new_User->ID ).', '.$DB->quote( intval( $auto_subscribe_posts ) ).', '.$DB->quote( intval( $auto_subscribe_comments ) ).' )' );
		}

		// Send notification email about new user registrations to users with edit users permission
		$email_template_params = array(
				'country'     => $country,
				'firstname'   => $firstname,
				'gender'      => $gender,
				'locale'      => $locale,
				'source'      => $new_User->get( 'source' ),
				'trigger_url' => $session_registration_trigger_url,
				'initial_hit' => $initial_hit,
				'login'       => $login,
				'email'       => $email,
				'new_user_ID' => $new_User->ID,
			);
		send_admin_notification( NT_('New user registration'), 'account_new', $email_template_params );

		$Plugins->trigger_event( 'AfterUserRegistration', array( 'User' => & $new_User ) );
		// Move user to suspect group by IP address. Make this move even if during the registration it was added to a trusted group.
		antispam_suspect_user_by_IP( '', $new_User->ID, false );

		if( $Settings->get('newusers_mustvalidate') )
		{ // We want that the user validates his email address:
			$inskin_blog = $inskin ? $blog : NULL;
			if( $new_User->send_validate_email( $redirect_to, $inskin_blog ) )
			{
				$activateinfo_link = 'href="'.get_activate_info_url( NULL, '&amp;' ).'"';
				$Messages->add( sprintf( T_('An email has been sent to your email address. Please click on the link therein to activate your account. <a %s>More info &raquo;</a>'), $activateinfo_link ), 'success' );
			}
			elseif( $demo_mode )
			{
				$Messages->add( 'Sorry, could not send email. Sending email in demo mode is disabled.', 'error' );
			}
			else
			{
				$Messages->add( T_('Sorry, the email with the link to activate your account could not be sent.')
					.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
				// fp> TODO: allow to enter a different email address (just in case it's that kind of problem)
			}
		}
		else
		{ // Display this message after successful registration and without validation email
			$Messages->add( T_('You have successfully registered on this site. Welcome!'), 'success' );
		}

		// Autologin the user. This is more comfortable for the user and avoids
		// extra confusion when account validation is required.
		$Session->set_User( $new_User );

		// Set redirect_to pending from after_registration setting
		$after_registration = $Settings->get( 'after_registration' );
		if( $after_registration == 'return_to_original' )
		{ // Return to original page ( where user was before the registration process )
			if( empty( $redirect_to ) )
			{ // redirect_to param was not set
				if( $inskin && !empty( $Blog ) )
				{
					$redirect_to = $Blog->gen_blogurl();
				}
				else
				{
					$redirect_to = $baseurl;
				}
			}
		}
		else
		{ // Return to the specific URL which is set in the registration settings form
			$redirect_to = $after_registration;
		}

		header_redirect( $redirect_to );
		break;


	case 'disabled':
		/*
		 * Registration disabled:
		 */
		$params = array(
				'register_form_title' => T_('Registration Currently Disabled'),
				'wrap_width'          => '350px',
			);
		require $adminskins_path.'login/_reg_form.main.php';

		exit(0);
}

if( ! empty( $is_quick ) )
{ // Redirect to previous page everytime when quick registration is used, even when errors exist
	if( ! empty( $param_input_err_messages ) )
	{ // Save all param errors in Session because of the redirect below
		$Session->set( 'param_input_err_messages_'.$widget, $param_input_err_messages );
	}
	$param_input_values = array(
			$dummy_fields['email'] => $email,
			'firstname'            => $firstname,
			'lastname'             => $lastname
		);
	$Session->set( 'param_input_values_'.$widget, $param_input_values );
	header_redirect( $redirect_to );
}

/*
 * Default: registration form:
 */
if( $inskin && !empty( $Blog ) )
{ // in-skin display
	$SkinCache = & get_SkinCache();
	$Skin = & $SkinCache->get_by_ID( $Blog->get_skin_ID() );
	$skin = $Skin->folder;
	$disp = 'register';
	$ads_current_skin_path = $skins_path.$skin.'/';
	require $ads_current_skin_path.'index.main.php';
	// already exited here
	exit(0);
}

// Load jQuery library and functions to work with ajax response
require_js( '#jquery#' );
require_js( 'ajax.js' );

// Display reg form:
require $adminskins_path.'login/_reg_form.main.php';

?>