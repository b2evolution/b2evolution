<?php
/**
 * This file implements login/logout handling functions.
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

load_class( 'users/model/_group.class.php', 'Group' );
load_class( 'users/model/_user.class.php', 'User' );


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

	return $User->check_password( $pass, $pass_is_md5 );
}


/**
 * Template tag: Output link to login
 */
function user_login_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	if( is_logged_in() ) return false;

	if( $link_text == '' ) $link_text = T_('Log in');
	if( $link_title == '#' ) $link_title = T_('Log in if you have an account...');

	$r = $before;
	$r .= '<a href="'.get_login_url().'" title="'.$link_title.'">';
	$r .= $link_text;
	$r .= '</a>';
	$r .= $after;

	echo $r;
}


/**
 * Get url to login
 *
 * @return string
 */
function get_login_url()
{
	global $htsrv_url_sensitive, $edited_Blog, $generating_static;

	if( !isset($generating_static) )
	{ // We are not generating a static page here:
		$redirect = regenerate_url( '', '', '', '&' );
	}
	elseif( isset($edited_Blog) ) // fp> this is a shady test!! :/
	{ // We are generating a static page
		$redirect = $edited_Blog->get('url'); // was dynurl
	}
	else
	{ // We are in a weird situation
		$redirect = '';
	}

	if( ! empty($redirect) )
	{
		$redirect = '?redirect_to='.rawurlencode( url_rel_to_same_host( $redirect, $htsrv_url_sensitive ) );
	}

	return $htsrv_url_sensitive.'login.php'.$redirect;
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
 * @param string Where to redirect
 * @return string
 */
function get_user_register_link( $before = '', $after = '', $link_text = '', $link_title = '#', $disp_when_logged_in = false, $redirect = null )
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

	if( ! isset($redirect) )
	{
		if( !isset($generating_static) )
		{ // We are not generating a static page here:
			$redirect = regenerate_url( '', '', '', '&' );
		}
		elseif( isset($edited_Blog) )
		{ // We are generating a static page
			$redirect = $edited_Blog->get('url'); // was dynurl
		}
		else
		{ // We are in a weird situation
			$redirect = '';
		}
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
function user_logout_link( $before = '', $after = '', $link_text = '', $link_title = '#', $params = array() )
{
	echo get_user_logout_link( $before, $after, $link_text, $link_title, $params );
}


/**
 * Template tag: Get a link to logout
 *
 * @param string
 * @param string
 * @param string link text can include %s for current user login
 * @return string
 */
function get_user_logout_link( $before = '', $after = '', $link_text = '', $link_title = '#', $params = array() )
{
	global $current_User;

	if( ! is_logged_in() )
	{
		return false;
	}

	if( $link_text == '' ) $link_text = T_('Logout');
	if( $link_title == '#' ) $link_title = T_('Logout from your account');

	$r = $before;
	$r .= '<a href="'.get_user_logout_url().'"';
	$r .= get_field_attribs_as_string( $params, false );
	$r .= ' title="'.$link_title.'">';
	$r .= sprintf( $link_text, $current_User->login );
	$r .= '</a>';
	$r .= $after;
	return $r;
}


/**
 * Get the URL for the logout button
 *
 * @return string
 */
function get_user_logout_url()
{
	global $admin_url, $baseurl, $htsrv_url_sensitive, $is_admin_page, $Blog;

	if( ! is_logged_in() )
	{
		return false;
	}

	if( $is_admin_page )
	{
		if( isset( $Blog ) )
		{	// Go to the home page of the blog that was being edited:
  		$redirect_to = $Blog->get( 'url' );
		}
		else
		{	// We were not editing a blog...
			// return to global home:
  		$redirect_to = url_rel_to_same_host( $baseurl, $htsrv_url_sensitive);
  		// Alternative: return to the login page (fp> a basic user would be pretty lost on that login page)
  		// $redirect_to = url_rel_to_same_host($admin_url, $htsrv_url_sensitive);
		}
	}
	else
	{	// Return to current blog page:
		$redirect_to = url_rel_to_same_host(regenerate_url('','','','&'), $htsrv_url_sensitive);
	}

	return $htsrv_url_sensitive.'login.php?action=logout&amp;redirect_to='.rawurlencode($redirect_to);
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
function user_admin_link( $before = '', $after = '', $link_text = '', $link_title = '#', $not_visible = '' )
{
	echo get_user_admin_link( $before, $after, $link_text, $link_title, $not_visible );
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
function get_user_admin_link( $before = '', $after = '', $link_text = '', $link_title = '#', $not_visible = '' )
{
	global $admin_url, $blog, $current_User;

	if( is_logged_in() && ! $current_User->check_perm( 'admin', 'visible' ) )
	{ // If user should NOT see admin link:
		return $not_visible;
	}

	if( $link_text == '' ) $link_text = T_('Admin');
	if( $link_title == '#' ) $link_title = T_('Go to the back-office...');
	// add the blog param to $page if it is not already in there

	if( !empty( $blog ) )
	{
		$url = url_add_param( $admin_url, 'blog='.$blog );
	}
	else
	{
		$url = $admin_url;
	}

	$r = $before;
	$r .= '<a href="'.$url.'" title="'.$link_title.'">';
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
	global $current_User;

	if( ! is_logged_in() )
	{
		return false;
	}

	if( $link_text == '' )
	{
		$link_text = T_('Profile');
	}
	else
	{
		$link_text = str_replace( '%s', $current_User->login, $link_text );
	}
	if( $link_title == '#' ) $link_title = T_('Edit your profile');

	$r = $before
		.'<a href="'.get_user_profile_url().'" title="'.$link_title.'">'
		.sprintf( $link_text, $current_User->login )
		.'</a>'
		.$after;

	return $r;
}


/**
 * Get URL to edit user profile
 */
function get_user_profile_url()
{
	global $current_User, $Blog, $is_admin_page, $admin_url, $ReqURI;

	if( $is_admin_page || empty( $Blog ) )
	{
		$url = $admin_url.'?ctrl=users&amp;user_ID='.$current_User->ID;
	}
	else
	{
		$url = url_add_param( $Blog->gen_blogurl(), 'disp=profile&amp;redirect_to='.rawurlencode($ReqURI) );
	}

	return $url;
}


/**
 * Template tag: Provide a link to subscription screen
 */
function user_subs_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	global $current_User, $Blog;

	if( ! $url = get_user_subs_url() )
	{
		return false;
	}

	if( $link_text == '' ) $link_text = T_('Subscribe');
	if( $link_title == '#' ) $link_title = T_('Subscribe to email notifications');

	echo $before;
	echo '<a href="'.$url.'" title="', $link_title, '">';
	printf( $link_text, $current_User->login );
	echo '</a>';
	echo $after;
}

/**
 * Template tag: Provide url to subscription screen
 */
function get_user_subs_url()
{
	global $Blog, $is_admin_page;

	if( ! is_logged_in() || $is_admin_page )
	{
		return false;
	}

	if( empty( $Blog ) || ! $Blog->get_setting( 'allow_subscriptions' ) )
	{
		return false;
	}

	return url_add_param( $Blog->gen_blogurl(), 'disp=subs&amp;redirect_to='.rawurlencode( url_rel_to_same_host(regenerate_url('','','','&'), $Blog->gen_blogurl())));
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


/**
 * Check profile parameters and add errors through {@link param_error()}.
 *
 * @param array associative array.
 *     Either array( $value, $input_name ) or just $value;
 *     ($input_name gets used for associating it to a form fieldname)
 *     - 'login': check for non-empty
 *     - 'nickname': check for non-empty
 *     - 'icq': must be a number
 *     - 'email': mandatory, must be well formed
 *     - 'country': check for non-empty
 *     - 'url': must be well formed, in allowed scheme, not blacklisted
 *     - 'pass1' / 'pass2': passwords (twice), must be the same and not == login (if given)
 *     - 'pass_required': false/true (default is true)
 * @param User|NULL A user to use for additional checks (password != login/nick).
 */
function profile_check_params( $params, $User = NULL )
{
	global $Messages, $Settings;

	foreach( $params as $k => $v )
	{
		// normalize params:
		if( $k != 'pass_required' && ! is_array($v) )
		{
			$params[$k] = array($v, $k);
		}
	}

	// checking login has been typed:
	if( isset($params['login']) && empty($params['login'][0]) )
	{
		param_error( 'login', T_('Please enter a login.') );
	}

	// checking the nickname has been typed
	if( isset($params['nickname']) && empty($params['nickname'][0]) )
	{
		param_error($params['nickname'][1], T_('Please enter a nickname (can be the same as your login).') );
	}

	// if the ICQ UIN has been entered, check to see if it has only numbers
	if( !empty($params['icq'][0]) )
	{
		if( !preg_match( '#^[0-9]+$#', $params['icq'][0]) )
		{
			param_error( $params['icq'][1], T_('The ICQ UIN can only be a number, no letters allowed.') );
		}
	}

	// checking e-mail address
	if( isset($params['email'][0]) )
	{
		if( empty($params['email'][0]) )
		{
			param_error( $params['email'][1], T_('Please enter an e-mail address.') );
		}
		elseif( !is_email($params['email'][0]) )
		{
			param_error( $params['email'][1], T_('The email address is invalid.') );
		}
	}

	// Checking country
	if( isset($params['country']) && empty($params['country'][0]) )
	{
		param_error( 'country', T_('Please select country.') );
	}

	// Checking URL:
	if( isset($params['url']) )
	{
		if( $error = validate_url( $params['url'][0], 'commenting' ) )
		{
			param_error( $params['url'][1], T_('Supplied URL is invalid: ').$error );
		}
	}

	// Check passwords:

	$pass_required = isset( $params['pass_required'] ) ? $params['pass_required'] : true;

	if( isset($params['pass1'][0]) && isset($params['pass2'][0]) )
	{
		if( $pass_required || !empty($params['pass1'][0]) || !empty($params['pass2'][0]) )
		{ // Password is required or was given
			// checking the password has been typed twice
			if( empty($params['pass1'][0]) || empty($params['pass2'][0]) )
			{
				param_error( $params['pass2'][1], T_('Please enter your password twice.') );
			}

			// checking the password has been typed twice the same:
			if( $params['pass1'][0] !== $params['pass2'][0] )
			{
				param_error( $params['pass1'][1], T_('You typed two different passwords.') );
			}
			elseif( strlen($params['pass1'][0]) < $Settings->get('user_minpwdlen') )
			{
				param_error( $params['pass1'][1], sprintf( T_('The minimum password length is %d characters.'), $Settings->get('user_minpwdlen')) );
			}
			elseif( isset($User) && $params['pass1'][0] == $User->get('login') )
			{
				param_error( $params['pass1'][1], T_('The password must be different from your login.') );
			}
			elseif( isset($User) && $params['pass1'][0] == $User->get('nickname') )
			{
				param_error( $params['pass1'][1], T_('The password must be different from your nickname.') );
			}
		}
	}
}


/**
 * Get avatar <img> tag by user login
 *
 * @param user login
 * @param avatar size
 * @param style class
 * @param image align
 * @param if true show user login before an avatar
 * @return login <img> tag
 */
function get_avatar_imgtag( $user_login, $size = 'crop-15x15', $class = 'avatar_before_login', $align = '', $show_login = true)
{
	global $current_User;

	$UserCache = & get_UserCache();
	$User = & $UserCache->get_by_login( $user_login );

	$img_tag = '';
	if( $User !== false )
	{
		$img_tag = $User->get_avatar_imgtag( $size, $class, $align );

		if( $show_login )
		{
			$img_tag = '<span class="nowrap">'.$img_tag.$user_login.'</span>';
		}

		if( $current_User->check_perm( 'users', 'view', false ) )
		{	// Permission to view user details
			$img_tag = '<a href="?ctrl=users&amp;user_ID='.$User->ID.'">'.$img_tag.'</a>';
		}

	}

	return $img_tag;
}


/**
 * Get avatar <img> tags for list of user logins
 *
 * @param list of user logins
 * @param avatar size
 * @param style class
 * @param image align
 * @param if true show user login before an avatar
 * @return coma separated login <img> tag
 */
function get_avatar_imgtags( $user_logins_list, $size = 'crop-15x15', $class = 'avatar_before_login', $align = '', $show_login = true )
{
	if( !is_array( $user_logins_list ) )
	{
		$user_logins_list = explode( ', ', $user_logins_list );
	}

	$user_imgtags_list = array();
	foreach( $user_logins_list as $user_login )
	{
		$user_imgtags_list[] = get_avatar_imgtag( $user_login, $size, $class, $align, $show_login );
	}
	return implode( ', ', $user_imgtags_list );
}


/*
 * $Log$
 * Revision 1.15  2009/09/19 20:50:57  fplanque
 * added action icons/links
 *
 * Revision 1.14  2009/09/19 01:15:49  fplanque
 * minor
 *
 * Revision 1.13  2009/09/18 16:01:50  fplanque
 * cleanup
 *
 * Revision 1.12  2009/09/17 07:32:56  efy-bogdan
 * Require country
 *
 * Revision 1.11  2009/09/14 13:46:11  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.10  2009/03/23 22:19:46  fplanque
 * evobar right menu is now also customizable by plugins
 *
 * Revision 1.9  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.8  2008/04/13 23:38:53  fplanque
 * Basic public user profiles
 *
 * Revision 1.7  2008/01/21 09:35:36  fplanque
 * (c) 2008
 *
 * Revision 1.6  2008/01/19 15:45:28  fplanque
 * refactoring
 *
 * Revision 1.5  2008/01/14 07:22:07  fplanque
 * Refactoring
 *
 * Revision 1.4  2007/12/10 01:22:04  blueyed
 * Pass on redirect_to param from login form through the register... link to the register form.
 * get_user_register_link: added $redirect param for injection
 *
 * Revision 1.3  2007/09/28 02:17:48  fplanque
 * Menu widgets
 *
 * Revision 1.2  2007/07/01 03:57:20  fplanque
 * toolbar eveywhere
 *
 * Revision 1.1  2007/06/25 11:01:47  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.30  2007/05/28 15:18:31  fplanque
 * cleanup
 *
 * Revision 1.29  2007/04/26 00:11:11  fplanque
 * (c) 2007
 *
 * Revision 1.28  2007/03/25 13:19:17  fplanque
 * temporarily disabled dynamic and static urls.
 * may become permanent in favor of a caching mechanism.
 *
 * Revision 1.27  2007/03/06 12:23:38  fplanque
 * bugfix
 *
 * Revision 1.26  2007/03/04 05:24:52  fplanque
 * some progress on the toolbar menu
 *
 * Revision 1.25  2007/01/29 09:58:55  fplanque
 * enhanced toolbar - experimental
 *
 * Revision 1.24  2007/01/28 17:53:09  fplanque
 * changes for 2.0 skin structure
 *
 * Revision 1.23  2007/01/27 19:57:12  blueyed
 * Use param_error() in profile_check_params()
 *
 * Revision 1.22  2007/01/20 00:38:39  blueyed
 * todo
 *
 * Revision 1.21  2007/01/19 03:06:57  fplanque
 * Changed many little thinsg in the login procedure.
 * There may be new bugs, sorry. I tested this for several hours though.
 * More refactoring to be done.
 *
 * Revision 1.20  2006/12/19 20:48:28  blueyed
 * MFB: Use relative URL for "redirect_to" in get_user_profile_link(). See http://forums.b2evolution.net/viewtopic.php?p=48686#48686
 *
 * Revision 1.19  2006/12/16 01:30:46  fplanque
 * Setting to allow/disable email subscriptions on a per blog basis
 *
 * Revision 1.18  2006/11/24 18:27:25  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.17  2006/10/23 22:19:02  blueyed
 * Fixed/unified encoding of redirect_to param. Use just rawurlencode() and no funky &amp; replacements
 *
 * Revision 1.16  2006/10/15 21:36:08  blueyed
 * Use url_rel_to_same_host() for redirect_to params.
 */
?>
