<?php
/**
 * This file implements login/logout handling functions.
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
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_group.class.php';
require_once dirname(__FILE__).'/_user.class.php';


/**
 * Log the user out
 */
function logout()
{
	global $current_User, $Session, $Plugins;

	$Plugins->trigger_event( 'Logout', array( 'User' => $current_User ) );

	// Reset all global variables
	// Note: unset is bugguy on globals
	$current_User = NULL; // NULL, as we do isset() on it in several places!

	$Session->logout();
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
	$UserCache = & get_Cache( 'UserCache' );
	$User = & $UserCache->get_by_login( $login );
	if( !$User )
	{
		return false;
	}
	// echo 'got data for: ', $User->login;

	if( !$pass_is_md5 )
	{
		$pass = md5( $pass );
	}
	// echo 'pass: ', $pass, '/', $User->pass;

	return ( $pass == $User->pass );
}


/**
 * Template tag: Output link to login
 */
function user_login_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	echo get_user_login_link( $before, $after, $link_text, $link_title );
}


/**
 * Template tag: Get link to login
 */
function get_user_login_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	global $htsrv_url_sensitive, $edited_Blog, $generating_static;

	if( is_logged_in() ) return false;

	if( $link_text == '' ) $link_text = T_('Login...');
	if( $link_title == '#' ) $link_title = T_('Login if you have an account...');

	if( !isset($generating_static) )
	{ // We are not generating a static page here:
		$redirect = regenerate_url( '', '', '', '&' );
	}
	elseif( isset($edited_Blog) )
	{ // We are generating a static page
		$redirect = $edited_Blog->get('dynurl');
	}
	else
	{ // We are in a weird situation
		$redirect = '';
	}

	if( ! empty($redirect) )
	{
		$redirect = '?redirect_to='.rawurlencode( url_rel_to_same_host( $redirect, $htsrv_url_sensitive ) );
	}

	$r = $before;
	$r .= '<a href="'.$htsrv_url_sensitive.'login.php'.$redirect.'" title="'.$link_title.'">';
	$r .= $link_text;
	$r .= '</a>';
	$r .= $after;
	return $r;
}


/**
 * Template tag: Output a link to new user registration
 * @param string
 * @param string
 * @param string
 * @param boolean Display the link, if the user is already logged in? (this is used by the login form)
 */
function user_register_link( $before = '', $after = '', $link_text = '', $link_title = '#', $disp_when_logged_in = false )
{
	echo get_user_register_link( $before, $after, $link_text, $link_title, $disp_when_logged_in );
}


/**
 * Template tag: Get a link to new user registration
 * @param string
 * @param string
 * @param string
 * @param boolean Display the link, if the user is already logged in? (this is used by the login form)
 * @return string
 */
function get_user_register_link( $before = '', $after = '', $link_text = '', $link_title = '#', $disp_when_logged_in = false )
{
	global $htsrv_url_sensitive, $Settings, $edited_Blog, $generating_static;

	if( is_logged_in() && ! $disp_when_logged_in )
	{ // Do not display, when already logged in:
		return false;
	}

	if( ! $Settings->get('newusers_canregister'))
	{ // We won't let him register
		return false;
	}

	if( $link_text == '' ) $link_text = T_('Register...');
	if( $link_title == '#' ) $link_title = T_('Register to open an account...');

	if( !isset($generating_static) )
	{ // We are not generating a static page here:
		$redirect = regenerate_url( '', '', '', '&' );
	}
	elseif( isset($edited_Blog) )
	{ // We are generating a static page
		$redirect = $edited_Blog->get('dynurl');
	}
	else
	{ // We are in a weird situation
		$redirect = '';
	}

	if( ! empty($redirect) )
	{
		$redirect = '?redirect_to='.rawurlencode( url_rel_to_same_host( $redirect, $htsrv_url_sensitive ) );
	}

	$r = $before;
	$r .= '<a href="'.$htsrv_url_sensitive.'register.php'.$redirect.'" title="'.$link_title.'">';
	$r .= $link_text;
	$r .= '</a>';
	$r .= $after;
	return $r;
}


/**
 * Template tag: Output a link to logout
 */
function user_logout_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	echo get_user_logout_link( $before, $after, $link_text, $link_title );
}


/**
 * Template tag: Get a link to logout
 *
 * @return string
 */
function get_user_logout_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	global $htsrv_url_sensitive, $current_User, $blog;

	if( ! is_logged_in() )
	{
		return false;
	}

	if( $link_text == '' ) $link_text = T_('Logout (%s)');
	if( $link_title == '#' ) $link_title = T_('Logout from your account');

	$r = $before;
	$r .= '<a href="'.$htsrv_url_sensitive.'login.php?action=logout&amp;redirect_to='.rawurlencode( url_rel_to_same_host(regenerate_url('','','','&'), $htsrv_url_sensitive) ).'" title="'.$link_title.'">';
	$r .= sprintf( $link_text, $current_User->login );
	$r .= '</a>';
	$r .= $after;
	return $r;
}


/**
 * Template tag: Output a link to the backoffice.
 *
 * Usually provided in skins in order for newbies to find the admin interface more easily...
 *
 * @param string To be displayed before the link.
 * @param string To be displayed after the link.
 * @param string The page/controller to link to inside of {@link $admin_url}
 * @param string Text for the link.
 * @param string Title for the link.
 */
function user_admin_link( $before = '', $after = '', $page = '', $link_text = '', $link_title = '#' )
{
	echo get_user_admin_link( $before, $after, $page, $link_text, $link_title );
}


/**
 * Template tag: Get a link to the backoffice.
 *
 * Usually provided in skins in order for newbies to find the admin interface more easily...
 *
 * @param string To be displayed before the link.
 * @param string To be displayed after the link.
 * @param string The page/controller to link to inside of {@link $admin_url}
 * @param string Text for the link.
 * @param string Title for the link.
 * @return string
 */
function get_user_admin_link( $before = '', $after = '', $page = '', $link_text = '', $link_title = '#' )
{
	global $admin_url, $blog, $current_User;

	if( ! is_logged_in() ) return false;

	if( ! $current_User->check_perm( 'admin', 'visible' ) )
	{ // If user should NOT see admin link:
		return false;
	}

	if( $link_text == '' ) $link_text = T_('Admin');
	if( $link_title == '#' ) $link_title = T_('Go to the back-office');
	// add the blog param to $page if it is not already in there
	if( !preg_match('/(&|&amp;|\?)blog=/', $page) ) $page = url_add_param( $page, 'blog='.$blog );

	$r = $before;
	$r .= '<a href="'.$admin_url.$page.'" title="'.$link_title.'">';
	$r .= $link_text;
	$r .= '</a>';
	$r .= $after;
	return $r;
}


/**
 * Template tag: Display a link to user profile
 */
function user_profile_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	echo get_user_profile_link( $before, $after, $link_text, $link_title );
}


/**
 * Template tag: Get a link to user profile
 *
 * @return string|false
 */
function get_user_profile_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	global $current_User, $Blog;

	if( ! is_logged_in() )
	{
		return false;
	}

	if( $link_text == '' ) $link_text = T_('Profile (%s)');
	if( $link_title == '#' ) $link_title = T_('Edit your profile');

	$r = $before
		.'<a href="'.url_add_param( $Blog->dget( 'blogurl', 'raw' ), 'disp=profile&amp;redirect_to='.rawurlencode(regenerate_url('','','','&')) )
		.'" title="'.$link_title.'">'
		.sprintf( $link_text, $current_User->login )
		.'</a>'
		.$after;

	return $r;
}


/**
 * Template tag: Provide a link to subscription screen
 */
function user_subs_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	global $current_User, $Blog;

	if( ! is_logged_in() )
	{
		return false;
	}

	if( $link_text == '' ) $link_text = T_('Subscribe (%s)');
	if( $link_title == '#' ) $link_title = T_('Subscribe to email notifications');

	echo $before;
	echo '<a href="'.url_add_param( $Blog->dget( 'blogurl', 'raw' ), 'disp=subs&amp;redirect_to='.rawurlencode( url_rel_to_same_host(regenerate_url('','','','&'), $Blog->get('blogurl'))) )
			.'" title="', $link_title, '">';
	printf( $link_text, $current_User->login );
	echo '</a>';
	echo $after;
}


/**
 * Template tag: Display the user's preferred name
 *
 * Used in result lists.
 *
 * @param integer user ID
 */
function user_preferredname( $user_ID )
{
	$UserCache = & get_Cache( 'UserCache' );
	if( !empty( $user_ID )
		&& ($User = & $UserCache->get_by_ID( $user_ID )) )
	{
		$User->disp('preferredname');
	}
}


/*
 * profile_title(-)
 *
 * @movedTo _obsolete092.php
 */


/**
 * Check profile parameters and add errors to {@link $Messages}.
 *
 * @param array associative array
 *              'login': check for non-empty
 *              'nickname': check for non-empty
 *              'icq': must be a number
 *              'email': mandatory, must be well formed
 *              'url': must be well formed, in allowed scheme, not blacklisted
 *              'pass1' / 'pass2': passwords (twice), must be the same and not == login (if given)
 *              'pass_required': false/true (default is true)
 * @param User|NULL A user to use for additional checks (password != login/nick).
 */
function profile_check_params( $params, $User = NULL )
{
	global $Messages, $Settings, $comments_allowed_uri_scheme;

	if( !is_array($params) )
	{
		$params = array( $params );
	}

	// checking login has been typed:
	if( isset($params['login']) && empty($params['login']) )
	{
		$Messages->add( T_('Please enter a login.'), 'error' );
	}

	// checking the nickname has been typed
	if( isset($params['nickname']) && empty($params['nickname']) )
	{
		$Messages->add( T_('Please enter a nickname (can be the same as your login).'), 'error' );
	}

	// if the ICQ UIN has been entered, check to see if it has only numbers
	if( !empty($params['icq']) )
	{
		if( !preg_match( '#^[0-9]+$#', $params['icq']) )
		{
			$Messages->add( T_('The ICQ UIN can only be a number, no letters allowed.'), 'error' );
		}
	}

	// checking e-mail address
	if( isset($params['email']) )
	{
		if( empty($params['email']) )
		{
			$Messages->add( T_('Please enter an e-mail address.'), 'error' );
		}
		elseif( !is_email($params['email']) )
		{
			$Messages->add( T_('The email address is invalid.'), 'error' );
		}
	}

	// Checking URL:
	if( isset($params['url']) )
	{
		if( $error = validate_url( $params['url'], $comments_allowed_uri_scheme ) )
		{
			$Messages->add( T_('Supplied URL is invalid: ').$error, 'error' );
		}
	}

	// Check passwords:

	$pass_required = isset( $params['pass_required'] ) ? $params['pass_required'] : true;

	if( isset($params['pass1']) && isset($params['pass2']) )
	{
		if( $pass_required || !empty($params['pass1']) || !empty($params['pass2']) )
		{ // Password is required or was given
			// checking the password has been typed twice
			if( empty($params['pass1']) || empty($params['pass2']) )
			{
				$Messages->add( T_('Please enter your password twice.'), 'error' );
			}

			// checking the password has been typed twice the same:
			if( $params['pass1'] != $params['pass2'] )
			{
				$Messages->add( T_('You typed two different passwords.'), 'error' );
			}
			elseif( strlen($params['pass1']) < $Settings->get('user_minpwdlen')
							|| strlen($params['pass2']) < $Settings->get('user_minpwdlen') )
			{
				$Messages->add( sprintf( T_('The minimum password length is %d characters.'), $Settings->get('user_minpwdlen')), 'error' );
			}
			elseif( isset($User) && $params['pass1'] == $User->get('login') )
			{
				$Messages->add( T_('The password must be different from your login.'), 'error' );
			}
			elseif( isset($User) && $params['pass1'] == $User->get('nickname') )
			{
				$Messages->add( T_('The password must be different from your nickname.'), 'error' );
			}
		}
	}
}

/*
 * $Log$
 * Revision 1.17  2006/10/23 22:19:02  blueyed
 * Fixed/unified encoding of redirect_to param. Use just rawurlencode() and no funky &amp; replacements
 *
 * Revision 1.16  2006/10/15 21:36:08  blueyed
 * Use url_rel_to_same_host() for redirect_to params.
 *
 * Revision 1.14  2006/08/21 16:07:44  fplanque
 * refactoring
 *
 * Revision 1.13  2006/08/19 07:56:31  fplanque
 * Moved a lot of stuff out of the automatic instanciation in _main.inc
 *
 * Revision 1.12  2006/07/26 20:48:33  blueyed
 * Added Plugin event "Logout"
 *
 * Revision 1.11  2006/07/26 20:19:15  blueyed
 * Set $current_User = NULL on logout (not false!)
 *
 * Revision 1.10  2006/06/25 23:34:15  blueyed
 * wording pt2
 *
 * Revision 1.9  2006/06/25 23:23:38  blueyed
 * wording
 *
 * Revision 1.8  2006/06/22 22:30:04  blueyed
 * htsrv url for password related scripts (login, register and profile update)
 *
 * Revision 1.7  2006/04/20 22:13:48  blueyed
 * Display "Register..." link in login form also if user is logged in already.
 *
 * Revision 1.6  2006/04/19 20:13:50  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.5  2006/03/26 03:34:12  blueyed
 * Fixed E_NOTICE
 *
 * Revision 1.4  2006/03/26 03:25:21  blueyed
 * Added more getters
 *
 * Revision 1.3  2006/03/12 23:09:00  fplanque
 * doc cleanup
 *
 * Revision 1.2  2006/02/24 20:26:37  blueyed
 * _group.funcs.php is empty
 *
 * Revision 1.1  2006/02/23 21:11:58  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.40  2006/01/30 16:09:34  blueyed
 * doc
 *
 * Revision 1.38  2006/01/26 20:58:16  blueyed
 * Added get_user_profile_link()
 *
 * Revision 1.37  2006/01/15 19:05:36  blueyed
 * user_admin_link(): empty default for $page, so that /admin/index.php gets respected.
 */
?>