<?php
/**
 * This file implements login/logout handling functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
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
	global $current_User, $Session;

	// Reset all global variables
	// Note: unset is bugguy on globals
	$current_User = false;

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
	global $UserCache;

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
		$redirect = '?redirect_to='.rawurlencode( regenerate_url() );
	}
	elseif( isset($edited_Blog) )
	{ // We are generating a static page
		$redirect = '?redirect_to='.rawurlencode( $edited_Blog->get('dynurl') );
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
		$redirect = '?redirect_to='.rawurlencode( regenerate_url() );
	}
	elseif( isset($edited_Blog) )
	{ // We are generating a static page
		$redirect = '?redirect_to='.rawurlencode( $edited_Blog->get('dynurl') );
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
	echo '<a href="'.$htsrv_url.'login.php?action=logout&amp;redirect_to='.rawurlencode( regenerate_url() ).'" title="'.$link_title.'">';
	printf( $link_text, $current_User->login );
	echo '</a>';
	echo $after;
}


/**
 * Template tag: Provide a link to the backoffice.
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

	echo $before;
	echo '<a href="'.$admin_url.$page.'" title="'.$link_title.'">';
	echo $link_text;
	echo '</a>';
	echo $after;
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
		.'<a href="'.url_add_param( $Blog->dget( 'blogurl', 'raw' ), 'disp=profile&amp;redirect_to='.rawurlencode(regenerate_url()) )
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
	echo '<a href="'.url_add_param( $Blog->dget( 'blogurl', 'raw' ), 'disp=subs&amp;redirect_to='.rawurlencode(regenerate_url()) )
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
	global $UserCache;

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
 *
 * Revision 1.35  2005/12/12 19:44:09  fplanque
 * Use cached objects by reference instead of copying them!!
 *
 * Revision 1.34  2005/12/12 19:21:23  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.33  2005/12/08 22:49:18  blueyed
 * Typo
 *
 * Revision 1.32  2005/11/02 20:11:19  fplanque
 * "containing entropy"
 *
 * Revision 1.31  2005/10/31 08:33:31  blueyed
 * profile_check_params(): Allow passing a User object that can be used for additional tests (password != login/nickname)
 *
 * Revision 1.30  2005/10/31 06:13:03  blueyed
 * Finally merged my work on $Session in.
 *
 * Revision 1.29  2005/10/31 05:51:06  blueyed
 * Use rawurlencode() instead of urlencode()
 *
 * Revision 1.28  2005/09/29 15:07:30  fplanque
 * spelling
 *
 * Revision 1.27  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.26  2005/08/26 14:17:15  fplanque
 * removed obsolete cookie cleaners
 *
 * Revision 1.25  2005/08/22 18:43:34  blueyed
 * Handle non-existing user in user_pass_ok() correctly.
 *
 * Revision 1.24  2005/06/03 15:12:33  fplanque
 * error/info message cleanup
 *
 * Revision 1.23  2005/05/24 18:46:26  fplanque
 * implemented blog email subscriptions (part 1)
 *
 * Revision 1.22  2005/05/09 19:07:05  fplanque
 * bugfixes + global access permission
 *
 * Revision 1.21  2005/03/15 19:19:49  fplanque
 * minor, moved/centralized some includes
 *
 * Revision 1.20  2005/03/09 14:54:26  fplanque
 * refactored *_title() galore to requested_title()
 *
 * Revision 1.19  2005/02/28 09:06:34  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.18  2005/02/23 04:06:16  blueyed
 * minor
 *
 * Revision 1.17  2005/02/22 02:42:21  blueyed
 * Login refactored (send password-change-request mail instead of new password)
 *
 * Revision 1.16  2005/02/20 23:21:20  blueyed
 * user pwd verifying fixed
 *
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
 * added user_preferredname()
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