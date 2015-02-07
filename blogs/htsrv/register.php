<?php
/**
 * Register a new user.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: register.php 8076 2015-01-26 15:41:38Z yura $
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
// Check if gender is required
$registration_require_gender = $Settings->get('registration_require_gender');
// Check if registration ask for locale
$registration_ask_locale = $Settings->get('registration_ask_locale');

$login = param( $dummy_fields[ 'login' ], 'string', '' );
$email = evo_strtolower( param( $dummy_fields[ 'email' ], 'string', '' ) );
param( 'action', 'string', '' );
param( 'country', 'integer', '' );
param( 'firstname', 'string', '' );
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

if( ! $Settings->get('newusers_canregister') )
{
	$action = 'disabled';
}

if( $register_user = $Session->get('core.register_user') )
{	// Get an user data from predefined session (after adding of a comment)
	$login = preg_replace( '/[^a-z0-9 ]/i', '', $register_user['name'] );
	$login = str_replace( ' ', '_', $login );
	$login = evo_substr( $login, 0, 20 );
	$email = $register_user['email'];

	$Session->delete( 'core.register_user' );
}

switch( $action )
{
	case 'register':
		// Stop a request from the blocked IP addresses or Domains
		antispam_block_request();

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'regform' );

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

		if( $Messages->has_errors() )
		{ // a Plugin has added an error
			break;
		}

		// Set params:
		$paramsList = array(
			'login'   => $login,
			'pass1'   => $pass1,
			'pass2'   => $pass2,
			'email'   => $email,
			'pass_required' => true );

		if( $registration_require_country )
		{
			$paramsList['country'] = $country;
		}

		if( $registration_require_firstname )
		{
			$paramsList['firstname'] = $firstname;
		}

		if( $registration_require_gender == 'required' )
		{
			$paramsList['gender'] = $gender;
		}

		// Check profile params:
		profile_check_params( $paramsList );

		// We want all logins to be lowercase to guarantee uniqueness regardless of the database case handling for UNIQUE indexes:
		$login = evo_strtolower( $login );

		$UserCache = & get_UserCache();
		if( $UserCache->get_by_login( $login ) )
		{ // The login is already registered
			param_error( $dummy_fields[ 'login' ], sprintf( T_('The login &laquo;%s&raquo; is already registered, please choose another one.'), $login ) );
		}

		if( $Messages->has_errors() )
		{
			break;
		}

		$DB->begin();

		$new_User = new User();
		$new_User->set( 'login', $login );
		$new_User->set( 'pass', md5($pass1) ); // encrypted
		$new_User->set( 'ctry_ID', $country );
		$new_User->set( 'firstname', $firstname );
		$new_User->set( 'gender', $gender );
		$new_User->set( 'source', $source );
		$new_User->set_email( $email );
		$new_User->set_datecreated( $localtimenow );
		if( $registration_ask_locale )
		{ // set locale if it was prompted, otherwise let default
			$new_User->set( 'locale', $locale );
		}

		$new_User->dbinsert();

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

		// Send notification email about new user registrations to users with edit users permission
		$email_template_params = array(
				'country'     => $country,
				'firstname'   => $firstname,
				'gender'      => $gender,
				'locale'      => $locale,
				'source'      => $source,
				'trigger_url' => $session_registration_trigger_url,
				'initial_hit' => $initial_hit,
				'login'       => $login,
				'email'       => $email,
				'new_user_ID' => $new_User->ID,
			);
		send_admin_notification( NT_('New user registration'), 'account_new', $email_template_params );

		$Plugins->trigger_event( 'AfterUserRegistration', array( 'User' => & $new_User ) );


		if( $Settings->get('newusers_mustvalidate') )
		{ // We want that the user validates his email address:
			$inskin_blog = $inskin ? $blog : NULL;
			if( $new_User->send_validate_email( $redirect_to, $inskin_blog ) )
			{
				if( $inskin && !empty( $Blog ) )
				{
					$activateinfo_link = 'href="'.url_add_param( $Blog->gen_blogurl(), 'disp=activateinfo' ).'"';
				}
				else
				{
					$activateinfo_link = 'href="'.$secure_htsrv_url.'login.php?action=req_validatemail'.'"';
				}
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
		require $adminskins_path.'login/_reg_disabled.main.php';

		exit(0);
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