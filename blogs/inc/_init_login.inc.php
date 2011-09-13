<?php
/**
 * This file initializes everything BUT the blog!
 *
 * It is useful when you want to do very customized templates!
 * It is also called by more complete initializers.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 * @author mfollett: Matt FOLLETT
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
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

global $login_error;

$login = NULL;
$pass = NULL;
$pass_md5 = NULL;

if( isset($_POST['login'] ) && isset($_POST['pwd'] ) )
{ // Trying to log in with a POST
	$login = $_POST['login'];
	$pass = $_POST['pwd'];
	unset($_POST['pwd']); // password will be hashed below
}
elseif( isset($_GET['login'] ) )
{ // Trying to log in with a GET; we might only provide a user here.
	$login = $_GET['login'];
	$pass = isset($_GET['pwd']) ? $_GET['pwd'] : '';
	unset($_GET['pwd']); // password will be hashed below
}

$Debuglog->add( 'Login: login: '.var_export($login, true), 'request' );
$Debuglog->add( 'Login: pass: '.( empty($pass) ? '' : 'not' ).' empty', 'request' );

// either 'login' (normal) or 'redirect_to_backoffice' may be set here. This also helps to display the login form again, if either login or pass were empty.
$login_action = param_arrayindex( 'login_action' );

$UserCache = & get_UserCache();

if( ! empty($login_action) || (! empty($login) && ! empty($pass)) )
{ // User is trying to login right now
	$Debuglog->add( 'Login: User is trying to log in.', 'request' );

	header_nocache();		// Don't take risks here :p

	// Check that this login request is not a CSRF hacked request:
	$Session->assert_received_crumb( 'loginform' );

	// Note: login and password cannot include '<' !
	$login = evo_strtolower(strip_tags(remove_magic_quotes($login)));
	$pass = strip_tags(remove_magic_quotes($pass));
	$pass_md5 = md5( $pass );


	/*
	 * Handle javascript-hashed password:
	 * If possible, the login form will hash the entered password with a salt that changes everytime.
	 */
	param('pwd_salt', 'string', ''); // just for comparison with the one from Session
	$pwd_salt_sess = $Session->get('core.pwd_salt');

	// $Debuglog->add( 'Login: salt: '.var_export($pwd_salt, true).', session salt: '.var_export($pwd_salt_sess, true) );

	$transmit_hashed_password = (bool)$Settings->get('js_passwd_hashing') && !(bool)$Plugins->trigger_event_first_true('LoginAttemptNeedsRawPassword');
	if( $transmit_hashed_password )
	{
		param( 'pwd_hashed', 'string', '' );
	}
	else
	{ // at least one plugin requests the password un-hashed:
		$pwd_hashed = '';
	}

	// $Debuglog->add( 'Login: pwd_hashed: '.var_export($pwd_hashed, true).', pass: '.var_export($pass, true) );

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

		// Make sure that we can load the user:
		$User = & $UserCache->get_by_login($login);

		if( $User && ! $pass_ok )
		{ // check the password, if no plugin has said "it's ok":
			if( ! empty($pwd_hashed) )
			{ // password hashed by JavaScript:

				$Debuglog->add( 'Login: Hashed password available.', 'request' );

				if( empty($pwd_salt_sess) )
				{ // no salt stored in session: either cookie problem or the user had already tried logging in (from another window for example)
					$Debuglog->add( 'Login: Empty salt_sess!', 'request' );
					if( ($pos = strpos( $pass, '_hashed_' ) ) && substr($pass, $pos+8) == $Session->ID )
					{ // session ID matches, no cookie problem
						$login_error = T_('The login window has expired. Please try again.');
						$Debuglog->add( 'Login: Session ID matches.', 'request' );
					}
					else
					{ // more general error:
						$login_error = T_('Either you have not enabled cookies or this login window has expired.');
						$Debuglog->add( 'Login: Session ID does not match.', 'request' );
					}
				}
				elseif( $pwd_salt != $pwd_salt_sess )
				{ // submitted salt differs from the one stored in the session
					$login_error = T_('The login window has expired. Please try again.');
					$Debuglog->add( 'Login: Submitted salt and salt from Session do not match.', 'request' );
				}
				else
				{ // compare the password, using the salt stored in the Session:
					#pre_dump( sha1($User->pass.$pwd_salt), $pwd_hashed );
					$pass_ok = sha1($User->pass.$pwd_salt) == $pwd_hashed;
					$Session->delete('core.pwd_salt');
					$Debuglog->add( 'Login: Compared hashed passwords. Result: '.(int)$pass_ok, 'request' );
				}
			}
			else
			{
				$pass_ok = ( $User->pass == $pass_md5 );
				$Debuglog->add( 'Login: Compared raw passwords. Result: '.(int)$pass_ok, 'request' );
			}
		}
	}

	if( $pass_ok )
	{ // Login succeeded, set cookies
		$Debuglog->add( 'Login: User successfully logged in with username and password...', 'login');
		// set the user from the login that succeeded
		$current_User = & $UserCache->get_by_login($login);
		// save the user for later hits
		$Session->set_User( $current_User );
	}
	elseif( empty( $login_error ) )
	{ // if the login_error wasn't set yet, add the default one:
		// This will cause the login screen to "popup" (again)
		$login_error = T_('Wrong login/password.');
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

	$Debuglog->add( 'Login: Was already logged in... ['.$current_User->get('login').']', 'request' );
}
else
{ // The Session has no user or $login is given (and differs from current user), allow alternate authentication through Plugin:
	if( ($event_return = $Plugins->trigger_event_first_true( 'AlternateAuthentication' ))
	    && $Session->has_User()  # the plugin should have attached the user to $Session
	)
	{
		$Debuglog->add( 'Login: User has been authenticated through plugin #'.$event_return['plugin_ID'].' (AlternateAuthentication)', 'request' );
		$current_User = & $UserCache->get_by_ID( $Session->user_ID );
	}
	elseif( $login_required )
	{ /*
		 * ---------------------------------------------------------
		 * User was not logged in at all, but login is required
		 * ---------------------------------------------------------
		 */
		// echo ' NOT logged in...';
		$Debuglog->add( 'Login: NOT logged in... (did not try)', 'request' );

		$login_error = T_('You must log in!');
	}
}
unset($pass);


// Check if the user needs to be validated, but is not yet:
// TODO: dh> this block prevents registration, if you are logged in already, but not validated!
//       (e.g. when registered as "foo", you cannot register as "bar" until you logout (but there's no link in sight)
//        or validate the "foo" account)
if( ! empty($current_User)
		&& ! $current_User->validated
		&& $Settings->get('newusers_mustvalidate') // same check as in login.php
		&& param('action', 'string', '') != 'logout' ) // fp> TODO: non validated users should be automatically logged out
{ // efy-asimo> It's not a good idea to automatically log out the user, because needs to send a validation email.
	if( $action != 'req_validatemail' && $action != 'validatemail' )
	{ // we're not in that action already:
		$action = 'req_validatemail'; // for login.php
		$login_error = T_('You must validate your email address before you can continue as a logged in user.');
	}
}
else
{ // Trigger plugin event that allows the plugins to re-act on the login event:
	// TODO: dh> these events should provide a flag "login_attempt_failed".
	if( empty($current_User) )
	{
		$Plugins->trigger_event( 'AfterLoginAnonymousUser', array() );
	}
	else
	{
		$Plugins->trigger_event( 'AfterLoginRegisteredUser', array() );

		if( ! empty($login_action) )
		{ // We're coming from the Login form and need to redirect to the requested page:
			if( $login_action == 'redirect_to_backoffice' )
			{ // user pressed the "Log into backoffice!" button
				$redirect_to = $admin_url;
			}
			else
			{
				param( 'redirect_to', 'string', $baseurl );
			}

			header_redirect( $redirect_to );
			exit(0);
		}
	}
}

if( ! empty( $login_error ) )
{
	param( 'inskin', 'boolean', 0 );
	if( $inskin || use_in_skin_login() )
	{ // Use in-skin login
		if( is_logged_in() )
		{ // user is logged in, but the email address is not validated yet
			$login = $current_User->login;
			$email = $current_User->email;
		}
		if( empty( $Blog ) )
		{
			if( isset( $blog) && $blog > 0 )
			{
				$BlogCache = & get_BlogCache();
				$Blog = $BlogCache->get_by_ID( $blog, false, false );
			}
		}
		if( ( !empty( $Blog ) ) && ( !empty( $Blog->skin_ID ) ) )
		{
			$Messages->add( $login_error );
			$SkinCache = & get_SkinCache();
			$Skin = & $SkinCache->get_by_ID( $Blog->skin_ID );
			$skin = $Skin->folder;
			$disp = 'login';
			$redirect_to = $Blog->gen_baseurl();
			$ads_current_skin_path = $skins_path.$skin.'/';
			require $ads_current_skin_path.'index.main.php';
			exit(0);
		}
	}

	// Use standard login
	// Init charset handling:
	init_charsets( $current_charset );
	require $htsrv_path.'login.php';
	exit(0);
}

$Timer->pause( '_init_login' );


/*
 * $Log$
 * Revision 1.9  2011/09/13 08:32:30  efy-asimo
 * Add crumb check for login and register
 * Never cache in-skin login and register
 * Fix page caching
 *
 * Revision 1.8  2011/09/04 22:13:13  fplanque
 * copyright 2011
 *
 * Revision 1.7  2011/06/14 13:33:55  efy-asimo
 * in-skin register
 *
 * Revision 1.6  2011/03/24 15:15:05  efy-asimo
 * in-skin login - feature
 *
 * Revision 1.5  2011/02/15 05:31:53  sam2kb
 * evo_strtolower mbstring wrapper for strtolower function
 *
 * Revision 1.4  2010/11/18 15:09:16  efy-asimo
 * create $login_error global variable
 *
 * Revision 1.3  2010/04/12 19:14:31  blueyed
 * doc
 *
 * Revision 1.2  2010/02/08 17:51:25  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.1  2009/12/06 05:20:36  fplanque
 * Violent refactoring for _main.inc.php
 * Sorry for potential side effects.
 * This needed to be done badly -- for clarity!
 *
 */
?>
