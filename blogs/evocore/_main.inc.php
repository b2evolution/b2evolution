<?php
/**
 * This file initializes everything BUT the blog!
 *
 * It is useful when you want to do very customized templates!
 * It is also called by more complete initializers.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005 by The University of North Carolina at Charlotte as
 * contributed by Jason Edgecombe {@link http://tst.uncc.edu/team/members/jason_bio.php}.
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
 *
 * In addition, as a special exception, the copyright holders give permission to link
 * the code of this program with the PHP/SWF Charts library by maani.us (or with
 * modified versions of this library that use the same license as PHP/SWF Charts library
 * by maani.us), and distribute linked combinations including the two. You must obey the
 * GNU General Public License in all respects for all of the code used other than the
 * PHP/SWF Charts library by maani.us. If you modify this file, you may extend this
 * exception to your version of the file, but you are not obligated to do so. If you do
 * not wish to do so, delete this exception statement from your version.
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * The University of North Carolina at Charlotte grants François PLANQUE the right to license
 * Jason EDGECOMBE's contributions to this file and the b2evolution project
 * under the GNU General Public License (http://www.opensource.org/licenses/gpl-license.php)
 * and the Mozilla Public License (http://www.opensource.org/licenses/mozilla1.1.php).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * {@internal Below is a list of former authors whose contributions to this file have been
 *            either removed or redesigned and rewritten anew:
 *            - t3dworld
 *            - tswicegood
 * }}
 *
 * @version $Id$
 */

/**
 * Prevent double loading since require_once won't work in all situations
 * on windows when some subfolders have caps :(
 * (Check it out on static page generation)
 */
if( defined( 'EVO_MAIN_INIT' ) )
{
	return;
}
define( 'EVO_MAIN_INIT', true );


/**
 * Load logging class
 */
require_once dirname(__FILE__).'/_log.class.php';
/**
 * Debug message log for debugging only (initialized here)
 * @global Log $Debuglog
 */
$Debuglog = & new Log( 'note' );
/**
 * Info & error message log for end user (initialized here)
 * @global Log $Messages
 */
$Messages = & new Log( 'error' );


/**
 * Start timer:
 */
require_once dirname(__FILE__).'/_timer.class.php';
$Timer = & new Timer('main');


/**
 * Load base + advanced configuration:
 */
require_once dirname(__FILE__).'/../conf/_config.php';
if( !$config_is_done )
{ // base config is not done!
	$error_message = 'Base configuration is not done! (see /conf/_config.php)';
}
elseif( !isset( $locales[$default_locale] ) )
{
	$error_message = 'The default locale '.var_export( $default_locale, true ).' does not exist! (see /conf/_locales.php)';
}
if( isset( $error_message ) )
{ // error & exit
	require dirname(__FILE__).'/_conf_error.inc.php';
}


/**
 * Sets various arrays and vars
 */
require_once dirname(__FILE__).'/_vars.inc.php';


/**
 * Load DB class
 */
require_once dirname(__FILE__).'/_db.class.php';
/**
 * Database connection (connection opened here)
 *
 * @global DB $DB
 */
$DB = new DB( $EvoConfig->DB['user'], $EvoConfig->DB['password'], $EvoConfig->DB['name'], $EvoConfig->DB['host'], $db_aliases, $db_use_transactions, $db_table_options );


/**
 * Load settings class
 */
require_once dirname(__FILE__).'/_generalsettings.class.php';
require_once dirname(__FILE__).'/_usersettings.class.php';
/**
 * Interface to general settings
 *
 * @global GeneralSettings $Settings
 */
$Settings = & new GeneralSettings();
/**
 * Interface to user settings
 *
 * @global UserSettings $UserSettings
 */
$UserSettings = & new UserSettings();


/**
 * Absolute Unix timestamp for server
 * @global int $servertimenow
 */
$servertimenow = time();
/**
 * Corrected Unix timestamp to match server timezone
 * @global int $localtimenow
 */
$localtimenow = $servertimenow + ($Settings->get('time_difference') * 3600);


/**
 * The Hit class
 */
require_once dirname(__FILE__).'/_hit.class.php';
/**
 * @global Hit The Hit object
 */
$Hit = & new Hit();


/**
 * Load Request class
 */
require_once dirname(__FILE__).'/_request.class.php';
/**
 * Debug message log for debugging only (initialized here)
 * @global Request $Request
 */
$Request = & new Request( $Messages );


/**
 * Check conf...
 */
if( !function_exists( 'gzencode' ) )
{ // when there is no function to gzip, we won't do it
	$Debuglog->add( '$use_gzipcompression is true, but the function gzencode() does not exist. Disabling gzip compression.' );
	$use_gzipcompression = false;
}

if( !isset( $use_html_checker ) ) $use_html_checker = 1;


/**
 * Includes:
 */
require_once dirname(__FILE__).'/_blog.funcs.php';
require_once dirname(__FILE__).'/_item.funcs.php';
require_once dirname(__FILE__).'/_category.funcs.php';
require_once dirname(__FILE__).'/_comment.funcs.php';
require_once dirname(__FILE__).'/_file.funcs.php';
if( $use_html_checker ) require_once dirname(__FILE__).'/_htmlchecker.class.php';
require_once dirname(__FILE__).'/_item.funcs.php';
require_once dirname(__FILE__).'/_message.funcs.php';
require_once dirname(__FILE__).'/_pingback.funcs.php';
require_once dirname(__FILE__).'/_ping.funcs.php';
require_once dirname(__FILE__).'/_skin.funcs.php';
require_once dirname(__FILE__).'/_trackback.funcs.php';
require_once dirname(__FILE__).'/_user.funcs.php';


/**
 * Optionnaly include obsolete functions
 */
@include_once dirname(__FILE__).'/_obsolete092.php';


require_once dirname(__FILE__).'/_resultsel.class.php';


/**
 * Includes:
 */
require_once dirname(__FILE__).'/_template.funcs.php';    // function to be called from templates
require_once dirname(__FILE__).'/'.$core_dirout.$lib_subdir.'_xmlrpc.php';
require_once dirname(__FILE__).'/'.$core_dirout.$lib_subdir.'_xmlrpcs.php';
require_once dirname(__FILE__).'/_blog.class.php';
require_once dirname(__FILE__).'/_itemlist.class.php';
require_once dirname(__FILE__).'/_itemcache.class.php';
require_once dirname(__FILE__).'/_commentlist.class.php';
require_once dirname(__FILE__).'/_dataobjectcache.class.php';
require_once dirname(__FILE__).'/_element.class.php';
require_once dirname(__FILE__).'/_filecache.class.php';
require_once dirname(__FILE__).'/_usercache.class.php';
require_once dirname(__FILE__).'/_link.class.php';
require_once dirname(__FILE__).'/_linkcache.class.php';
require_once dirname(__FILE__).'/_file.class.php';
require_once dirname(__FILE__).'/_filerootcache.class.php';
// Object caches init:

// fplanque>>jwedgeco: I cannot commit this: $BlogCache = array();
$response = NULL;

if ( $use_memcached )
{
	// TODO: fplanque>>jwedgeco: please document why you set a dummy here ;)
	$response = $memcache->set( 'dummy' , 0);
	$response = $memcache->get( 'BlogCache' );
	$GroupCache =  $memcache->get( 'GroupCache' );
	$ItemCache =  $memcache->get( 'ItemCache' );
	$itemTypeCache =  $memcache->get( 'itemTypeCache' );
	$itemStatusCache =  $memcache->get( 'itemStatusCache' );
	$LinkCache =  $memcache->get( 'LinkCache' );
	$UserCache =  $memcache->get( 'UserCache' );
//	echo "<pre>\n";
//	print_r($response);
//	echo "</pre>\n";

}

// TODO FIXME test for null on each object.
if ( $response == null )
{
	$BlogCache = & new BlogCache();
	$FileCache = & new fileCache();
	$FileRootCache = & new FileRootCache();
	$GroupCache = & new DataObjectCache( 'Group', true, 'T_groups', 'grp_', 'grp_ID' );
	$ItemCache = & new ItemCache();
	$itemTypeCache = & new DataObjectCache( 'Element', true, 'T_posttypes', 'ptyp_', 'ptyp_ID' );
	$itemStatusCache = & new DataObjectCache( 'Element', true, 'T_poststatuses', 'pst_', 'pst_ID' );
	$LinkCache = & new LinkCache();
	$UserCache = & new UserCache();
}
else
{
	$BlogCache = & $response;
//	$GroupCache = & new DataObjectCache( 'Group', true, 'T_groups', 'grp_', 'grp_ID' );
//	$ItemCache = & new ItemCache();
//	$itemTypeCache = & new DataObjectCache( 'Element', true, 'T_posttypes', 'ptyp_', 'ptyp_ID' );
//	$itemStatusCache = & new DataObjectCache( 'Element', true, 'T_poststatuses', 'pst_', 'pst_ID' );
//	$LinkCache = & new LinkCache();
//	$UserCache = & new UserCache();
	$Debuglog->add( 'Found key BlogCache in memcache: ' .  $BlogCache , 'note' );
}



require_once dirname(__FILE__).'/_hitlog.funcs.php';     // referer logging
require_once dirname(__FILE__).'/_form.funcs.php';
require_once dirname(__FILE__).'/_form.class.php';
require_once dirname(__FILE__).'/_itemquery.class.php';
require_once dirname(__FILE__).'/'.$core_dirout.$lib_subdir.'_swfcharts.php';

/**
 * Plug-ins init:
 */
require_once dirname(__FILE__).'/_plugins.class.php';
$Plugins = & new Plugins();


/**
 * Output buffering?
 */
if( $use_obhandler )
{ // register output buffer handler
	ob_start( 'obhandler' );
}


/**
 * Locale selection:
 */
$Debuglog->add( 'default_locale from conf: '.$default_locale, 'locale' );

locale_overwritefromDB();
$Debuglog->add( 'default_locale from DB: '.$default_locale, 'locale' );

$default_locale = locale_from_httpaccept(); // set default locale by autodetect
$Debuglog->add( 'default_locale from HTTP_ACCEPT: '.$default_locale, 'locale' );

if( ($locale_from_get = param( 'locale', 'string', NULL, true ))
		&& $locale_from_get != $default_locale
		&& isset( $locales[$locale_from_get] ) )
{
	$default_locale = $locale_from_get;
	$Debuglog->add( 'Overriding locale from REQUEST: '.$default_locale, 'locale' );
}


/**
 * Activate default locale:
 */
locale_activate( $default_locale );

/**
 * Login procedure: {{{
 */
if( !isset($login_required) )
{
	$login_required = false;
}


// TODO: prevent brute force attacks! (timeout - based on coming Session or Hit object?)

$login = $pass = $pass_md5 = NULL;

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


$Debuglog->add( 'login: '.var_export($login, true), 'login' );
$Debuglog->add( 'pass: '.( empty($pass) ? '' : 'not' ).' empty', 'login' );
$Debuglog->add( 'cookie_user: '.var_export(@$_COOKIE[$cookie_user], true), 'login' );


if( !empty($login) && !empty($pass) )
{ // User is trying to login right now
	$Debuglog->add( 'User is trying to login right now.', 'login' );

	$login = strtolower(strip_tags(get_magic_quotes_gpc() ? stripslashes($login) : $login));
	$pass = strip_tags(get_magic_quotes_gpc() ? stripslashes($pass) : $pass);
	$pass_md5 = md5( $pass );

	// echo 'Trying to log in right now...';
	header_nocache();

	$Plugins->trigger_event( 'LoginAttempt', array( 'login' => $login, 'pass' => $pass, 'pass_md5' => $pass_md5 ) );

	// Check login and password
	if( !user_pass_ok( $login, $pass_md5, true ) )
	{ // Login failed
		$Debuglog->add( 'user_pass_ok() returned false!', 'login' );

		$login = NULL;

		if( $login_required )
		{
			// echo 'login failed!!';
			$Messages->add( T_('Wrong login/password.'), 'login_error' );
		}
	}
	else
	{ // Login succeeded, set cookies
		$Debuglog->add( 'Ok. Setting cookies.', 'login' );

		//echo $login, $pass_is_md5, $user_pass,  $cookie_domain;
		if( !setcookie( $cookie_user, $login, $cookie_expires, $cookie_path, $cookie_domain ) )
		{
			printf( T_('setcookie &laquo;%s&raquo; failed!'). '<br />', $cookie_user );
		}
		if( !setcookie( $cookie_pass, $pass_md5, $cookie_expires, $cookie_path, $cookie_domain) )
		{
			printf( T_('setcookie &laquo;%s&raquo; failed!'). '<br />', $cookie_user );
		}
	}
}
elseif( !isset($login) && isset($_COOKIE[$cookie_user]) && isset($_COOKIE[$cookie_pass]) )
{ /*
	 * ---------------------------------------------------------
	 * User was not trying to log in, but he already was logged in: check validity
	 * ---------------------------------------------------------
	 */
	// echo 'Was already logged in...';
	$Debuglog->add( 'Was already logged in... ['.$login.']', 'login' );

	$login = trim(strip_tags(get_magic_quotes_gpc() ? stripslashes($_COOKIE[$cookie_user]) : $_COOKIE[$cookie_user]));
	$pass_md5 = trim(strip_tags(get_magic_quotes_gpc() ? stripslashes($_COOKIE[$cookie_pass]) : $_COOKIE[$cookie_pass]));
	// echo 'pass=', $pass_md5;

	if( !user_pass_ok( $login, $pass_md5, true ) )
	{ // login is NOT OK:
		$Debuglog->add( 'user_pass_ok() returned false!', 'login' );

		$login = NULL;

		if( $login_required )
		{
			$Debuglog->add( 'Login is required!', 'login' );
			$Messages->add( T_('Login/password no longer valid.'), 'login_error' );
		}
	}
}
elseif( $login_required )
{ /*
	 * ---------------------------------------------------------
	 * User was not logged in at all, but login is required
	 * ---------------------------------------------------------
	 */
	// echo ' NOT logged in...';
	$Debuglog->add( 'NOT logged in...', 'login' );

	$Messages->add( T_('You must log in!'), 'login_error' );
}
unset($pass);


if( !empty($login) && !$Messages->count('login_error') )
{
	$current_User =& $UserCache->get_by_login($login);

	if( !$current_User )
	{
		$Messages->add( 'Error while loading user data!', 'login_error' );
	}
}

if( $Messages->count( 'login_error' ) )
{
	header_nocache();

	require dirname(__FILE__).'/'.$core_dirout.$htsrv_subdir.'login.php';
	exit();
}


#echo $current_User->disp('login');

// Login procedure }}}


/**
 * The Session class
 */
require_once dirname(__FILE__).'/_session.class.php';
/**
 * @global Session The Session object
 */
$Session =& new Session();

/**
 * The Sessions class
 */
require_once dirname(__FILE__).'/_sessions.class.php';
/**
 * @global Sessions The Sessions object
 */
$Sessions = & new Sessions();


/**
 * User locale selection:
 */
if( is_logged_in() && $current_User->get('locale') != $current_locale
		&& !$locale_from_get )
{ // change locale to users preference
	locale_activate( $current_User->get('locale') );
	if( $current_locale == $current_User->get('locale') )
	{
		$default_locale = $current_locale;
		$Debuglog->add( 'default_locale from user profile: '.$default_locale, 'locale' );
	}
	else
	{
		$Debuglog->add( 'locale from user profile could not be activated: '.$current_User->get('locale'), 'locale' );
	}
}


/**
 * Load the icons - we need the users locale set there ({@link T_()})
 */
require_once $conf_path.'_icons.php';


/**
 * Load hacks file if it exists
 */
@include_once dirname(__FILE__).'/../conf/hacks.php';

/*
 * $Log$
 * Revision 1.47  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.46  2005/09/01 17:11:46  fplanque
 * no message
 *
 * Revision 1.45  2005/08/31 19:08:51  fplanque
 * Factorized Item query WHERE clause.
 * Fixed calendar contextual accuracy.
 *
 * Revision 1.44  2005/08/26 16:52:58  fplanque
 * no message
 *
 * Revision 1.43  2005/08/23 00:08:16  blueyed
 * Added debug level "login". Unsetting $login
 *
 * Revision 1.42  2005/08/22 19:22:23  fplanque
 * minor
 *
 * Revision 1.41  2005/08/22 18:27:37  blueyed
 * Allow empty login to force login form.
 *
 * Revision 1.40  2005/08/22 18:08:20  blueyed
 * Reworked login logic to allow providing a username (without password) override cookie login
 *
 * Revision 1.39  2005/07/29 17:56:18  fplanque
 * Added functionality to locate files when they're attached to a post.
 * permission checking remains to be done.
 *
 * Revision 1.38  2005/07/12 23:05:36  blueyed
 * Added Timer class with categories 'main' and 'sql_queries' for now.
 *
 * Revision 1.37  2005/06/03 20:14:39  fplanque
 * started input validation framework
 *
 * Revision 1.36  2005/06/02 18:50:52  fplanque
 * no message
 *
 * Revision 1.35  2005/05/13 18:41:28  fplanque
 * made file links clickable... finally ! :P
 *
 * Revision 1.34  2005/05/10 18:40:08  fplanque
 * normalizing
 *
 * Revision 1.33  2005/04/28 20:44:20  fplanque
 * normalizing, doc
 *
 * Revision 1.32  2005/04/26 18:19:25  fplanque
 * no message
 *
 * Revision 1.31  2005/04/19 18:04:38  fplanque
 * implemented nested transactions for MySQL
 *
 * Revision 1.30  2005/04/19 16:23:03  fplanque
 * cleanup
 * added FileCache
 * improved meta data handling
 *
 * Revision 1.29  2005/04/06 13:33:29  fplanque
 * minor changes
 *
 * Revision 1.28  2005/04/05 13:44:22  jwedgeco
 * Added experimental memcached support. Needs much more work. Use at your own risk.
 *
 * Revision 1.27  2005/03/15 19:19:47  fplanque
 * minor, moved/centralized some includes
 *
 * Revision 1.25  2005/03/14 20:22:19  fplanque
 * refactoring, some cacheing optimization
 *
 * Revision 1.24  2005/03/07 17:11:25  fplanque
 * added debug helpers
 *
 * Revision 1.23  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.22  2005/02/28 01:32:32  blueyed
 * Hitlog refactoring, part uno.
 *
 * Revision 1.21  2005/02/23 23:05:06  blueyed
 * fixed login / pass/pass_md5
 *
 * Revision 1.20  2005/02/23 20:36:15  blueyed
 * also pass raw password to LoginAttempt plugin event
 *
 * Revision 1.19  2005/02/22 02:42:21  blueyed
 * Login refactored (send password-change-request mail instead of new password)
 *
 * Revision 1.18  2005/02/21 00:34:34  blueyed
 * check for defined DB_USER!
 *
 * Revision 1.17  2005/02/15 22:05:08  blueyed
 * Started moving obsolete functions to _obsolete092.php..
 *
 * Revision 1.16  2005/02/15 20:05:51  fplanque
 * no message
 *
 * Revision 1.15  2005/02/10 23:00:31  blueyed
 * small enhancements
 *
 * Revision 1.14  2005/02/08 23:57:20  blueyed
 * moved Debugmessage, ..
 *
 * Revision 1.13  2005/01/03 06:21:35  blueyed
 * moved declaration of $map_iconsfiles, $map_iconsizes so that they can make use of T_()
 *
 * Revision 1.12  2004/12/27 18:37:58  fplanque
 * changed class inheritence
 *
 * Revision 1.10  2004/12/21 21:18:38  fplanque
 * Finished handling of assigning posts/items to users
 *
 * Revision 1.9  2004/12/20 19:49:24  fplanque
 * cleanup & factoring
 *
 * Revision 1.8  2004/12/17 20:38:52  fplanque
 * started extending item/post capabilities (extra status, type)
 *
 * Revision 1.7  2004/11/09 00:25:12  blueyed
 * minor translation changes (+MySQL spelling :/)
 *
 * Revision 1.6  2004/10/28 11:11:09  fplanque
 * MySQL table options handling
 *
 * Revision 1.5  2004/10/21 00:14:44  blueyed
 * moved
 *
 * Revision 1.4  2004/10/17 20:18:37  fplanque
 * minor changes
 *
 * Revision 1.3  2004/10/16 01:31:22  blueyed
 * documentation changes
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.73  2004/10/12 18:48:34  fplanque
 * Edited code documentation.
 *
 */
?>