<?php
/**
 * This file implements login/logout handling functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author cafelog (team)
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author jeffbearer: Jeff BEARER - {@link http://www.jeffbearer.com/}.
 * @author jupiterx: Jordan RUNNING.
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__). '/_group.funcs.php';
require_once dirname(__FILE__). '/_user.class.php';


/**
 * veriflog(-)
 *
 * Verify if user is logged in
 * checking login & pass in the database
 */
function veriflog( $login_required = false )
{
	global $cookie_user, $cookie_pass, $cookie_expires, $cookie_path, $cookie_domain, $error;
	global $UserCache, $current_User;

	// Reset all global variables in case some tricky stuff is trying to set them otherwise:
	// Warning: unset() prevent from setting a new global value later in the func !!! :((
	$user_login = '';
	$user_pass_md5 = '';

	// Check if user is trying to login right now:
	if( isset($_POST['log'] ) && isset($_POST['pwd'] ))
	{ // Trying to log in with a POST
		$log = strtolower(trim(strip_tags(get_magic_quotes_gpc() ? stripslashes($_POST['log']) : $_POST['log'])));
		$user_pass_md5 = md5(trim(strip_tags(get_magic_quotes_gpc() ? stripslashes($_POST['pwd']) : $_POST['pwd'])));
		unset($_POST['pwd']); // password is hashed from now on
	}
	elseif( isset($_GET['log'] ) && isset($_GET['pwd'] ))
	{ // Trying to log in with a GET
		$log = strtolower(trim(strip_tags(get_magic_quotes_gpc() ? stripslashes($_GET['log']) : $_GET['log'])));
		$user_pass_md5 = md5(trim(strip_tags(get_magic_quotes_gpc() ? stripslashes($_GET['pwd']) : $_GET['pwd'])));
		unset($_GET['pwd']); // password is hashed from now on
	}

	if( isset($log) )
	{ /*
		 * ---------------------------------------------------------
		 * User is trying to login right now
		 * ---------------------------------------------------------
		 */
		// echo 'Trying to log in right now...';

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");

		// Check login and password
		$user_login = $log;
		if( !( $login_ok = user_pass_ok( $user_login, $user_pass_md5, true ) ) )
		{
			// echo 'login failed!!';
			return '<strong>'. T_('ERROR'). ':</strong> '. T_('wrong login/password.');
		}

		// Login succeeded:
		//echo $user_login, $pass_is_md5, $user_pass,  $cookie_domain;
		if( !setcookie( $cookie_user, $log, $cookie_expires, $cookie_path, $cookie_domain ) )
			printf( T_('setcookie &laquo;%s&raquo; failed!'). '<br />', $cookie_user );
		if( !setcookie( $cookie_pass, $user_pass_md5, $cookie_expires, $cookie_path, $cookie_domain) )
			printf( T_('setcookie &laquo;%s&raquo; failed!'). '<br />', $cookie_user );
	}
	elseif( isset($_COOKIE[$cookie_user]) && isset($_COOKIE[$cookie_pass]) )
	{ /*
		 * ---------------------------------------------------------
		 * User was not trying to log in, but he already was logged in: check validity
		 * ---------------------------------------------------------
		 */
		// echo 'Was already logged in...';

		$user_login = trim(strip_tags(get_magic_quotes_gpc() ? stripslashes($_COOKIE[$cookie_user]) : $_COOKIE[$cookie_user]));
		$user_pass_md5 = trim(strip_tags(get_magic_quotes_gpc() ? stripslashes($_COOKIE[$cookie_pass]) : $_COOKIE[$cookie_pass]));
		// echo 'pass=', $user_pass_md5;

		if( ! user_pass_ok( $user_login, $user_pass_md5, true ) )
		{ // login is NOT OK:
			if( $login_required )
			{
				header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
				header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
				header("Cache-Control: no-cache, must-revalidate");
				header("Pragma: no-cache");

				return '<strong>'. T_('ERROR'). ':</strong> '. T_('login/password no longer valid.');
			}

			return 0;	// Wrong login but we don't care.
		}
	}
	else
	{ /*
		 * ---------------------------------------------------------
		 * User was not logged in at all
		 * ---------------------------------------------------------
		 */
		// echo ' NOT logged in...';

		if( $login_required )
		{
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-cache, must-revalidate");
			header("Pragma: no-cache");

			return T_('You must log in!');
			exit();
		}

		return 0;	// Not logged in but we don't care
	}

	/*
	 * Login info is OK, we set the global variables:
	 */
	// echo 'LOGGED IN';


	if( $current_User = $UserCache->get_by_login( $user_login ) )
	{
		return 0; // OK
	}
	else
	{
		return 'Error while loading user data!';
	}
	#echo $current_User->disp('login');
}


/**
 * Log the user out
 */
function logout()
{
	global $cookie_user, $cookie_pass, $cookie_expired, $cookie_path, $cookie_domain;
	global $current_User;

	// Reset all global variables
	// Note: unset is bugguy on globals
	$current_User = false;

	setcookie( 'cafeloguser' );		// OLD
	setcookie( 'cafeloguser', '', $cookie_expired, $cookie_path, $cookie_domain); // OLD
	setcookie( $cookie_user, '', $cookie_expired, $cookie_path, $cookie_domain);

	setcookie( 'cafelogpass' );		// OLD
	setcookie( 'cafelogpass', '', $cookie_expired, $cookie_path, $cookie_domain);	// OLD
	setcookie( $cookie_pass, '', $cookie_expired, $cookie_path, $cookie_domain);
}


/**
 * is_logged_in(-)
 */
function is_logged_in()
{
	global $generating_static, $current_User;

	if( isset($generating_static) )
	{ // When generating static page, we should always consider we are not logged in.
		return false;
	}

	return is_object( $current_User ) && !empty( $current_User->ID );
}


/**
 * Check if a password is ok for a login.
 *
 * @param string login
 * @param string password
 * @param boolean Is the password parameter already MD5()'ed?
 * @return boolean
 */
function user_pass_ok( $login, $pass, $pass_is_md5 = false )
{
	global $UserCache;

	$User =& $UserCache->get_by_login( $login );
	// echo 'got data for: ', $User->login;

	if( !$pass_is_md5 )
	{
		$pass = md5( $pass );
	}
	// echo 'pass: ', $pass, '/', $User->pass;

	return ( $pass == $User->pass );
}


/**
 * Template tag: Provide a link to login
 */
function user_login_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	global $htsrv_url, $edited_Blog, $generating_static;

	if( is_logged_in() ) return false;

	if( $link_text == '' ) $link_text = T_('Login...');
	if( $link_title == '#' ) $link_title = T_('Login if you have an account...');

	if( !isset($generating_static) )
	{ // We are not generating a static page here:
		$redirect = '?redirect_to='.urlencode( regenerate_url() );
	}
	elseif( isset($edited_Blog) )
	{ // We are generating a static page
		$redirect = '?redirect_to='.$edited_Blog->get('dynurl');
	}
	else
	{ // We are in a weird situation
		$redirect = '';
	}

	echo $before;
	echo '<a href="'.$htsrv_url.'login.php'.$redirect.'" title="'.$link_title.'">';
	echo $link_text;
	echo '</a>';
	echo $after;
}


/**
 * Template tag: Provide a link to new user registration
 */
function user_register_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	global $htsrv_url, $Settings, $edited_Blog, $generating_static;

	if( is_logged_in() || !$Settings->get('newusers_canregister'))
	{ // There's no need to provide this link if already logged in or if we won't let him register
		return false;
	}

	if( $link_text == '' ) $link_text = T_('Register...');
	if( $link_title == '#' ) $link_title = T_('Register to open an account...');

	if( !isset($generating_static) )
	{ // We are not generating a static page here:
		$redirect = '?redirect_to='.urlencode( regenerate_url() );
	}
	elseif( isset($edited_Blog) )
	{ // We are generating a static page
		$redirect = '?redirect_to='.$edited_Blog->get('dynurl');
	}
	else
	{ // We are in a weird situation
		$redirect = '';
	}

	echo $before;
	echo '<a href="'.$htsrv_url.'register.php'.$redirect.'" title="'.$link_title.'">';
	echo $link_text;
	echo '</a>';
	echo $after;
}


/**
 * Template tag: Provide a link to logout
 */
function user_logout_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	global $htsrv_url, $current_User, $blog;

	if( ! is_logged_in() )
	{
		return false;
	}

	if( $link_text == '' ) $link_text = T_('Logout (%s)');
	if( $link_title == '#' ) $link_title = T_('Logout from your account');

	echo $before;
	echo '<a href="'.$htsrv_url.'login.php?action=logout&amp;redirect_to='.urlencode( regenerate_url() ).'" title="'.$link_title.'">';
	printf( $link_text, $current_User->login );
	echo '</a>';
	echo $after;
}


/**
 * Template tag: Provide a link to the backoffice
 */
function user_admin_link( $before = '', $after = '', $page = 'b2edit.php', $link_text = '', $link_title = '#' )
{
	global $admin_url, $blog, $current_User;

	if( ! is_logged_in() ) return false;

	if( $current_User->get('level') == 0 )
	{ // If user is NOT active:
		return false;
	}

	if( $link_text == '' ) $link_text = T_('Admin');
	if( $link_title == '#' ) $link_title = T_('Go to the back-office');
	// add the blog param to $page if it is not already in there
	if( !preg_match('/(&|&amp;|\?)blog=/', $page) ) $page = url_add_param( $page, 'blog='.$blog );

	echo $before;
	echo '<a href="'.$admin_url.$page.'" title="'.$link_title.'">';
	echo $link_text ;
	echo '</a>';
	echo $after;
}


/**
 * Template tag: Provide a link to user profile
 */
function user_profile_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	global $current_User, $pagenow, $Blog;

	if( ! is_logged_in() )
	{
		return false;
	}

	if( $link_text == '' ) $link_text = T_('Profile (%s)');
	if( $link_title == '#' ) $link_title = T_('Edit your profile');

	echo $before;
	echo '<a href="'.url_add_param( $Blog->dget( 'blogurl', 'raw' ), 'disp=profile&amp;redirect_to='.urlencode(regenerate_url()) )
			.'" title="', $link_title, '">';
	printf( $link_text, $current_User->login );
	echo '</a>';
	echo $after;
}


/**
 * Template tag: Display the user's prefered name
 *
 * Used in result lists.
 *
 * @param integer user ID
 */
function user_preferedname( $user_ID )
{
	global $UserCache;

	if( !empty( $user_ID ) && $User =& $UserCache->get_by_ID( $user_ID ) )
	{
		$User->disp('preferedname');
	}
}


/**
 * Display "User profile" title if it has been requested
 *
 * {@internal profile_title(-) }}
 *
 * @param string Prefix to be displayed if something is going to be displayed
 * @param mixed Output format, see {@link format_to_output()} or false to
 *              return value instead of displaying it
 */
function profile_title( $prefix = ' ', $display = 'htmlbody' )
{
	global $disp;

	if( $disp == 'profile' )
	{
		$info = $prefix.T_('User profile');
		if ($display)
			echo format_to_output( $info, $display );
		else
			return $info;
	}
}


/**
 * Check profile parameters and add errors to {@link $Messages}.
 *
 * @param array associative array
 *              'nickname': is mandatory
 *              'icq': must be a number
 *              'email': mandatory, must be well formed
 *              'url': must be well formed, in allowed scheme, not blacklisted
 *              'pass1' / 'pass2': passwords (twice), must be the same
 */
function profile_check_params( $params )
{
	global $Messages, $Settings;
	global $comments_allowed_uri_scheme;


	if( !is_array($params) )
	{
		$params = array( $params );
	}

	// checking login has been typed:
	if( isset($params['login']) && empty($params['login']) )
	{
		$Messages->add( T_('Please enter a login.') );
	}

	// checking the nickname has been typed
	if( isset($params['nickname']) && empty($params['nickname']) )
	{
		$Messages->add( T_('Please enter a nickname (can be the same as your login).') );
	}

	// if the ICQ UIN has been entered, check to see if it has only numbers
	if( !empty($params['icq']) )
	{
		if( !preg_match( '^[0-9]+$', $params['icq']) )
		{
			$Messages->add( T_('The ICQ UIN can only be a number, no letters allowed.') );
		}
	}

	// checking e-mail address
	if( isset($params['email']) )
	{
		if( empty($params['email']) )
		{
			$Messages->add( T_('Please enter an e-mail address.') );
		}
		elseif( !is_email($params['email']) )
		{
			$Messages->add( T_('The email address is invalid.') );
		}
	}

	// Checking URL:
	if( isset($params['url']) )
	{
		if( $error = validate_url( $params['url'], $comments_allowed_uri_scheme ) )
		{
			$Messages->add( T_('Supplied URL is invalid: ') . $error );
		}
	}

	// Check passwords:
	if( isset($params['pass1']) )
	{
		// checking the password has been typed twice
		if( empty($params['pass1']) || empty($params['pass2']) )
		{
			$Messages->add( T_('Please enter your password twice.') );
		}

		// checking the password has been typed twice the same:
		if( $params['pass1'] != $params['pass2'] )
		{
			$Messages->add( T_('You typed two different passwords.') );
		}
		elseif( strlen($params['pass1']) < $Settings->get('user_minpwdlen')
						|| strlen($params['pass2']) < $Settings->get('user_minpwdlen') )
		{
			$Messages->add( sprintf( T_('The mimimum password length is %d characters.'), $Settings->get('user_minpwdlen')) );
		}
	}
}

/*
 * $Log$
 * Revision 1.15  2005/02/20 23:03:24  blueyed
 * profile_check_params() enhanced
 *
 * Revision 1.14  2005/02/19 18:20:47  blueyed
 * obsolete functions removed
 *
 * Revision 1.13  2005/02/15 22:05:10  blueyed
 * Started moving obsolete functions to _obsolete092.php..
 *
 * Revision 1.12  2005/02/09 00:27:13  blueyed
 * Removed deprecated globals / userdata handling
 *
 * Revision 1.11  2005/02/08 20:17:57  blueyed
 * removed obsolete $User_ID global
 *
 * Revision 1.10  2005/02/08 03:06:26  blueyed
 * marked get_user_info() as deprecated
 *
 * Revision 1.9  2005/01/03 19:15:15  fplanque
 * no message
 *
 * Revision 1.7  2004/12/30 23:07:02  blueyed
 * removed obsolete $user_nickname
 *
 * Revision 1.6  2004/12/15 20:50:34  fplanque
 * heavy refactoring
 * suppressed $use_cache and $sleep_after_edit
 * code cleanup
 *
 * Revision 1.5  2004/12/10 19:45:55  fplanque
 * refactoring
 *
 * Revision 1.4  2004/11/15 18:57:05  fplanque
 * cosmetics
 *
 * Revision 1.3  2004/10/15 17:51:38  fplanque
 * added user_preferedname()
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.61  2004/10/12 18:48:34  fplanque
 * Edited code documentation.
 *
 * Revision 1.47  2004/5/28 17:22:31  jeffbearer
 * added the showonline case
 *
 * Revision 1.3  2003/8/28 1:48:30  jupiterx
 * Added MD5 password hashing; misc. code cleanup
 */
?>