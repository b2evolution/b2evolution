<?php
/**
 * This file implements login/logout handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
 *
 * @param boolean true if not active users are considerated as logged in users, false otherwise
 */
function is_logged_in( $accept_not_active = true )
{
	global $current_User;

	return is_object( $current_User ) && !empty( $current_User->ID ) && ( $accept_not_active || $current_User->check_status( 'is_validated' ) );
}


/**
 * Check if current User status permit the give action
 *
 * @param string action
 * @param integger target ID - can be a post ID, user ID
 * @return boolean true if the user is loggedn in and the action is permitted, false otherwise
 */
function check_user_status( $action, $target = NULL )
{
	global $current_User;

	if( !is_logged_in() )
	{
		return false;
	}

	return $current_User->check_status( $action, $target );
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
	$UserCache = & get_UserCache();
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
function user_login_link( $before = '', $after = '', $link_text = '', $link_title = '#', $source = 'user login link' )
{
	echo get_user_login_link( $before, $after, $link_text, $link_title, $source );
}


/**
 * Get link to login
 *
 * @param string Text before link
 * @param string Text after link
 * @param string Link text
 * @param string Link title
 * @param string Source
 * @return string Link for log in
 */
function get_user_login_link( $before = '', $after = '', $link_text = '', $link_title = '#', $source = 'user login link', $redirect_to = NULL )
{
	if( is_logged_in() ) return false;

	if( $link_text == '' ) $link_text = T_('Log in');
	if( $link_title == '#' ) $link_title = T_('Log in if you have an account...');

	$r = $before;
	$r .= '<a href="'.get_login_url( $source, $redirect_to ).'" title="'.$link_title.'">';
	$r .= $link_text;
	$r .= '</a>';
	$r .= $after;

	return $r;
}


/**
 * Get url to login
 *
 * @param string describe the source ina word or two, used for stats (search current calls to this function for examples)
 * @param string
 * @return string
 */
function get_login_url( $source, $redirect_to = NULL )
{
	global $edited_Blog, $secure_htsrv_url;

	if( !empty( $redirect_to ) )
	{
		$redirect = $redirect_to;
	}
	else
	{
		$redirect = regenerate_url( '', '', '', '&' );
	}

	if( use_in_skin_login() )
	{ // use in-skin login
		global $blog;
		$BlogCache = & get_BlogCache();
		$Blog = $BlogCache->get_by_ID( $blog );
		if( ! empty($redirect) )
		{
			$redirect = 'redirect_to='.rawurlencode( url_rel_to_same_host( $redirect, $Blog->get( 'loginurl' ) ) );
		}
		$url = url_add_param( $Blog->get( 'loginurl' ), $redirect, '&' );
	}
	else
	{ // Normal login
		if( ! empty($redirect) )
		{
			$redirect = '?redirect_to='.rawurlencode( url_rel_to_same_host( $redirect, $secure_htsrv_url ) );
		}
		$url = $secure_htsrv_url.'login.php'.$redirect;
	}

	return url_add_param( $url, 'source='.rawurlencode($source), '&' );
}


/**
 * Get url to show user activate info screen
 */
function get_activate_info_url( $redirect_to = NULL )
{
	global $Blog, $secure_htsrv_url;

	if( empty( $redirect_to ) )
	{ // redirect back to current URL
		$redirect_to = rawurlencode( url_rel_to_same_host( regenerate_url( '', '', '', '&' ), $secure_htsrv_url ) );
	}

	if( use_in_skin_login() )
	{ // use in-skin login is set, use in-skin activate info page
		return url_add_param( $Blog->gen_blogurl(), 'disp=activateinfo&redirect_to='.$redirect_to, '&' );
	}

	return $secure_htsrv_url.'login.php?action=req_validatemail&redirect_to='.$redirect_to;
}


/**
 * Get url where to redirect, after successful account activation
 */
function redirect_after_account_activation()
{
	global $Settings, $Session, $baseurl;

	// Get general "Users setting" to determine if we want to return to original page after account activation or to a specific url:
	$redirect_to = $Settings->get( 'after_email_validation' );
	if( $redirect_to == 'return_to_original' )
	{ // we want to return to original page after account activation
		// the redirect_to param should be set in the Session. This was set when the account activation email was sent.
		$redirect_to = $Session->get( 'core.validatemail.redirect_to' );
		// if the redirect_to is not set in the Session or is empty, we MUST NEVER let to redirect back to the origianl page which can be hotmail, gmail, etc.
		if( empty( $redirect_to ) )
		{ // session redirect_to was not set, initialize $redirect_to to the home page
			$redirect_to = $baseurl;
		}
	}

	return $redirect_to;
}


/**
 * Send notification to users with edit users permission
 *
 * @param string notification email suject
 * @param string notificaiton email template name
 * @param array notification email template params
 */
function send_admin_notification( $subject, $template_name, $template_params )
{
	global $Session, $UserSettings, $current_User;

	$UserCache = & get_UserCache();
	$template_params = array_merge( array(
			'login' => '',
		), $template_params );

	// Set default subject and permname:
	$final_subject = T_( $subject ).': '.$template_params['login'];
	$perm_name = 'users';

	switch( $template_name )
	{
		case 'registration':
			$check_setting = 'notify_new_user_registration';
			break;

		case 'user_activated':
			$check_setting = 'notify_activated_account';
			break;

		case 'close_account':
			$check_setting = 'notify_closed_account';
			break;

		case 'user_reported':
			$check_setting = 'notify_reported_account';
			break;

		case 'cronjob_error':
			$final_subject = T_( $subject );
			$check_setting = 'notify_cronjob_error';
			$perm_name = 'options';
			break;

		default:
			debug_die( 'Unhandled admin notification template!' );
	}

	if( empty($current_User) && $Session->has_User() )
	{ // current_User is not set at the time of registration
		$current_User = & $Session->get_User();
	}

	// load users with edit all users permission
	$UserCache->load_where( 'user_grp_ID = 1 OR user_grp_ID IN ( SELECT gset_grp_ID FROM T_groups__groupsettings WHERE gset_name = "perm_'.$perm_name.'" AND gset_value = "edit" )' );
	// iterate through UserCache
	$UserCache->rewind();
	while( $User = & $UserCache->get_next() )
	{ // Loop through Users
		if( is_logged_in() && $current_User->ID == $User->ID )
		{ // Don't send a notification to current user, because he already knows about this event
			continue;
		}
		if( $UserSettings->get( $check_setting, $User->ID ) && $User->check_perm( $perm_name, 'edit' ) )
		{ // this user must be notifed
			locale_temp_switch( $User->get( 'locale' ) );
			// send mail to user (using his local)
			send_mail_to_User( $User->ID, $final_subject, $template_name, $template_params ); // ok, if this may fail
			locale_restore_previous();
		}
	}
}


/**
 * Use in-skin login
 */
function use_in_skin_login()
{
	global $Blog, $blog;

	if( is_admin_page() )
	{
		return false;
	}

	if( !isset( $blog ) )
	{
		return false;
	}

	$BlogCache = & get_BlogCache();
	$Blog = $BlogCache->get_by_ID( $blog, false, false );
	if( empty( $Blog ) )
	{
		return false;
	}

	return $Blog->get_setting( 'in_skin_login' );
}


/**
 * Check if show toolbar
 */
function show_toolbar()
{
	global $current_User;
	return ( is_logged_in() && ( $current_User->check_perm( 'admin', 'toolbar' ) ) );
}


/**
 * Check a settings from user for Back office and from skin for Front office
 *
 * @param string Setting name ( gender_colored OR bubbletip)
 * @return bool Use colored gender
 */
function check_setting( $setting_name )
{
	global $Settings, $Blog, $SkinCache;

	if( ! isset( $Blog ) && ! is_admin_page() )
	{	// If we use some page without blog data
		return false;
	}

	if( is_admin_page() )
	{	// Check setting in the Back office
		if( $Settings->get( $setting_name ) )
		{	// Set TRUE if the setting is ON
			return true;
		}
	}
	else
	{	// Check setting in the Front office for current blog & skin
		global $Blog, $SkinCache;
		if( ! isset( $SkinCache ) )
		{	// Init $SkinCache if it doesn't still exist
			$SkinCache = & get_SkinCache();
		}
		$skin = & $SkinCache->get_by_ID( $Blog->get( 'skin_ID' ) );
		if( $skin->get_setting( $setting_name ) )
		{ // If setting is ON for current Blog & Skin
			if( $setting_name == 'bubbletip' )
			{	// Check separate case for setting 'bubbletip'
				if( is_logged_in() || $Settings->get( $setting_name.'_anonymous' ) )
				{	// If user is logged in OR Anonymous user can see bubbletips
					return true;
				}
			}
			else
			{ // Setting 'gender_colored' doesn't depend on user's logged status
				return true;
			}
		}
	}

	return false;
}


/**
 * Template tag: Output a link to new user registration
 * @param string
 * @param string
 * @param string
 * @param boolean Display the link, if the user is already logged in? (this is used by the login form)
 * @param string used for source tracking if $source is not already set
 */
function user_register_link( $before = '', $after = '', $link_text = '', $link_title = '#', $disp_when_logged_in = false, $default_source_string = '' )
{
	echo get_user_register_link( $before, $after, $link_text, $link_title, $disp_when_logged_in, NULL, $default_source_string );
}


/**
 * Template tag: Get a link to new user registration
 *
 * @param string
 * @param string
 * @param string
 * @param string
 * @param boolean Display the link, if the user is already logged in? (this is used by the login form)
 * @param string Where to redirect
 * @return string used for source tracking
 */
function get_user_register_link( $before = '', $after = '', $link_text = '', $link_title = '#',
		$disp_when_logged_in = false, $redirect = null, $default_source_string = '' )
{
	$register_url = get_user_register_url( $redirect, $default_source_string, $disp_when_logged_in );

	if( !$register_url )
	{
		return false;
	}

	if( $link_text == '' ) $link_text = T_('Register').' &raquo;';
	if( $link_title == '#' ) $link_title = T_('Register for a new account...');

	$r = $before;
	$r .= '<a href="'.$register_url.'" title="'.$link_title.'">';
	$r .= $link_text;
	$r .= '</a>';
	$r .= $after;
	return $r;
}


/**
 * put your comment there...
 *
 * @param mixed $redirect
 * @param mixed $default_source_string
 * @param mixed $disp_when_logged_in
 */
function get_user_register_url( $redirect = NULL, $default_source_string = '', $disp_when_logged_in = false )
{
	global $Settings, $edited_Blog, $secure_htsrv_url;

	if( is_logged_in() && ! $disp_when_logged_in )
	{ // Do not display, when already logged in:
		return false;
	}

	if( ! $Settings->get('newusers_canregister'))
	{ // We won't let him register
		return false;
	}

	if( use_in_skin_login() )
	{
		global $blog;

		$BlogCache = & get_BlogCache();
		$Blog = $BlogCache->get_by_ID( $blog );

		$register_url = url_add_param( $Blog->get( 'url' ), 'disp=register' );
	}
	else
	{
		$register_url = $secure_htsrv_url.'register.php';
	}

	// Source=
	$source = param( 'source', 'string', '' );
	if( empty($source) )
	{
		$source = $default_source_string;
	}
	if( ! empty($source) )
	{
		$register_url = url_add_param( $register_url, 'source='.rawurlencode($source), '&' );
	}

	// Redirect_to=
	if( ! isset($redirect) )
	{
		$redirect = regenerate_url( '', '', '', '&' );
	}

	if( ! empty($redirect) )
	{
		$register_url = url_add_param( $register_url, 'redirect_to='.rawurlencode( url_rel_to_same_host( $redirect, $secure_htsrv_url ) ), '&' );
	}

	return $register_url;
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
	global $admin_url, $baseurl, $is_admin_page, $Blog, $secure_htsrv_url;

	if( ! is_logged_in() )
	{
		return false;
	}

	$redirect_to = url_rel_to_same_host( regenerate_url( 'disp,action','','','&' ), $secure_htsrv_url );
	if( require_login( $redirect_to, true ) )
	{ // if redirect_to page is a login page, or also require login ( e.g. admin.php )
		if( !empty( $Blog ) )
		{ // Blog is set
			// set redirect_to to Blog url
			$redirect_to = $Blog->gen_blogurl();
		}
		else
		{ // Blog is empty, set abort url to baseurl
			$redirect_to =  url_rel_to_same_host( $baseurl, $secure_htsrv_url );
		}
	}

	return $secure_htsrv_url.'login.php?action=logout&amp;redirect_to='.rawurlencode($redirect_to);
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

	if( is_logged_in() && ! $current_User->check_perm( 'admin', 'normal' ) )
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
 * Template tag: Display a link to user tab
 */
function user_tab_link( $user_tab = 'user', $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	echo get_user_tab_link( $user_tab, $before, $after, $link_text, $link_title );
}


/**
 * Template tag: Get a link to view user
 *
 * @return string|false
 */
function get_user_tab_link( $user_tab = 'user', $before = '', $after = '', $link_text = '#', $link_title = '#' )
{
	if( ! is_logged_in() )
	{
		return false;
	}

	$user_tab_url = get_user_settings_url( $user_tab );

	if( empty( $user_tab_url ) )
	{
		return false;
	}

	if( $link_text == '#' )
	{
		$link_text = T_('My profile');
	}

	if( $link_title == '#' )
	{
		$link_title = T_('My profile');
	}

	$r = $before
		.'<a href="'.$user_tab_url.'" title="'.$link_title.'">'
		.$link_text
		.'</a>'
		.$after;

	return $r;
}


/**
 * Get URL to edit user profile
 */
function get_user_profile_url()
{
	return get_user_settings_url( 'profile' );
}


/**
 * Get URL to edit user avatar
 */
function get_user_avatar_url()
{
	return get_user_settings_url( 'avatar' );
}


/**
 * Get URL to change user password
 */
function get_user_pwdchange_url()
{
	return get_user_settings_url( 'pwdchange' );
}


/**
 * Get URL to edit user preferences
 */
function get_user_preferences_url()
{
	return get_user_settings_url( 'userprefs' );
}


/**
 * Template tag: Provide a link to subscription screen
 */
function user_subs_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	echo get_user_subs_link( $before, $after, $link_text, $link_title );
}


/**
 * Get a link to subscription screen
 */
function get_user_subs_link( $before = '', $after = '', $link_text = '', $link_title = '#' )
{
	global $current_User;

	if( ! $url = get_user_subs_url() )
	{
		return false;
	}

	if( $link_text == '' ) $link_text = T_('Subscribe');
	if( $link_title == '#' ) $link_title = T_('Subscribe to email notifications');

	$r = $before
		.'<a href="'.$url.'" title="'.$link_title.'">'
		.sprintf( $link_text, $current_User->login )
		.'</a>'
		.$after;

	return $r;
}


/**
 * Get url to set notificaitons/subscription screen
 *
 * @return string Url to subscription screen
 */
function get_user_subs_url()
{
	return get_user_settings_url( 'subs' );
}

/**
 * Get User identity link. User is given with his login or ID. User login or ID must be set.
 *
 * @param string User login ( can be NULL if ID is set )
 * @param integer User ID ( can be NULL if login is set )
 * @param string On which user profile tab should this link point to
 * @return NULL|string NULL if this user or the profile tab doesn't exists, the identity link otherwise.
 */
function get_user_identity_link( $user_login, $user_ID = NULL, $profile_tab = 'profile', $link_text = 'avatar' )
{
	$UserCache = & get_UserCache();

	if( empty( $user_login ) )
	{
		$User = & $UserCache->get_by_ID( $user_ID, false, false );
		if( !$User )
		{ // user with given user_ID doesn't exist
			return NULL;
		}
	}
	else
	{
		$User = & $UserCache->get_by_login( $user_login );
	}

	if( $User == false )
	{
		return NULL;
	}

	return $User->get_identity_link( array( 'profile_tab' => $profile_tab, 'link_text' => $link_text ) );
}


/**
 * Get the available user display url
 *
 * @param integer User ID
 * @param string Name of user tab in backoffice ( values: profile, avatar, pwdchange, userprefs, advanced, admin, blogs )
 * @return string Url
 */
function get_user_identity_url( $user_ID, $user_tab = 'profile' )
{
	global $current_User, $Blog, $Settings;

	if( $user_ID == NULL )
	{
		return NULL;
	}

	$UserCache = & get_UserCache();
	$User = $UserCache->get_by_ID( $user_ID, false );

	if( empty( $User ) )
	{
		return NULL;
	}

	if( !$User->check_status( 'can_display_link' ) && !( is_admin_page() && is_logged_in( false ) && ( $current_User->check_perm( 'users', 'edit' ) ) ) )
	{ // if the account status restrict to display user profile link and current User is not an admin in admin interface, then do not return identity url!
		return NULL;
	}

	if( !is_logged_in() )
	{ // user is not logged in
		if( $Settings->get( 'allow_anonymous_user_profiles' ) && ( isset( $Blog ) ) )
		{
			return url_add_param( $Blog->gen_blogurl(), 'disp=user&amp;user_ID='.$user_ID );
		}
		// return NULL if user is not logged in and display is not allowed or Blog is not set
		return NULL;
	}

	if( !$current_User->check_status( 'can_view_user', $User->ID ) )
	{ // if the current user status restrict to view other user profile
		return NULL;
	}

	if( isset($Blog) && !is_admin_page() )
	{	// can't display the profile form, display the front office User form
		return url_add_param( $Blog->gen_blogurl(), 'disp=user&amp;user_ID='.$user_ID );
	}

	if( $current_User->check_status( 'can_access_admin' ) && ( ($current_User->ID == $user_ID ) || $current_User->check_perm( 'users', 'view' ) ) )
	{	// Go to backoffice profile:
		return get_user_settings_url( $user_tab, $user_ID );
	}

	// can't show anything:
	return NULL;
}


/**
 * Get URL to a specific user settings tab (profile, avatar, pwdchange, userprefs)
 *
 * @param string user tab
 * @param integer user ID for the requested user. If isn't set then return $current_User settings url.
 */
function get_user_settings_url( $user_tab, $user_ID = NULL )
{
	global $current_User, $Blog, $is_admin_page, $admin_url, $ReqURI;

	if( !is_logged_in() )
	{
		debug_die( 'Active user not found.' );
	}

	if( in_array( $user_tab, array( 'advanced', 'admin', 'sessions', 'activity' ) ) )
	{
		$is_admin_tab = true;
	}
	else
	{
		$is_admin_tab = false;
	}

	if( ( !$is_admin_tab ) && ( ! in_array( $user_tab, array( 'profile', 'user', 'avatar', 'pwdchange', 'userprefs', 'subs' ) ) ) )
	{
		debug_die( 'Not supported user tab!' );
	}

	if( $user_ID == NULL )
	{
		$user_ID = $current_User->ID;
	}

	if( $is_admin_page || $is_admin_tab || empty( $Blog ) || $current_User->ID != $user_ID )
	{
		if( ( $current_User->ID != $user_ID ) && ( ! $current_User->check_perm( 'users', 'view' ) ) )
		{
			return NULL;
		}
		$current_User->get_Group();
		if( ( $user_tab == 'admin' ) && ( $current_User->Group->ID != 1 ) )
		{
			$user_tab = 'profile';
		}
		return $admin_url.'?ctrl=user&amp;user_tab='.$user_tab.'&amp;user_ID='.$user_ID;
	}

	return url_add_param( $Blog->gen_blogurl(), 'disp='.$user_tab );
}


/**
 * Template tag: Display a link to messaging module
 */
function user_messaging_link( $before = '', $after = '', $link_text = '#', $link_title = '#', $show_badge = false )
{
	echo get_user_messaging_link( $before, $after, $link_text, $link_title );
}


/**
 * Template tag: Get a link to messaging module
 *
 * @return string|false
 */
function get_user_messaging_link( $before = '', $after = '', $link_text = '#', $link_title = '#', $show_badge = false )
{
	global $unread_messages_count;

	$user_messaging_url = get_user_messaging_url();

	if( !$user_messaging_url )
	{	// Messages link is not available
		return false;
	}

	if( $link_text == '#' )
	{
		$link_text = T_('Messages');
	}

	if( $link_title == '#' )
	{
		$link_title = T_('Messages');
	}

	$badge = '';
	if( $show_badge && $unread_messages_count > 0 )
	{
		$badge = ' <span class="badge">'.$unread_messages_count.'</span>';
	}

	$r = $before
		.'<a href="'.$user_messaging_url.'" title="'.$link_title.'">'
		.$link_text
		.'</a>'
		.$badge
		.$after;

	return $r;
}


/**
 * Get URL to messaging module
 */
function get_user_messaging_url()
{
	global $current_User, $Blog;

	if( !is_logged_in() )
	{
		return false;
	}

	if( !$current_User->check_perm( 'perm_messaging', 'reply' ) )
	{	// No minimum permissions for messaging module
		return false;
	}

	return get_dispctrl_url( 'threads' );
}


/**
 * Template tag: Display a link to user contacts
 */
function user_contacts_link( $before = '', $after = '', $link_text = '#', $link_title = '#' )
{
	echo get_user_contacts_link( $before, $after, $link_text, $link_title );
}


/**
 * Template tag: Get a link to user contacts
 *
 * @return string|false
 */
function get_user_contacts_link( $before = '', $after = '', $link_text = '#', $link_title = '#' )
{
	$user_contacts_url = get_user_contacts_url();

	if( !$user_contacts_url )
	{	// Messages link is not available
		return false;
	}

	if( $link_text == '#' )
	{
		$link_text = T_('Messages');
	}

	if( $link_title == '#' )
	{
		$link_title = T_('Messages');
	}

	$r = $before
		.'<a href="'.$user_contacts_url.'" title="'.$link_title.'">'
		.$link_text
		.'</a>'
		.$after;

	return $r;
}


/**
 * Get URL to user contacts
 */
function get_user_contacts_url()
{
	global $current_User, $Blog;

	if( !is_logged_in() )
	{
		return false;
	}

	return get_dispctrl_url( 'contacts' );
}


/**
 * Get colored tag with user field "required"
 *
 * @param string required value
 * @param integer user ID for the requested user. If isn't set then return $current_User settings url.
 */
function get_userfield_required( $value )
{
	return '<span class="userfield '.$value.'">'.T_( $value ).'</span>';
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
	$UserCache = & get_UserCache();
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
	if( isset($params['login'][0]) )
	{
		if( empty( $params['login'][0] ) )
		{ // login can't be empty
			param_error( $params['login'][1], T_('Please enter your login.') );
		}
		else
		{
			param_check_valid_login( 'login' );
		}
	}

	// checking e-mail address
	if( isset($params['email'][0]) )
	{
		if( empty($params['email'][0]) )
		{
			param_error( $params['email'][1], T_('Please enter your e-mail address.') );
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

	// Checking first name
	if( isset($params['firstname']) && empty($params['firstname'][0]) )
	{
		param_error( 'firstname', T_('Please enter your first name.') );
	}

	// Checking gender
	if( isset($params['gender']) )
	{
		if( empty($params['gender'][0]) )
		{
			param_error( 'gender', T_('Please select gender.') );
		}
		elseif( ( $params['gender'][0] != 'M' ) && ( $params['gender'][0] != 'F' ) )
		{
			param_error( 'gender', 'Gender value is invalid' );
		}
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
			elseif( $Settings->get('passwd_special') && !preg_match('~[\x20-\x2f\x3a-\x40\x5b-\x60\x7b-\x7f]~', $params['pass1'][0] )  )
			{
				param_error( $params['pass1'][1], T_('Your password should contain at least one special character (like & ! $ * - _ + etc.)') );
			}
			elseif( evo_strlen($params['pass1'][0]) < $Settings->get('user_minpwdlen') )
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
 * @param if true show user login after avatar
 * @param if true link to user profile
 * @param avatar size
 * @param style class of image
 * @param image align
 * @param avatar overlay text
 * @param style class of link
 * @param if true show user avatar
 * @return login <img> tag
 */
function get_avatar_imgtag( $user_login, $show_login = true, $link = true, $size = 'crop-top-15x15', $img_class = 'avatar_before_login', $align = '', $avatar_overlay_text = '', $link_class = '', $show_avatar = true )
{
	global $current_User;

	$UserCache = & get_UserCache();
	$User = & $UserCache->get_by_login( $user_login );

	if( $User === false )
	{
		return '';
	}

	$img_tag = '';
	if( $show_avatar )
	{	// Get user avatar
		$img_tag = $User->get_avatar_imgtag( $size, $img_class, $align, false, $avatar_overlay_text );
	}

	if( $show_login )
	{
		$img_tag = '<span class="nowrap">'.$img_tag.'<b>'.$user_login.'</b></span>';
	}

	$identity_url = get_user_identity_url( $User->ID );
	if( empty( $identity_url ) )
	{	// Current user has not permissions to view other user profile
		$img_tag = '<span class="'.$User->get_gender_class().'" rel="bubbletip_user_'.$User->ID.'">'.$img_tag.'</span>';
	}
	else if( !empty( $img_tag ) )
	{	// Show avatar & user login as link to the profile page
		$link_class = ( $link_class != '' ) ? ' '.$link_class : '';
		$img_tag = '<a href="'.$identity_url.'" class="'.$User->get_gender_class().$link_class.'" rel="bubbletip_user_'.$User->ID.'">'.$img_tag.'</a>';
	}

	return $img_tag;
}


/**
 * Get avatar <img> tags for list of user logins
 *
 * @param list of user logins
 * @param if true show user login after each avatar
 * @param avatar size
 * @param style class
 * @param image align
 * @param mixed read status, Set icon of the read status, 'left'/'left_message' - if user has left the conversation ( left messsage will display different title ), TRUE - users have seen message, FALSE - users have not seen the message
 *              leave it on NULL - to not display read status icon
 * @param if true show user avatar
 * @param separator between users
 * @param boolean set true to also show deleted users with 'Deleted user' label
 * @return coma separated login <img> tag
 */
function get_avatar_imgtags( $user_logins_list, $show_login = true, $link = true, $size = 'crop-top-15x15', $class = 'avatar_before_login', $align = '', $read_status = NULL, $show_avatar = true, $separator = '<br />', $show_deleted_users = false )
{
	if( !is_array( $user_logins_list ) )
	{
		$user_logins_list = explode( ', ', $user_logins_list );
	}

	$user_imgtags_list = array();
	foreach( $user_logins_list as $user_login )
	{
		$icon = '';
		if( ! is_null( $read_status ) )
		{ // Add icon behind user login (read status)
			if( $read_status === 'left' )
			{ // user has left the conversation
				$icon = get_icon( 'bullet_black', 'imgtag', array( 'alt' => sprintf( T_('%s has left this conversation.'), $user_login ), 'style' => 'margin:1px 4px' ) );
			}
			elseif( $read_status === 'left_message' )
			{ // user has left the conversation before this message
				$icon = get_icon( 'bullet_black', 'imgtag', array( 'alt' => sprintf( T_('%s has left the conversation and has not received this message.'), $user_login ), 'style' => 'margin:1px 4px' ) );
			}
			elseif( $read_status )
			{ // User has seen a message
				$icon = get_icon( 'allowback', 'imgtag', array( 'alt' => sprintf( T_('%s has seen this message.'), $user_login ), 'style' => 'margin:0 2px' ) );
			}
			else
			{ // User has not seen a message
				$icon = get_icon( 'bullet_red', 'imgtag', array( 'alt' => sprintf( T_('%s has NOT seen this message yet.'), $user_login ), 'style' => 'margin:1px 4px' ) );
			}
		}
		if( empty( $user_login ) )
		{ // user login is empty, we can't show avatar
			if( $show_deleted_users )
			{ // show this users as deleted user
				$user_imgtags_list[] = '<span class="nowrap">'.get_avatar_imgtag_default( $size, $class, $align ).'<span class="user deleted"><b>'.T_( 'Deleted user' ).'</b></span></span>';
			}
		}
		else
		{
			$user_imgtags_list[] = '<span class="nowrap">'.$icon.get_avatar_imgtag( $user_login, $show_login, $link, $size, $class, $align, '', '', $show_avatar ).'</span>';
		}
	}
	return implode( $separator, $user_imgtags_list );
}


/**
 * Get styled avatar
 *
 * @param integer user ID
 * @param array params
 * @return string
 */
function get_user_avatar_styled( $user_ID, $params )
{
	global $thumbnail_sizes;

	$params = array_merge( array(
			'block_class'  => 'avatar_rounded',
			'size'         => 'crop-top-64x64',
			'avatar_class' => 'avatar',
			'bubbletip'    => true,
		), $params );

	$UserCache = & get_UserCache();
	$User = & $UserCache->get_by_ID( $user_ID, false, false );

	if( $User )
	{ // requested user exists
		return $User->get_avatar_styled( $params );
	}

	// user doesn't exists because it was deleted
	$bubbletip_param = '';
	if( $params['bubbletip'] )
	{	// Init bubbletip param
		$bubbletip_param = 'rel="bubbletip_user_'.$user_ID.'"';
	}
	$style_width = '';
	if( isset( $thumbnail_sizes[$params['size']] ) )
	{
		$style_width = ' style="width:'.$thumbnail_sizes[$params['size']][1].'px"';
	}

	$result = '<div class="'.$params['block_class'].'" '.$bubbletip_param.$style_width.'>'
			 .get_avatar_imgtag_default( $params['size'], $params['avatar_class'] )
			 .'<span class="user deleted">'.T_( 'Deleted user' ).'</span>'
			 .'</div>';
	return $result;
}


/**
 * Get avatar <img> tag with default picture
 *
 * @param avatar size
 * @param style class of image
 * @param image align
 * @return <img> tag
 */
function get_avatar_imgtag_default( $size = 'crop-top-15x15', $class = '', $align = '', $params = array() )
{
	global $Settings;

	if( ! $Settings->get('allow_avatars') || ! $Settings->get('use_gravatar') )
	{ // Avatars & Gravatars are not allowed, Exit here
		return '';
	}

	global $thumbnail_sizes;

	$default_gravatar = $Settings->get('default_gravatar');
	if( $default_gravatar == 'b2evo' )
	{ // Use gravatar from b2evo default avatar image
		global $default_avatar;
		$img_url = $default_avatar;
		$gravatar_width = isset( $thumbnail_sizes[$size] ) ? $thumbnail_sizes[$size][1] : '15';
		$gravatar_height = isset( $thumbnail_sizes[$size] ) ? $thumbnail_sizes[$size][2] : '15';
	}

	if( empty( $img_url ) )
	{ // Use gravatar
		$params = array_merge( array(
				'email'    => '',
				'username' => '',
				'default'  => $default_gravatar,
			), $params );

		$img_url = 'http://www.gravatar.com/avatar/'.md5( $params['email'] );
		$gravatar_width = isset( $thumbnail_sizes[$size] ) ? $thumbnail_sizes[$size][1] : '15';
		$gravatar_height = $gravatar_width;

		$img_url_params = array();
		if( !empty( $params['rating'] ) )
		{ // Rating
			$img_url_params[] = 'rating='.$params['rating'];
		}

		if( !empty( $gravatar_width ) )
		{ // Size
			$img_url_params[] = 'size='.$gravatar_width;
		}

		if( !empty( $params['default'] ) )
		{ // Type
			$img_url_params[] = 'default='.urlencode( $params['default'] );
		}

		if( count( $img_url_params ) > 0 )
		{ // Append url params to request gravatar
			$img_url .= '?'.implode( '&', $img_url_params );
		}
	}

	$img_params = array(
			'src'    => $img_url,
			'width'  => $gravatar_width,  // dh> NOTE: works with gravatar, check if extending
			'height' => $gravatar_height, // dh> NOTE: works with gravatar, check if extending
		);

	if( !empty( $params['username'] ) )
	{ // Add alt & title
		$img_params['alt']   = $params['username'];
		$img_params['title'] = $params['username'];
	}
	if( !empty( $class ) )
	{ // Add class
		$img_params['class'] = $class;
	}
	if( !empty( $align ) )
	{ // Add align
		$img_params['align'] = $align;
	}

	return '<img'.get_field_attribs_as_string( $img_params ).' />';
}


/**
 * Convert seconds to months, days, hours, minutes and seconds format
 *
 * @param integer seconds
 * @return string
 */
function duration_format( $duration )
{
	$result = '';

	$fields = get_duration_fields( $duration );
	if( $fields[ 'months' ] > 0 )
	{
		$result .= sprintf( T_( '%d months' ), $fields[ 'months' ] ).' ';
	}
	if( $fields[ 'days' ] > 0 )
	{
		$result .= sprintf( T_( '%d days' ), $fields[ 'days' ] ).' ';
	}
	if( $fields[ 'hours' ] > 0 )
	{
		$result .= sprintf( T_( '%d hours' ), $fields[ 'hours' ] ).' ';
	}
	if( $fields[ 'minutes' ] > 0 )
	{
		$result .= sprintf( T_( '%d minutes' ), $fields[ 'minutes' ] ).' ';
	}
	if( $fields[ 'seconds' ] > 0 )
	{
		$result .= sprintf( T_( '%d seconds' ),  $fields[ 'seconds' ] );
	}

	$result = trim( $result );
	if( empty( $result ) )
	{
		$result = '0';
	}

	return $result;
}


/**
 * Get the integer value of a status permission
 * The status permissions are stored as a set, and each status has an integer value also
 * 
 * @param string status
 * @return integer status perm value
 */
function get_status_permvalue( $status )
{
	static $status_permission_map = array(
			'trash'      => 0, // Note that 'trash' status doesn't have a real permission value, with this value no-one has permission, and that is OK
			'review'     => 1,
			'draft'      => 2,
			'private'    => 4,
			'protected'  => 8,
			'deprecated' => 16,
			'community'  => 32,
			'published'  => 64,
			'redirected' => 128
		);

	switch( $status )
	{
		case 'published_statuses':
			return $status_permission_map['protected'] + $status_permission_map['community'] + $status_permission_map['published'];

		default:
			break;
	}

	if( !isset( $status_permission_map[$status] ) )
	{
		debug_die( 'Invalid status permvalue was requested!' );
	}

	return $status_permission_map[$status];
}


/**
 * Load blog advanced User/Group permission
 * 
 * @param array the array what should be loaded with the permission values ( it should be the User or Group blog_post_statuses array )
 * @param integer the target blog ID
 * @param integer the target User or Group ID
 * @param string the prefix which must be bloguser or bloggroup depends from where we call this fucntion
 * @return boolean true on success, false on failure
 */
function load_blog_advanced_perms( & $blog_perms, $perm_target_blog, $perm_target_ID, $prefix )
{
	global $DB;

	$BlogCache = & get_BlogCache();
	/**
	 * @var Blog
	 */
	$Blog = & $BlogCache->get_by_ID( $perm_target_blog );
	if( ! $Blog->advanced_perms )
	{ // We do not abide to advanced perms
		return false;
	}

	if( empty( $perm_target_ID ) )
	{ // Target object is not in DB, nothing to load!:
		return false;
	}

	if( !empty( $blog_perms ) )
	{ // perms are already loaded, don't load again
		return false;
	}

	switch( $prefix )
	{
		case 'bloguser':
			$table = 'T_coll_user_perms';
			$perm_target_key = 'bloguser_user_ID';
			break;

		case 'bloggroup':
			$table = 'T_coll_group_perms';
			$perm_target_key = 'bloggroup_group_ID';
			break;

		default:
			debug_die( 'Invalid call of load blog permission' );
	}

	// Load now:
	$query = '
		SELECT *, '.$prefix.'_perm_poststatuses + 0 as perm_poststatuses_bin, '.$prefix.'_perm_cmtstatuses + 0 as perm_cmtstatuses_bin
		  FROM '.$table.'
		 WHERE '.$prefix.'_blog_ID = '.$perm_target_blog.'
		   AND '.$perm_target_key.' = '.$perm_target_ID;
	$row = $DB->get_row( $query, ARRAY_A );

	if( empty($row) )
	{ // No rights set for this Blog - User/Group: remember this (in order not to have the same query next time)
		$blog_perms = array(
				'blog_ismember' => '0',
				'blog_post_statuses' => 0,
				'blog_edit' => 'no',
				'blog_del_post' => '0',
				'blog_edit_ts' => '0',
				'blog_edit_cmt' => 'no',
				'blog_comments' => '0',
				'blog_del_cmts' => '0',
				'blog_recycle_owncmts' => '0',
				'blog_vote_spam_comments' => '0',
				'blog_cmt_statuses' => 0,
				'blog_cats' => '0',
				'blog_properties' => '0',
				'blog_admin' => '0',
				'blog_page' => '0',
				'blog_intro' => '0',
				'blog_podcast' => '0',
				'blog_sidebar' => '0',
				'blog_media_upload' => '0',
				'blog_media_browse' => '0',
				'blog_media_change' => '0',
			);
	}
	else
	{ // OK, rights found:
		$blog_perms['blog_ismember'] = $row[$prefix.'_ismember'];

		$blog_perms['blog_post_statuses'] = $row['perm_poststatuses_bin'];
		$blog_perms['blog_cmt_statuses'] = $row['perm_cmtstatuses_bin'];

		$blog_perms['blog_edit'] = $row[$prefix.'_perm_edit'];
		$blog_perms['blog_del_post'] = $row[$prefix.'_perm_delpost'];
		$blog_perms['blog_edit_ts'] = $row[$prefix.'_perm_edit_ts'];
		$blog_perms['blog_del_cmts'] = $row[$prefix.'_perm_delcmts'];
		$blog_perms['blog_recycle_owncmts'] = $row[$prefix.'_perm_recycle_owncmts'];
		$blog_perms['blog_vote_spam_comments'] = $row[$prefix.'_perm_vote_spam_cmts'];
		$blog_perms['blog_edit_cmt'] = $row[$prefix.'_perm_edit_cmt'];
		$blog_perms['blog_cats'] = $row[$prefix.'_perm_cats'];
		$blog_perms['blog_properties'] = $row[$prefix.'_perm_properties'];
		$blog_perms['blog_admin'] = $row[$prefix.'_perm_admin'];
		$blog_perms['blog_page'] = $row[$prefix.'_perm_page'];
		$blog_perms['blog_intro'] = $row[$prefix.'_perm_intro'];
		$blog_perms['blog_podcast'] = $row[$prefix.'_perm_podcast'];
		$blog_perms['blog_sidebar'] = $row[$prefix.'_perm_sidebar'];
		$blog_perms['blog_media_upload'] = $row[$prefix.'_perm_media_upload'];
		$blog_perms['blog_media_browse'] = $row[$prefix.'_perm_media_browse'];
		$blog_perms['blog_media_change'] = $row[$prefix.'_perm_media_change'];
	}

	return true;
}


/**
 * Check blog advanced user/group permission
 * 
 * @param array blog user or group advanced permission settings
 * @param integer the user ID for whow we are checking the permission
 * @param string permission name
 * @param string permission level
 * @param Object permission target which can be a Comment or an Item depends from the permission what we are checking
 * @return boolean true if checked User/Group has permission, false otherwise
 */
function check_blog_advanced_perm( & $blog_perms, $user_ID, $permname, $permlevel, $perm_target = NULL )
{
	if( empty( $blog_perms ) )
	{
		return false;
	}

	// Check if permission is granted:
	switch( $permname )
	{
		case 'stats':
			// Wiewing stats is the same perm as being authorized to edit properties: (TODO...)
			if( $permlevel == 'view' )
			{
				return $blog_perms['blog_properties'];
			}
			// No other perm can be granted here (TODO...)
			return false;

		case 'blog_post_statuses':
			// We grant this permission only if user has rights to create posts with any status different then 'deprecated' or 'redirected'
			$deprecated_value = get_status_permvalue( 'deprecated' );
			$redirected_value = get_status_permvalue( 'redirected' );
			return ( ( ~ ( $deprecated_value + $redirected_value ) ) & $blog_perms['blog_post_statuses'] ) > 0;

		case 'blog_comment_statuses':
			// We grant this permission only if user has rights to create comments with any status different then 'deprecated'
			$deprecated_value = get_status_permvalue( 'deprecated' );
			return ( ( ~ $deprecated_value ) & $blog_perms['blog_cmt_statuses'] ) > 0;

		case 'blog_comments':
			$edit_permname = 'blog_edit_cmt';
			$perm = ( $blog_perms['blog_cmt_statuses'] > 0 );
			break;

		case 'blog_post!published':
		case 'blog_post!community':
		case 'blog_post!protected':
		case 'blog_post!private':
		case 'blog_post!review':
		case 'blog_post!draft':
		case 'blog_post!deprecated':
		case 'blog_post!redirected':
			// We want a specific post permission:
			$status = substr( $permname, 10 );
			$edit_permname = 'blog_edit';
			$perm_statuses_value = $blog_perms['blog_post_statuses'];
			if( !empty( $perm_target ) )
			{
				$Item = & $perm_target;
				$creator_user_ID = $Item->creator_user_ID;
			}

			$perm = $perm_statuses_value & get_status_permvalue( $status );
			break;

		case 'blog_comment!published':
		case 'blog_comment!community':
		case 'blog_comment!protected':
		case 'blog_comment!private':
		case 'blog_comment!review':
		case 'blog_comment!draft':
		case 'blog_comment!deprecated':
			// We want a specific comment permission:
			$status = substr( $permname, 13 );
			$edit_permname = 'blog_edit_cmt';
			$perm_statuses_value = $blog_perms['blog_cmt_statuses'];
			if( !empty( $perm_target ) )
			{
				$Comment = & $perm_target;
				$creator_user_ID = $Comment->author_user_ID;
			}

			$perm = $perm_statuses_value & get_status_permvalue( $status );
			break;

		case 'files':
			switch( $permlevel )
			{
				case 'add':
					return $blog_perms['blog_media_upload'];
				case 'view':
					return $blog_perms['blog_media_browse'];
				case 'edit':
					return $blog_perms['blog_media_change'];
				default:
					return false;
			}
			break;

		case 'blog_edit':
		case 'blog_edit_cmt':
			if( $permlevel == 'no' )
			{ // Doesn't make sensce to check that the user has at least 'no' permission
				debug_die( 'Invalid edit pemlevel!' );
			}
			$edit_permvalue = $blog_perms[$permname];
			switch( $edit_permvalue )
			{
				case 'all':
					return true;

				case 'le':
					return $permlevel != 'all';

				case 'lt':
					return $permlevel != 'all' && $permlevel != 'le';

				case 'anon':
					return $permlevel == 'anon' || $permlevel == 'own';

				case 'own':
					return $permlevel == 'own';

				default:
					return false;
			}

		default:
			return $blog_perms[$permname];
	}

	// TODO: the following probably should be handled by the Item class!
	if( $perm && ( $permlevel == 'edit' || $permlevel == 'moderate' )
		&& ( !empty( $creator_user_ID ) || ( !empty( $Comment ) ) ) ) // Check if Comment is not empty because in case of comments authors may be empty ( anonymous users )
	{	// Can we edit this specific Item/Comment?
		$edit_permvalue = $blog_perms[$edit_permname];
		switch( $edit_permvalue )
		{
			case 'own': // Own posts/comments only:
				return ( $creator_user_ID == $user_ID );

			case 'lt': // Own + Lower level posts only:
			case 'le': // Own + Lower or equal level posts only:
				if( empty( $creator_user_ID ) || ( $creator_user_ID == $user_ID ) )
				{ // allow if the comment creator is not registered or it is the current User
					return true;
				}
				$UserCache = & get_UserCache();
				// Get creator User
				$creator_User = & $UserCache->get_by_ID( $creator_user_ID, false, false );
				// Get user for who we are checking this permission
				$User = & $UserCache->get_by_ID( $user_ID, false, false );
				return ( $creator_User && $User && ( $creator_User->level < $User->level || ( $edit_permvalue == 'le' && $creator_User->level == $User->level ) ) );

			case 'anon': // Anonymous comment or own comment ( This perm value may have only for comments )
				return ( empty( $creator_user_ID ) || ( $creator_user_ID == $user_ID ) );

			case 'all':
				return true;

			case 'no':
			default:
				return false;
		}
	}

	if( $perm && $permlevel == 'edit' && empty( $creator_user_ID ) )
	{
		return $blog_perms[$edit_permname] != 'no';
	}

	if( $perm && $permlevel == 'moderate' && empty( $creator_user_ID ) )
	{ // check moderator rights
		return in_array( $blog_perms[$edit_permname], array( 'lt', 'le', 'all' ) );
	}

	return $perm;
}


/**
 * Display user edit forms action icons
 *
 * @param object Widget(Form,Table,Results) where to display
 * @param objcet Edited User
 * @param string the action string, 'view' or 'edit'
 */
function echo_user_actions( $Widget, $edited_User, $action )
{
	global $current_User;

	if( $edited_User->ID != 0 )
	{ // show these actions only if user already exists
		if( ( $current_User->check_perm( 'users', 'edit', false ) ) && ( $current_User->ID != $edited_User->ID )
			&& ( $edited_User->ID != 1 ) )
		{
			$Widget->global_icon( T_('Delete this user!'), 'delete', '?ctrl=users&amp;action=delete&amp;user_ID='.$edited_User->ID.'&amp;'.url_crumb('user'), ' '.T_('Delete'), 3, 4  );
			$Widget->global_icon( T_('Delete this user as spammer!'), 'delete', '?ctrl=users&amp;action=delete&amp;deltype=spammer&amp;user_ID='.$edited_User->ID.'&amp;'.url_crumb('user'), ' '.T_('Delete spammer'), 3, 4  );
		}
		if( $edited_User->get_msgform_possibility( $current_User ) )
		{
			$Widget->global_icon( T_('Compose message'), 'comments', '?ctrl=threads&action=new&user_login='.$edited_User->login );
		}
	}

	$redirect_to = get_param( 'redirect_to' );
	if( $redirect_to == NULL )
	{
		$redirect_to = regenerate_url( 'user_ID,action,ctrl', 'ctrl=users' );
	}
	$Widget->global_icon( ( $action != 'view' ? T_('Cancel editing!') : T_('Close user profile!') ), 'close', $redirect_to );
}


/**
 * Get user menu sub entries
 *
 * @param boolean true to get admin interface user sub menu entries, false to get front office user sub menu entries
 * @param integer edited user ID
 * @return array user sub entries
 */
function get_user_sub_entries( $is_admin, $user_ID )
{
	global $current_User, $Settings, $Blog;
	$users_sub_entries = array();
	if( empty( $user_ID ) )
	{
		$user_ID = $current_User->ID;
	}

	if( $is_admin )
	{
		$ctrl_param = 'ctrl=user&amp;user_tab=';
		$user_param = '&amp;user_ID='.$user_ID;
		$base_url = '';
	}
	else
	{
		$ctrl_param = 'disp=';
		$user_param = '';
		$base_url = $Blog->gen_blogurl();
	}
	$edit_perm = ( $user_ID == $current_User->ID || $current_User->check_perm( 'users', 'edit' ) );
	$view_perm = ( $user_ID == $current_User->ID || $current_User->check_perm( 'users', 'view' ) );

	if( $view_perm )
	{
		$users_sub_entries['profile'] = array(
							'text' => T_('Profile'),
							'href' => url_add_param( $base_url, $ctrl_param.'profile'.$user_param ) );

		if( $Settings->get('allow_avatars') )
		{
			$users_sub_entries['avatar'] = array(
							'text' => T_('Profile picture'),
							'href' => url_add_param( $base_url, $ctrl_param.'avatar'.$user_param ) );
		}

		if( $edit_perm )
		{
			$users_sub_entries['pwdchange'] = array(
								'text' => T_('Password'),
								'href' => url_add_param( $base_url, $ctrl_param.'pwdchange'.$user_param ) );
		}

		$users_sub_entries['userprefs'] = array(
							'text' => T_('Preferences'),
							'href' => url_add_param( $base_url, $ctrl_param.'userprefs'.$user_param ) );

		$users_sub_entries['subs'] = array(
							'text' => T_('Notifications'),
							'href' => url_add_param( $base_url, $ctrl_param.'subs'.$user_param ) );

		if( $is_admin )
		{	// show this only in backoffice
			$users_sub_entries['advanced'] = array(
								'text' => T_('Advanced'),
								'href' => url_add_param( $base_url, 'ctrl=user&amp;user_tab=advanced'.$user_param ) );

			if( $current_User->check_perm( 'users', 'edit' ) )
			{ // User have edit/delete all users permission, so this user is an administrator
				$users_sub_entries['admin'] = array(
								'text' => T_('Admin'),
								'href' => url_add_param( $base_url, 'ctrl=user&amp;user_tab=admin'.$user_param ) );

				// Only users with view/edit all users permission can see the 'Sessions' & 'User Activity' tabs
				$users_sub_entries['sessions'] = array(
									'text' => T_('Sessions'),
									'href' => url_add_param( $base_url, 'ctrl=user&amp;user_tab=sessions'.$user_param ) );

				$users_sub_entries['activity'] = array(
									'text' => $current_User->ID == $user_ID ? T_('My Activity') : T_('User Activity'),
									'href' => url_add_param( $base_url, 'ctrl=user&amp;user_tab=activity'.$user_param ) );
			}
		}
	}

	return $users_sub_entries;
}


/**
 * Get if user is subscribed to get emails, when a new comment is published on this item.
 *
 * @param integer user ID
 * @param integer item ID
 * @return boolean true if user is subscribed and false otherwise
 */
function get_user_isubscription( $user_ID, $item_ID )
{
	global $DB;
	$result = $DB->get_var( 'SELECT count( isub_user_ID )
								FROM T_items__subscriptions
								WHERE isub_user_ID = '.$user_ID.' AND isub_item_ID = '.$item_ID.' AND isub_comments <> 0' );
	return $result > 0;
}


/**
 * Set user item subscription
 *
 * @param integer user ID
 * @param integer item ID
 * @param integer value 0 for unsubscribe and 1 for subscribe
 * @return boolean true is new value was successfully set, false otherwise
 */
function set_user_isubscription( $user_ID, $item_ID, $value )
{
	global $DB;
	if( ( $value < 0 ) || ( $value > 1 ) )
	{ // Invalid value. It should be 0 for unsubscribe and 1 for subscribe.
		return false;
	}

	return $DB->query( 'REPLACE INTO T_items__subscriptions( isub_item_ID, isub_user_ID, isub_comments )
								VALUES ( '.$item_ID.', '.$user_ID.', '.$value.' )' );
}


/**
 * Get usertab header. Contains the user avatar image, the user tab title, and the user menu.
 *
 * @param object edited User
 * @param string user tab name
 * @param string user tab title
 * @return string tab header
 */
function get_usertab_header( $edited_User, $user_tab, $user_tab_title )
{
	global $AdminUI;

	// user status
	$user_status_icons = get_user_status_icons();
	$user_status_titles = get_user_statuses();
	$user_status = ' <small>('.$user_status_icons[ $edited_User->get( 'status' ) ].' '.$user_status_titles[ $edited_User->get( 'status' ) ].')</small>';

	// set title
	$form_title = '<h2 class="user_title">'.$edited_User->get_colored_login().$user_status.' &ndash; '.$user_tab_title.'</h2>';

	// set avatar tag
	$avatar_tag = $edited_User->get_avatar_imgtag( 'crop-top-48x48', 'floatleft', '', true );

	// build menu3
	$AdminUI->add_menu_entries( array( 'users', 'users' ), get_user_sub_entries( true, $edited_User->ID ) );
	$AdminUI->set_path( 'users', 'users', $user_tab );
	$user_menu3 = $AdminUI->get_html_menu( array( 'users', 'users' ), 'menu3' );

	$result = $avatar_tag.'<div class="user_header_content">'.$form_title.$user_menu3.'</div>';
	return '<div class="user_header">'.$result.'</div>'.'<div class="clear"></div>';
}


/**
 * Get the account validation emails common message content
 */
function get_validate_email_message( $user_locale, $user_status, $email_format = 'txt' )
{
	global $baseurl;

	if( $email_format == 'html' )
	{	// HTML
		$newline = '<br />';
		$baseurl_link = '<a href="'.$baseurl.'">'.$baseurl.'</a>';
	}
	else
	{	// PLAIN TEXT
		$newline = "\n";
		$baseurl_link = $baseurl;
	}

	locale_temp_switch( $user_locale );
	switch( $user_status )
	{
		case 'new':
			$intro_text = sprintf( T_( 'You have recently registered a new account on %s .' ), $baseurl_link );
			$activation_text = T_( 'Please activate this account by clicking on the following link:' );
			break;
		case 'emailchanged':
			$intro_text = sprintf( T_( 'You have recently changed the email address associated with your account on %s .' ), $baseurl_link );
			$activation_text = T_( 'Please reactivate this account by clicking on the following link:' );
			break;
		case 'deactivated':
			$intro_text = sprintf( T_( 'Your account on %s needs to be reactivated.' ), $baseurl_link );
			$activation_text = T_( 'Please reactivate this account by clicking on the following link:' );
			break;
		default:
			$intro_text = sprintf( T_( 'Someone -- presumably you -- has registered an account on %s with your email address.' ), $baseurl_link );
			$activation_text = T_( 'Please activate this account by clicking on the following link:' );
			break;
	}

	$message = T_( 'Hello $login$ !' )
		.$newline.$newline
		.$intro_text
		.$newline.$newline
		.T_('Your login is: $login$').$newline
		.T_('Your email is: $email$')
		.$newline.$newline
		.$activation_text.$newline;

	locale_restore_previous();
	return $message;
}


/**
 * Check if user can receive new email today with the given email type or the limit was already exceeded
 * This function will update the corresponding UserSettings but won't save it until the email is not sent
 *
 * @param string the name of limit/day setting
 * @param string the name of the last email setting
 * @param integer the user ID
 * @return boolean true if new email is allowed, false otherwise
 */
function check_allow_new_email( $limit_setting, $last_email_setting, $user_ID )
{
	global $UserSettings, $servertimenow;

	$limit = $UserSettings->get( $limit_setting, $user_ID );
	if( $limit == 0 )
	{ // user doesn't allow this kind of emails at all
		return false;
	}
	$email_count = 0;
	$last_email = $UserSettings->get( $last_email_setting, $user_ID );
	if( !empty( $last_email ) )
	{ // at least one email was sent
		$current_date = date( 'Y-m-d', $servertimenow );
		list( $last_email_ts, $last_email_count ) = explode( '_', $last_email );
		$last_date = date( 'Y-m-d', $last_email_ts );
		if( $last_date == $current_date )
		{ // last email was sent today
			if( $last_email_count >= $limit )
			{ // the limit was already reached
				return false;
			}
			$email_count = $last_email_count;
		}
	}
	// new email is allowed, set new email setting value, after the email will be sent
	$email_count++;
	$last_email = $servertimenow.'_'.$email_count;
	$UserSettings->set( $last_email_setting, $last_email, $user_ID );
	return true;
}


/**
 * Send account validation email with a permanent validation link
 *
 * @param array user ids to send validation email
 * @param boolean true if this email is an account activation reminder, false if the account status was changed right now
 * @return integer the number of successfully sent emails
 */
function send_easy_validate_emails( $user_ids, $is_reminder = true, $email_changed = false )
{
	global $UserSettings, $servertimenow, $secure_htsrv_url;

	$UserCache = & get_UserCache();

	if( isset($GLOBALS['messaging_Module']) )
	{	// Get already received messages for each recepient user
		$already_received_messages = get_users_unread_threads( $user_ids );
	}

	$cache_by_locale = array();
	$email_sent = 0;
	foreach( $user_ids as $user_ID )
	{ // Iterate through user ids and send account activation reminder to all user
		$User = $UserCache->get_by_ID( $user_ID, false );
		if( !$User )
		{ // user not exists
			continue;
		}

		if( !$User->check_status( 'can_be_validated' ) )
		{ // User is validated or it is not allowed to be validated
			continue;
		}

		if( $is_reminder && ( !$UserSettings->get( 'send_activation_reminder' ) ) )
		{ // This is an activation reminder, but user wouldn't like to receive this kind of emails
			continue;
		}

		if( mail_is_blocked( $User->get( 'email' ) ) )
		{ // prevent trying to send an email to a blocked email address
			continue;
		}

		$notify_locale = $User->get( 'locale' );
		$reminder_key = $UserSettings->get( 'last_activation_reminder_key', $User->ID );
		if( empty( $reminder_key ) || $email_changed )
		{ // reminder key was not generated yet, or the user email address was changed and we need a new one, to invalidate old requests
			$reminder_key = generate_random_key(32);
			$UserSettings->set( 'last_activation_reminder_key', $reminder_key, $User->ID );
		}
		if( ! isset($cache_by_locale[$notify_locale]) )
		{ // No subject for this locale generated yet:
			locale_temp_switch( $notify_locale );

			$cache_by_locale[$notify_locale]['subject'] = T_( 'Validate your email address for "$login$"!' );

			locale_restore_previous();
		}

		$email_template_params = array(
				'locale'                    => $notify_locale,
				'status'                    => $User->get( 'status' ),
				'reminder_key'              => $reminder_key,
				'is_reminder'               => $is_reminder
			);

		if( !empty( $already_received_messages[$User->ID] ) )
		{ // add already received message list to email body
			$email_template_params['already_received_messages'] = $already_received_messages[$User->ID];
		}

		// Update notification sender's info from General settings
		$User->update_sender( true );

		if( send_mail_to_User( $User->ID, $cache_by_locale[$notify_locale]['subject'], 'validate_account_easy', $email_template_params, true ) )
		{ // save corresponding user settings right after the email was sent, to prevent not saving if an eroor occurs
			$email_sent++;
			// Set last remind activation email date and increase sent reminder emails number in UserSettings
			$UserSettings->set( 'last_activation_email', date2mysql( $servertimenow ), $User->ID );
			if( $is_reminder )
			{
				$reminder_sent_to_user = $UserSettings->get( 'activation_reminder_count', $User->ID );
				$UserSettings->set( 'activation_reminder_count', $reminder_sent_to_user + 1, $User->ID );
			}
			$UserSettings->dbupdate();
		}
	}

	return $email_sent;
}


/**
 * Callback function to initialize a select element on the identity user form
 *
 * @param string Value
 * @return string Option elements for the select tag
 */
function callback_options_user_new_fields( $value = 0 )
{
	global $DB, $edited_User;

	$exclude_fields_sql = '';
	if( !empty( $edited_User ) )
	{	// Exclude not multiple fields for edited user
		$exclude_fields_sql = ' AND ( ufdf_duplicated != "forbidden" OR ufdf_required NOT IN ( "recommended", "require") )
			AND ufdf_ID NOT IN (
				SELECT ufdf_ID
				  FROM T_users__fields
				  LEFT JOIN T_users__fielddefs ON ufdf_ID = uf_ufdf_ID
				 WHERE ufdf_duplicated = "forbidden"
				   AND uf_user_ID = "'.$edited_User->ID.'" )';
	}

	// Get list of possible field types:
	$userfielddefs = $DB->get_results( '
		SELECT ufdf_ID, ufdf_type, ufdf_name, ufgp_ID, ufgp_name, ufdf_suggest
		  FROM T_users__fielddefs
		  LEFT JOIN T_users__fieldgroups ON ufgp_ID = ufdf_ufgp_ID
		 WHERE ufdf_required != "hidden"
		   '.$exclude_fields_sql.'
		 ORDER BY ufgp_order, ufdf_order' );

	$field_options = '';

	if( count( $userfielddefs ) > 0 )
	{	// Field types exist in DB
		global $user_fields_empty_name;
		$empty_name = isset( $user_fields_empty_name ) ? $user_fields_empty_name : T_('Add field...');

		$field_options .= '<option value="">'.$empty_name.'</option>';
		$current_group_ID = 0;
		foreach( $userfielddefs as $f => $fielddef )
		{
			if( $fielddef->ufgp_ID != $current_group_ID )
			{	// New group
				if( $f != 0 )
				{	// Close tag of previous group
					$field_options .= "\n".'</optgroup>';
				}
				$field_options .= "\n".'<optgroup label="'.$fielddef->ufgp_name.'">';
			}
			$field_options .= "\n".'<option value="'.$fielddef->ufdf_ID.'"';
			if( $value == $fielddef->ufdf_ID )
			{	// We had selected this type before getting an error:
				$field_options .= ' selected="selected"';
			}
			if( $fielddef->ufdf_suggest )
			{	// We can suggest a values for this field type
				$field_options .= ' rel="suggest"';
			}
			$field_options .= '>'.$fielddef->ufdf_name.'</option>';
			$current_group_ID = $fielddef->ufgp_ID;
		}
		$field_options .= "\n".'</optgroup>';
	}

	return $field_options;
}


/**
 * Display user fields from given array
 *
 * @param array User fields given from sql query with following structure:
 * 						ufdf_ID
 * 						uf_ID
 * 						ufdf_type
 * 						ufdf_name
 * 						uf_varchar
 * 						ufdf_required
 * 						ufdf_option
 * @param object Form
 * @param string Field name of the new fields ( new | add )
 * @param boolean Add a fieldset for group or don't
 */
function userfields_display( $userfields, $Form, $new_field_name = 'new', $add_group_fieldset = true )
{
	global $action;

	// Array contains values of the new fields from the request
	$uf_new_fields = param( 'uf_'.$new_field_name, 'array' );

	// Type of the new field
	global $new_field_type;

	$group_ID = 0;
	foreach( $userfields as $userfield )
	{
		if( $group_ID != $userfield->ufgp_ID && $add_group_fieldset )
		{	// Start new group
			if( $group_ID > 0 )
			{	// End previous group
				$Form->end_fieldset();
			}
			$Form->begin_fieldset( T_( $userfield->ufgp_name ), array( 'id' => $userfield->ufgp_ID ) );
		}

		$uf_val = param( 'uf_'.$userfield->uf_ID, 'string', NULL );

		$uf_ID = $userfield->uf_ID;
		if( $userfield->uf_ID == '0' )
		{	// Set uf_ID for new (not saved) fields (recommended & require types)
			$userfield->uf_ID = $new_field_name.'['.$userfield->ufdf_ID.'][]';

			$value_num = 'uf_'.$new_field_name.'_'.$userfield->ufdf_ID.'prev_value_num';
			global $$value_num;	// Used when user add a many fields with the same type
			$$value_num = (int)$$value_num;
			if( isset( $uf_new_fields[$userfield->ufdf_ID][$$value_num] ) )
			{	// Get a value from submitted form
				$uf_val = $uf_new_fields[$userfield->ufdf_ID][$$value_num];
				$$value_num++;
			}
		}

		if( is_null( $uf_val ) )
		{	// No value submitted yet, get DB val:
			$uf_val = $userfield->uf_varchar;
		}

		$field_note = '';
		if( $action != 'view' )
		{
			if( in_array( $userfield->ufdf_duplicated, array( 'allowed', 'list' ) ) )
			{	// Icon to add a new field for multiple field
				$field_note .= get_icon( 'add', 'imgtag', array( 'rel' => 'add_ufdf_'.$userfield->ufdf_ID, 'style' => 'display:none !important; cursor: pointer; position: relative;' ) );
			}
		}

		if( $userfield->ufdf_type == 'url' && !empty( $uf_val ) )
		{
			$url = format_to_output( $uf_val, 'formvalue' );
			if( !preg_match('#://#', $url) )
			{
				$url = 'http://'.$url;
			}
			$field_note .= '<a href="'.$url.'" target="_blank" class="action_icon" style="vertical-align: 0;">'.get_icon( 'play', 'imgtag', array('title'=>T_('Visit the site')) ).'</a>';
		}

		$field_params = array();
		$field_params['rel'] = 'ufdf_'.$userfield->ufdf_ID;
		if( $userfield->ufdf_required == 'require' )
		{	// Field is required
			$field_params['required'] = true;
		}
		if( $userfield->ufdf_suggest == '1' && $userfield->ufdf_type == 'word' )
		{	// Mark field with this tag to suggest a values
			$field_params['autocomplete'] = 'on';
		}


		if( $action == 'view' )
		{	// Only view
			$Form->info( $userfield->ufdf_name, $uf_val.' '.$field_note );
		}
		else
		{	// Edit mode
			switch( $userfield->ufdf_type )
			{	// Display existing field:
				case 'text':
					$field_params['cols'] = 38;
					$field_params['note'] = $field_note;
					$Form->textarea_input( 'uf_'.$userfield->uf_ID, $uf_val, 5, $userfield->ufdf_name, $field_params );
					break;

				case 'list':
					$uf_options = explode( "\n", str_replace( "\r", '', $userfield->ufdf_options ) );
					if( $userfield->ufdf_required != 'require' || // Not required field
							$uf_ID == '0' || // New reqired field has to have an empty value
							( $uf_val != '' && ! in_array( $uf_val, $uf_options ) ) ) // Required field has a value that doesn't exist
					{	// Add empty value
						$uf_options = array_merge( array( '---' ), $uf_options );
					}
					$Form->select_input_array( 'uf_'.$userfield->uf_ID, $uf_val, $uf_options, $userfield->ufdf_name, $field_note, $field_params );
					break;

				default:
					$field_params['maxlength'] = 255;
					$Form->text_input( 'uf_'.$userfield->uf_ID, $uf_val, 40, $userfield->ufdf_name, $field_note, $field_params );
			}
		}

		$group_ID = $userfield->ufgp_ID;
	}

	if( $group_ID > 0 && $add_group_fieldset )
	{	// End gruop fieldset if userfields are exist
		$Form->end_fieldset();
	}
}


/**
 * Prepare some data for Userfield
 *
 * @param Userfield
 */
function userfield_prepare( & $userfield )
{
	$userfield->uf_varchar = format_to_output( $userfield->uf_varchar, 'formvalue' );
	if( $userfield->ufdf_type == 'url' )
	{	// Prepare value for url field
		$url = $userfield->uf_varchar;
		if( !preg_match('#://#', $url) )
		{
			$url = 'http://'.$url;
		}
		$userfield->uf_varchar = '<a href="'.$url.'" target="_blank" rel="nofollow">'.$userfield->uf_varchar.'</a>';
	}
}


/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function callback_filter_userlist( & $Form )
{
	global $Settings, $current_User;

	$Form->hidden( 'filter', 'new' );

	$Form->text( 'keywords', get_param('keywords'), 20, T_('Name'), '', 50 );

	if( $Settings->get( 'registration_require_gender' ) != 'hidden' )
	{
		echo '<span class="nowrap">';
		$Form->checkbox( 'gender_men', get_param('gender_men'), T_('Men') );
		$Form->checkbox( 'gender_women', get_param('gender_women'), T_('Women') );
		echo '</span>';
		if( !is_admin_page() )
		{
			echo '<br />';
		}
	}

	if( is_admin_page() )
	{ // show this filters only on admin interface
		if( $current_User->check_perm( 'users', 'edit' ) )
		{ // Show "Reported users" filter only for users with edit user permission
			$Form->checkbox( 'reported', get_param('reported'), T_('Reported users') );
		}

		$Form->select_input_array( 'account_status', get_param('account_status'), get_user_statuses( T_('All') ), T_('Account status') );

		$GroupCache = new DataObjectCache( 'Group', true, 'T_groups', 'grp_', 'grp_ID', 'grp_name' );
		$group_options_array = array(
				'-1' => T_('All (Ungrouped)'),
				'0'  => T_('All (Grouped)'),
			) + $GroupCache->get_option_array();
		$Form->select_input_array( 'group', get_param('group'), $group_options_array, T_('User group'), '', array( 'force_keys_as_values' => true ) );
		echo '<br />';
	}

	if( user_country_visible() )
	{	// Filter by country
		load_class( 'regional/model/_country.class.php', 'Country' );
		load_funcs( 'regional/model/_regional.funcs.php' );
		$CountryCache = & get_CountryCache( T_('All') );
		$Form->select_country( 'country', get_param('country'), $CountryCache, T_('Country'), array( 'allow_none' => true ) );
	}

	if( user_region_visible() )
	{	// Filter by region
		echo '<span id="region_filter"'.( !regions_exist( get_param('country'), true ) ? ' style="display:none"' : '' ).'>';
		$Form->select_input_options( 'region', get_regions_option_list( get_param('country'), get_param('region') ), T_('Region') );
		echo '</span>';
	}

	if( user_subregion_visible() )
	{	// Filter by subregion
		echo '<span id="subregion_filter"'.( !subregions_exist( get_param('region'), true ) ? ' style="display:none"' : '' ).'>';
		$Form->select_input_options( 'subregion', get_subregions_option_list( get_param('region'), get_param('subregion') ), T_('Sub-region') );
		echo '</span>';
	}

	if( user_city_visible() )
	{	// Filter by city
		echo '<span id="city_filter"'.( !cities_exist( get_param('country'), get_param('region'), get_param('subregion'), true ) ? ' style="display:none"' : '' ).'>';
		$Form->select_input_options( 'city', get_cities_option_list( get_param('country'), get_param('region'), get_param('subregion'), get_param('city') ), T_('City') );
		echo '</span>';
	}

	echo '<br />';

	$Form->interval( 'age_min', get_param('age_min'), 'age_max', get_param('age_max'), 3, T_('Age group') );
	echo '<br />';

	$criteria_types = param( 'criteria_type', 'array' );
	$criteria_values = param( 'criteria_value', 'array' );

	if( count( $criteria_types ) == 0 )
	{	// Init one criteria fieldset for first time
		$criteria_types[] = '';
		$criteria_values[] = '';
	}

	foreach( $criteria_types as $c => $type )
	{
		$value = trim( strip_tags( $criteria_values[$c] ) );
		if( $value == '' && count( $criteria_types ) > 1 && $c > 0 )
		{	// Don't display empty field again after filter request
			continue;
		}

		if( $c > 0 )
		{	// Separator between criterias
			echo '<br />';
		}
		$Form->output = false;
		$criteria_input = $Form->text( 'criteria_value[]', $value, 17, '', '', 50 );
		$criteria_input .= get_icon( 'add', 'imgtag', array( 'rel' => 'add_criteria' ) );
		$Form->output = true;

		global $user_fields_empty_name;
		$user_fields_empty_name = T_('Select...');

		$Form->select( 'criteria_type[]', $type, 'callback_options_user_new_fields', T_('Specific criteria'), $criteria_input );
	}

	if( user_region_visible() )
	{	// JS functions for AJAX loading of regions, subregions & cities
?>
<script type="text/javascript">
jQuery( '#country' ).change( function()
{
	var this_obj = jQuery( this );
	jQuery.ajax( {
	type: 'POST',
	url: '<?php echo get_samedomain_htsrv_url(); ?>anon_async.php',
	data: 'action=get_regions_option_list&ctry_id=' + jQuery( this ).val(),
	success: function( result )
		{
			jQuery( '#region' ).html( ajax_debug_clear( result ) );
			if( jQuery( '#region option' ).length > 1 )
			{
				jQuery( '#region_filter' ).show();
			}
			else
			{
				jQuery( '#region_filter' ).hide();
			}
			load_subregions( 0 ); // Reset sub-regions
		}
	} );
} );

jQuery( '#region' ).change( function ()
{	// Change option list with sub-regions
	load_subregions( jQuery( this ).val() );
} );

jQuery( '#subregion' ).change( function ()
{	// Change option list with cities
	load_cities( jQuery( '#country' ).val(), jQuery( '#region' ).val(), jQuery( this ).val() );
} );

function load_subregions( region_ID )
{	// Load option list with sub-regions for seleted region
	jQuery.ajax( {
	type: 'POST',
	url: '<?php echo get_samedomain_htsrv_url(); ?>anon_async.php',
	data: 'action=get_subregions_option_list&rgn_id=' + region_ID,
	success: function( result )
		{
			jQuery( '#subregion' ).html( ajax_debug_clear( result ) );
			if( jQuery( '#subregion option' ).length > 1 )
			{
				jQuery( '#subregion_filter' ).show();
			}
			else
			{
				jQuery( '#subregion_filter' ).hide();
			}
			load_cities( jQuery( '#country' ).val(), region_ID, 0 );
		}
	} );
}

function load_cities( country_ID, region_ID, subregion_ID )
{	// Load option list with cities for seleted region or sub-region
	jQuery.ajax( {
	type: 'POST',
	url: '<?php echo get_samedomain_htsrv_url(); ?>anon_async.php',
	data: 'action=get_cities_option_list&ctry_id=' + country_ID + '&rgn_id=' + region_ID + '&subrg_id=' + subregion_ID,
	success: function( result )
		{
			jQuery( '#city' ).html( ajax_debug_clear( result ) );
			if( jQuery( '#city option' ).length > 1 )
			{
				jQuery( '#city_filter' ).show();
			}
			else
			{
				jQuery( '#city_filter' ).hide();
			}
		}
	} );
}
</script>
<?php
	}
}


/**
 * Country is visible for defining
 *
 * @return boolean TRUE if users can define a country for own profile
 */
function user_country_visible()
{
	global $Settings;

	return $Settings->get( 'location_country' ) != 'hidden' || user_region_visible();
}


/**
 * Region is visible for defining
 *
 * @return boolean TRUE if users can define a region for own profile
 */
function user_region_visible()
{
	global $Settings;

	return $Settings->get( 'location_region' ) != 'hidden' || user_subregion_visible();
}


/**
 * Subregion is visible for defining
 *
 * @return boolean TRUE if users can define a subregion for own profile
 */
function user_subregion_visible()
{
	global $Settings;

	return $Settings->get( 'location_subregion' ) != 'hidden' || user_city_visible();
}


/**
 * City is visible for defining
 *
 * @return boolean TRUE if users can define a city for own profile
 */
function user_city_visible()
{
	global $Settings;

	return $Settings->get( 'location_city' ) != 'hidden';
}


/**
 * Get array for options of account statuses
 *
 * @param string Null option name
 * @return array Account statuses
 */
function get_user_statuses( $null_option_name = '' )
{
	$user_statuses = array(
			'new'              => T_( 'New' ),
			'activated'        => T_( 'Activated by email' ),
			'autoactivated'    => T_( 'Autoactivated' ),
			'emailchanged'     => T_( 'Email changed' ),
			'deactivated'      => T_( 'Deactivated email' ),
			'failedactivation' => T_( 'Failed activation' ),
			'closed'           => T_( 'Closed account' )
		);

	if( !empty( $null_option_name ) )
	{	// Set null option
		$user_statuses = array_merge( array(
					'' => $null_option_name
				), $user_statuses
			);
	}

	return $user_statuses;
}


/**
 * Get array with user statuses and icons
 *
 * @param boolean TRUE - display text after icon
 * @return array Array where Key is user status and Value is html icon
 */
function get_user_status_icons( $display_text = false )
{
	$user_status_icons = array(
			'activated'        => get_icon( 'bullet_green', 'imgtag', array( 'title' => T_( 'Account has been activated by email' ) ) ),
			'autoactivated'    => get_icon( 'bullet_green', 'imgtag', array( 'title' => T_( 'Account has been automatically activated' ) ) ),
			'new'              => get_icon( 'bullet_blue', 'imgtag', array( 'title' => T_( 'New account' ) ) ),
			'deactivated'      => get_icon( 'bullet_blue', 'imgtag', array( 'title' => T_( 'Deactivated account' ) ) ),
			'emailchanged'     => get_icon( 'bullet_yellow', 'imgtag', array( 'title' => T_( 'Email address was changed' ) ) ),
			'closed'           => get_icon( 'bullet_black', 'imgtag', array( 'title' => T_( 'Closed account' ) ) ),
			'failedactivation' => get_icon( 'bullet_red', 'imgtag', array( 'title' => T_( 'Account was not activated or the activation failed' ) ) )
		);

	if( $display_text )
	{
		$user_status_icons['activated']        .= ' '.T_( 'Activated' );
		$user_status_icons['autoactivated']    .= ' '.T_( 'Autoactivated' );
		$user_status_icons['new']              .= ' '.T_( 'New' );
		$user_status_icons['deactivated']      .= ' '.T_( 'Deactivated' );
		$user_status_icons['emailchanged']     .= ' '.T_( 'Email changed' );
		$user_status_icons['closed']           .= ' '.T_( 'Closed' );
		$user_status_icons['failedactivation'] .= ' '.T_( 'Failed activation' );
	}

	return $user_status_icons;
}


/**
 * Get all user ID and login where user_login starts with the reserved 'usr_' prefix
 * 
 * @return array result list
 */
function find_logins_with_reserved_prefix()
{
	global $DB;

	return $DB->get_results('SELECT user_ID, user_login FROM T_users WHERE user_login REGEXP "^usr_"');
}


/**
 * Get user reports available statuses
 * 
 * @return array with status key and status text
 */
function get_report_statuses()
{
	return array(
		'fake'       => T_('This user profile is fake'),
		'guidelines' => T_('This user does not follow the guidelines'),
		'harass'     => T_('This user is harassing me'),
		'spam'       => T_('This user is spamming me'),
		'other'      => T_('Other')
	);
}


/**
 * Get current User report from the given user
 * 
 * @param integer user ID to get report from
 * @return array with report status, info and date. The return value is an empty array if current User didn't report the given user.
 */
function get_report_from( $user_ID )
{
	global $DB, $current_User;

	return $DB->get_row( 'SELECT urep_status as status, urep_info as info, urep_datetime as date
								FROM T_users__reports
								WHERE urep_target_user_ID = '.$DB->quote( $user_ID ).'
									AND urep_reporter_ID = '.$DB->quote( $current_User->ID ),
					ARRAY_A );
}


/**
 * Count reprots by status from the given user
 * 
 * @param integer user ID
 * @param boolean set false to get plain result array, or set true to get display format
 * @return mixed array if display format is true, string otherwise
 */
function count_reports_from( $user_ID, $display_format = true )
{
	global $DB, $admin_url;

	$SQL = new SQL();
	$SQL->SELECT( 'urep_status as status, COUNT( DISTINCT( urep_reporter_ID ) ) as num_count' );
	$SQL->FROM( 'T_users__reports' );
	$SQL->WHERE( 'urep_target_user_ID = '.$DB->quote( $user_ID ) );
	$SQL->GROUP_BY( 'urep_status' );
	$reports = $DB->get_assoc( $SQL->get() );

	if( !$display_format )
	{ // don't display return result
		return $reports;
	}

	if( empty( $reports ) )
	{ // there are no reports yet from the given user
		return '<span style="color:green">'.T_('No reports yet.').'</span>';
	}

	$result = '<span style="color:red">';
	foreach( $reports as $status => $num_count )
	{
		$result .= $status.': '.$num_count.'; ';
	}
	$result .= '</span>- <a href="'.url_add_param( $admin_url, 'ctrl=user&amp;user_ID='.$user_ID.'&amp;user_tab=activity#reports_result' ).'">'.T_('View').' &raquo;</a>';
	return $result;
}


/**
 * Report a user
 * 
 * @param integer reported User ID
 * @param string reported user status (fake, guidelines, harass, spam, other )
 * @param string more info
 * @return mixed 1 on success false on error
 */
function add_report_from( $user_ID, $status, $info )
{
	global $DB, $current_User, $localtimenow;

	$UserCache = & get_UserCache();
	$reported_User = $UserCache->get_by_ID( $user_ID, false );
	if( !$reported_User )
	{ // if user doesn't exists return false
		return false;
	}

	$result = $DB->query( 'REPLACE INTO T_users__reports( urep_target_user_ID, urep_reporter_ID, urep_status, urep_info, urep_datetime )
						VALUES( '.$DB->quote( $user_ID ).', '.$DB->quote( $current_User->ID ).', '.$DB->quote( $status ).', '.$DB->quote( $info ).', '.$DB->quote( date2mysql( $localtimenow ) ).' )' );
	if( $result )
	{ // if report was successful send user reported notificaitons to admin users
		$email_template_params = array(
							'login'          => $reported_User->login,
							'email'          => $reported_User->email,
							'report_status'  => get_report_status_text( $status ),
							'report_info'    => $info,
							'user_ID' 		 => $user_ID,
							'reported_by'    => $current_User->login,
						);
		// send notificaiton ( it will be send to only those users who want to receive this kind of notifications )
		send_admin_notification( NT_('User account reported'), 'user_reported', $email_template_params );
	}

	return $result;
}


/**
 * Remove current User report from the given user
 * 
 * @param integer user ID
 * @return mixed 1 if report was removed, 0 if there was no report, false on failure
 */
function remove_report_from( $user_ID )
{
	global $DB, $current_User;

	return $DB->query( 'DELETE FROM T_users__reports
						WHERE urep_target_user_ID = '.$DB->quote( $user_ID ).' AND urep_reporter_ID = '.$DB->quote( $current_User->ID ) );
}


/**
 * Get form to quick users search
 *
 * @param array Params
 * @return string Form
 */
function get_user_quick_search_form( $params = array() )
{
	$params = array_merge( array(
			'before' => '<div class="quick_search_form">',
			'after'  => '</div>',
			'title'  => T_('Quick search'),
			'button' => T_('Find User'),
		), $params );

	$r = $params['before'];

	$Form = new Form();

	$Form->output = false;
	$Form->switch_layout( 'none' );

	$r .= $Form->begin_form();

	$Form->hidden_ctrl();
	$Form->add_crumb( 'user' );

	$r .= $Form->text_input( 'user_search', '', 15, $params['title'], '', array( 'maxlength' => 100 ) );
	
	$r .= $Form->submit_input( array(
			'name'  => 'actionArray[search]',
			'value' => $params['button']
		) );

	$r .= $Form->end_form();

	$r .= $params['after'];

	return $r;
}


/**
 * Display a voting form
 *
 * @param array Params
 */
function display_voting_form( $params = array() )
{
	$params = array_merge( array(
			'vote_type' => 'file',
			'vote_ID'   => 0,
			'display_like'          => true,
			'display_noopinion'     => true,
			'display_dontlike'      => true,
			'display_inappropriate' => true,
			'display_spam'          => true,
			'title_text'            => T_('My vote:'),
			'title_like'            => T_('I like this picture'),
			'title_like_voted'      => T_('You like this!'),
			'title_noopinion'       => T_('I have no opinion'),
			'title_noopinion_voted' => T_('You have no opinion on this.'),
			'title_dontlike'        => T_('I don\'t like this picture'),
			'title_dontlike_voted'  => T_('You don\'t like this.'),
			'title_inappropriate'   => T_('I think the content of this picture is inappropriate'),
			'title_spam'            => T_('I think this picture was posted by a spammer'),
		), $params );

	if( !is_logged_in() || empty( $params['vote_ID'] ) )
	{
		return;
	}

	global $current_User, $DB;

	$params_like = array(
			'id' => 'votingLike',
			'title' => $params['title_like']
		);
	$params_noopinion = array(
			'id' => 'votingNoopinion',
			'title' => $params['title_noopinion']
		);
	$params_dontlike = array(
			'id' => 'votingDontlike',
			'title' => $params['title_dontlike']
		);
	$params_inappropriate = array(
			'id' => 'votingInappropriate',
			'title' => $params['title_inappropriate']
		);
	$params_spam = array(
			'id' => 'votingSpam',
			'title' => $params['title_spam']
		);

	switch( $params['vote_type'] )
	{	// Get a voting results for current user
		case 'file':
			// Picture
			$SQL = new SQL( 'Get file voting for current user' );
			$SQL->SELECT( 'fvot_like AS result, fvot_inappropriate AS inappropriate, fvot_spam AS spam' );
			$SQL->FROM( 'T_files__vote' );
			$SQL->WHERE( 'fvot_file_ID = '.$DB->quote( $params['vote_ID'] ) );
			$SQL->WHERE_and( 'fvot_user_ID = '.$DB->quote( $current_User->ID ) );
			$vote = $DB->get_row( $SQL->get() );

			$params_spam['class'] = 'cboxCheckbox';

			break;

		case 'comment':
			// Comment
			$SQL = new SQL();
			$SQL->SELECT( 'cmvt_helpful AS result' );
			$SQL->FROM( 'T_comments__votes' );
			$SQL->WHERE( 'cmvt_cmt_ID = '.$DB->quote( $params['vote_ID'] ) );
			$SQL->WHERE_and( 'cmvt_user_ID = '.$DB->quote( $current_User->ID ) );
			$SQL->WHERE_and( 'cmvt_helpful IS NOT NULL' );
			$vote = $DB->get_row( $SQL->get() );

			break;
	}

	if( empty( $vote ) || is_null( $vote->result ) )
	{	// Current user didn't vote for this file yet
		$icon_like = 'thumb_up';
		$icon_noopinion = 'ban';
		$icon_dontlike = 'thumb_down';
		$type_voted = '';
	}
	else
	{	// Current user already voted for this file, We should set a disabled icons correctly
		switch( $vote->result )
		{
			case '-1':
				// Don't like
				$type_voted = 'dontlike';
				$icon_like = 'thumb_up_disabled';
				$icon_noopinion = 'ban_disabled';
				$icon_dontlike = 'thumb_down';
				$params_dontlike['class'] = 'voted';
				$params_dontlike['title'] = $params['title_dontlike_voted'];
				unset( $params_dontlike['id'] );
				break;

			case '0':
				// No opinion
				$type_voted = 'noopinion';
				$icon_like = 'thumb_up_disabled';
				$icon_noopinion = 'ban';
				$icon_dontlike = 'thumb_down_disabled';
				$params_noopinion['class'] = 'voted';
				$params_noopinion['title'] = $params['title_noopinion_voted'];
				unset( $params_noopinion['id'] );
				break;

			case '1':
				// Like
				$type_voted = 'like';
				$icon_like = 'thumb_up';
				$icon_noopinion = 'ban_disabled';
				$icon_dontlike = 'thumb_down_disabled';
				$params_like['class'] = 'voted';
				$params_like['title'] = $params['title_like_voted'];
				unset( $params_like['id'] );
				break;
		}
	}

	$checked_inappropriate = '';
	$checked_spam = '';
	if( !empty( $vote ) )
	{	// Current user already marked this file
		if( !empty( $vote->inappropriate ) )
		{	// File is marked as 'Inappropriate'
			$checked_inappropriate = ' checked="checked"';
		}
		if( !empty( $vote->spam ) )
		{	// File is marked as 'Spam'
			$checked_spam = ' checked="checked"';
		}
	}

	echo '<span>'.$params['title_text'].'</span>';

	// Set this url for case when JavaScript is not enabled
	$url = get_secure_htsrv_url().'anon_async.php?action=voting&vote_type='.$params['vote_type'].'&vote_ID='.$params['vote_ID'].'&'.url_crumb( 'voting' );
	$redirect_to = regenerate_url();
	if( strpos( $redirect_to, 'async.php' ) === false )
	{	// Append a redirect param
		$url .= '&redirect_to='.$redirect_to;
	}

	if( $params['display_like'] )
	{	// Display 'Like' icon
		$tag_icon = get_icon( $icon_like, 'imgtag', $params_like );
		if( $type_voted == 'like' )
		{
			echo $tag_icon;
		}
		else
		{
			$url_like = $url.'&vote_action=like';
			$class = ( strpos( $icon_like, 'disabled' ) !== false ) ? ' rollover_sprite' : '';
			echo '<a href="'.$url_like.'" class="action_icon'.$class.'">'.$tag_icon.'</a>';
		}
	}

	if( $params['display_noopinion'] )
	{	// Display 'No opinion' icon
		$tag_icon = get_icon( $icon_noopinion, 'imgtag', $params_noopinion );
		if( $type_voted == 'noopinion' )
		{
			echo $tag_icon;
		}
		else
		{
			$url_noopinion = $url.'&vote_action=noopinion';
			$class = ( strpos( $icon_noopinion, 'disabled' ) !== false ) ? ' rollover_sprite' : '';
			echo '<a href="'.$url_noopinion.'" class="action_icon'.$class.'">'.$tag_icon.'</a>';
		}
	}

	if( $params['display_dontlike'] )
	{	// Display 'Dont like' icon
		$tag_icon = get_icon( $icon_dontlike, 'imgtag', $params_dontlike );
		if( $type_voted == 'dontlike' )
		{
			echo $tag_icon;
		}
		else
		{
			$url_dontlike = $url.'&vote_action=dontlike';
			$class = ( strpos( $icon_dontlike, 'disabled' ) !== false ) ? ' rollover_sprite' : '';
			echo '<a href="'.$url_dontlike.'" class="action_icon'.$class.'">'.$tag_icon.'</a>';
		}
	}

	if( $params['display_inappropriate'] || $params['display_spam'] )
	{	// Display separator between icons and checkboxes
		echo '<span class="separator">&nbsp;</span>';
	}

	if( $params['display_inappropriate'] )
	{	// Display 'Inappropriate' checkbox
		echo '<label for="'.$params_inappropriate['id'].'" title="'.$params_inappropriate['title'].'">'.
				'<input type="checkbox" id="'.$params_inappropriate['id'].'" name="'.$params_inappropriate['id'].'"'.$checked_inappropriate.' />'.
				'<span>'.T_('Inappropriate').'</span>'.
			'</label>';
	}

	if( $params['display_spam'] )
	{	// Display 'Spam' checkbox
		echo '<label for="'.$params_spam['id'].'" class="'.$params_spam['class'].'" title="'.$params_spam['title'].'">'.
				'<input type="checkbox" id="'.$params_spam['id'].'" name="'.$params_spam['id'].'"'.$checked_spam.' />'.
				'<span>'.T_('Spam').'</span>'.
			'</label>';
	}

	// Create a hidden input with current ID
	echo '<input type="hidden" id="votingID" value="'.$params['vote_ID'].'" />';
}


/**
 * Find other users with the same email address
 *
 * @param integer User ID to exclude current edited user from list
 * @param string User email
 * @param string Message for note about users with the same email
 * @return boolean|string FALSE if no users with the same email | Message about other users also have the same email
 */
function find_users_with_same_email( $user_ID, $user_email, $message )
{
	global $DB;

	$email_users_SQL = new SQL();
	$email_users_SQL->SELECT( 'user_ID, user_login' );
	$email_users_SQL->FROM( 'T_users' );
	$email_users_SQL->WHERE( 'user_email = '.$DB->quote( $user_email ) );
	$email_users_SQL->WHERE_and( 'user_ID != '.$DB->quote( $user_ID ) );
	$email_users = $DB->get_assoc( $email_users_SQL->get() );
	if( empty( $email_users ) )
	{ // No users found with email
		return false;
	}

	$users_links = array();
	foreach( $email_users as $email_user_ID => $email_user_login )
	{
		global $admin_url;
		$users_links[] = '<a href="'.$admin_url.'?ctrl=user&amp;user_ID='.$email_user_ID.'">'.$email_user_login.'</a>';
	}

	return sprintf( $message, $user_email, implode( ', ', $users_links ) );
}


/**
 * Display user's reposts results table
 *
 * @param array Params
 */
function user_reports_results_block( $params = array() )
{
	// Make sure we are not missing any param:
	$params = array_merge( array(
			'edited_User'          => NULL,
			'results_param_prefix' => 'actv_reports_',
			'results_title'        => T_('Reports from the user'),
			'results_no_text'      => T_('User was not reported yet.'),
		), $params );

	if( !is_logged_in() )
	{	// Only logged in users can access to this function
		return;
	}

	global $current_User;
	if( !$current_User->check_perm( 'users', 'edit' ) )
	{	// Check minimum permission:
		return;
	}

	$edited_User = $params['edited_User'];
	if( !$edited_User )
	{	// No defined User, probably the function is calling from AJAX request
		$user_ID = param( 'user_ID', 'integer', 0 );
		if( empty( $user_ID ) )
		{	// Bad request, Exit here
			return;
		}
		$UserCache = & get_UserCache();
		if( ( $edited_User = & $UserCache->get_by_ID( $user_ID, false ) ) === false )
		{	// Bad request, Exit here
			return;
		}
	}

	global $DB;

	param( 'user_tab', 'string', '', true );
	param( 'user_ID', 'integer', 0, true );


	$SQL = new SQL();
	$SQL->SELECT( 'user_login, urep_datetime, urep_status, urep_info' );
	$SQL->FROM( 'T_users__reports' );
	$SQL->FROM_add( 'LEFT JOIN T_users ON user_ID = urep_reporter_ID' );
	$SQL->WHERE( 'urep_target_user_ID = '.$DB->quote( $edited_User->ID ) );

	// Create result set:
	$reports_Results = new Results( $SQL->get(), $params['results_param_prefix'], 'D' );
	$reports_Results->title = $params['results_title'];
	$reports_Results->no_results_text = $params['results_no_text'];

	// Initialize Results object
	user_reports_results( $reports_Results );

	if( is_ajax_content() )
	{	// init results param by template name
		if( !isset( $params[ 'skin_type' ] ) || ! isset( $params[ 'skin_name' ] ) )
		{
			debug_die( 'Invalid ajax results request!' );
		}
		$reports_Results->init_params_by_skin( $params[ 'skin_type' ], $params[ 'skin_name' ] );
	}

	$display_params = array(
		'before' => '<div class="results" style="margin-top:25px" id="reports_result">'
	);
	$reports_Results->display( $display_params );

	if( !is_ajax_content() )
	{	// Create this hidden div to get a function name for AJAX request
		echo '<div id="'.$params['results_param_prefix'].'ajax_callback" style="display:none">'.__FUNCTION__.'</div>';
	}

	// Who should be able to delete other users reports???
	/*if( $reports_Results->total_rows > 0 )
	{	// Display button to delete all records if at least one record exists & current user can delete at least one item created by user
		echo action_icon( sprintf( T_('Delete all reports from %s'), $edited_User->login ), 'delete', '?ctrl=user&amp;user_tab=activity&amp;action=delete_all_reports_from&amp;user_ID='.$edited_User->ID.'&amp;'.url_crumb('user'), ' '.T_('Delete all'), 3, 4 );
	}*/
}


/**
 * Initialize Results object for threads list
 *
 * @param object Results
 * @param array Params
 */
function user_reports_results( & $reports_Results, $params = array() )
{
	$reports_Results->cols[] = array(
		'th' => T_('Date and time'),
		'order' => 'urep_datetime',
		'default_dir' => 'D',
		'th_class' => 'nowrap',
		'td_class' => 'shrinkwrap',
		'td' => '<span class="date">%mysql2localedatetime( #urep_datetime# )%</span>',
	);

	$reports_Results->cols[] = array(
		'th' => T_('Reporting user'),
		'order' => 'user_login',
		'td_class' => 'left',
		'td' => '%get_user_identity_link( #user_login# )%',
	);

	$reports_Results->cols[] = array(
		'th' => T_('Selected option in the select list'),
		'order' => 'urep_status',
		'td_class' => 'nowrap',
		'td' => '%get_report_status_text( #urep_status# )%',
	);

	$reports_Results->cols[] = array(
		'th' => T_('Additional info'),
		'order' => 'urep_info',
		'td_class' => 'left',
		'td' => '$urep_info$',
	);
}


/**
 * Helper functions to display User's reports results.
 * New ( not display helper ) functions must be created above user_reports_results function
 */

function get_report_status_text( $status )
{
	$statuses = get_report_statuses();
	return $statuses[ $status ];
}

/**
 * Helper functions to display User's reports results.
 * New ( not display helper ) functions must be created above user_reports_results function
 */


/*
 * $Log$
 * Revision 1.102  2013/11/06 08:05:03  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>