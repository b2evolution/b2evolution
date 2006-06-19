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
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 *
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
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
 * @author mfollett: Matt FOLLETT.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * {@internal Below is a list of former authors whose contributions to this file have been
 *            either removed or redesigned and rewritten anew:
 *            - t3dworld
 *            - tswicegood
 * }}
 *
 * @version $Id$
 */

if( $maintenance_mode )
{
	header('HTTP/1.0 503 Service Unavailable');
	echo '<h1>503 Service Unavailable</h1>';
	die( 'The site is temporarily down for maintenance.' );
}


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
 * Security check for older PHP versions
 * Contributed by counterpoint / MAMBO team
 */
$protects = array( '_REQUEST', '_GET', '_POST', '_COOKIE', '_FILES', '_SERVER', '_ENV', 'GLOBALS', '_SESSION' );
foreach( $protects as $protect )
{
	if(  in_array( $protect, array_keys($_REQUEST) )
		|| in_array( $protect, array_keys($_GET) )
		|| in_array( $protect, array_keys($_POST) )
		|| in_array( $protect, array_keys($_COOKIE) )
		|| in_array( $protect, array_keys($_FILES) ) )
	{
		bad_request_die( 'Unacceptable params.' );
	}
}
/*
 * fp> Alternatively we might want to kill all auto registered globals this way:
 * TODO: testing
 *
$superglobals = array($_SERVER, $_ENV, $_FILES, $_COOKIE, $_POST, $_GET);
if (isset( $_SESSION )) array_unshift ( $superglobals , $_SESSION );
if (ini_get('register_globals') && !$this->mosConfig_register_globals)
{
	foreach ( $superglobals as $superglobal )
	{
		foreach ( $superglobal as $key => $value)
		{
			unset( $GLOBALS[$key]);
		}
	}
}
*/

/**
 * Load logging class
 */
require_once dirname(__FILE__).'/_misc/_log.class.php';
/**
 * Debug message log for debugging only (initialized here).
 *
 * If {@link $debug} is off, it will be re-instantiated of class {@link Log_noop} after loading config
 * and perform no operations.
 * @global Log|Log_noop $Debuglog
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
require_once dirname(__FILE__).'/_misc/_timer.class.php';
$Timer = & new Timer('total');

$Timer->start( 'main.inc' );

/**
 * Load base + advanced configuration:
 */
// Note: this should have been done before coming here...
// require_once dirname(__FILE__).'/_config.php';


/**
 * Sets various arrays and vars, also $app_name!
 *
 * Needed before the error messages.
 */
require_once dirname(__FILE__).'/_vars.inc.php';


if( !$config_is_done )
{ // base config is not done!
	$error_message = 'Base configuration is not done! (see /conf/_basic_config.php)';
}
elseif( !isset( $locales[$default_locale] ) )
{
	$error_message = 'The default locale '.var_export( $default_locale, true ).' does not exist! (see /conf/_locales.php)';
}
if( isset( $error_message ) )
{ // error & exit
	require dirname(__FILE__).'/_conf_error.inc.php';
}

if( !$debug )
{
	$Debuglog = & new Log_noop( 'note' );
}


/**
 * Miscellaneous functions
 */
require_once dirname(__FILE__).'/_misc/_misc.funcs.php';


/**
 * Connect to DB
 */
require_once dirname(__FILE__).'/_connect_db.inc.php';


/**
 * Load settings class
 */
require_once $model_path.'settings/_generalsettings.class.php';
require_once $model_path.'users/_usersettings.class.php';
/**
 * Interface to general settings
 *
 * Keep this below the creation of the {@link $DB DB object}, because it checks for the
 * correct db_version and catches "table does not exist" errors, providing a link to the
 * install script.
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

$time_difference = $Settings->get('time_difference') * 3600;

/**
 * Corrected Unix timestamp to match server timezone
 * @global int $localtimenow
 */
$localtimenow = $servertimenow + $time_difference;


/**
 * The Hit class
 */
require_once $model_path.'sessions/_hit.class.php';
/**
 * @global Hit The Hit object
 */
$Hit = & new Hit();


/**
 * The Session class.
 * It has to be instantiated before the "SessionLoaded" hook.
 */
require_once $model_path.'sessions/_session.class.php';
/**
 * @global Session The Session object
 */
$Session = & new Session();


/**
 * Plugins init.
 * This is done quite early here to give an early hook ("SessionLoaded") to plugins (though it might also be moved just after $DB init when there is reason for a hook there).
 * The {@link dnsbl_antispam_plugin} is an example that uses this to check the user's IP against a list of DNS blacklists.
 */
require_once dirname(__FILE__).'/_misc/_plugins.class.php';
/**
 * @global Plugins The Plugin management object
 */
$Plugins = & new Plugins();


// NOTE: it might be faster (though more bandwidth intensive) to spit cached pages (CachePageContent event) than to look into blocking the request (SessionLoaded event).
$Plugins->trigger_event( 'SessionLoaded' );


// Trigger a page content caching plugin. This would either return the cached content here or start output buffering
if( empty($generating_static) )
{
	if( $Session->get( 'core.no_CachePageContent' ) )
	{ // The event is disabled for this request:
		$Session->delete('core.no_CachePageContent');
		$Debuglog->add( 'Skipping CachePageContent event, because of core.no_CachePageContent setting.', 'plugins' );
	}
	elseif( ( $get_return = $Plugins->trigger_event_first_true( 'CachePageContent' ) ) // Plugin responded to the event
			&& ( isset($get_return['data']) ) ) // cached content returned
	{
		echo $get_return['data'];
		// Note: we should not use debug_info() here, because the plugin has probably sent a Content-Length header.
		exit;
	}
}


/**
 * Load Request class
 */
require_once dirname(__FILE__).'/_misc/_request.class.php';
/**
 * Debug message log for debugging only (initialized here)
 * @global Request $Request
 */
$Request = & new Request( $Messages );


if( !isset( $use_html_checker ) ) { $use_html_checker = 1; }


/**
 * Includes:
 */
require_once $model_path.'dataobjects/_dataobjectcache.class.php';
require_once $model_path.'generic/_genericelement.class.php';
require_once $model_path.'generic/_genericcache.class.php';
require_once $model_path.'collections/_blog.class.php';
require_once $model_path.'collections/_blog.funcs.php';
require_once $model_path.'collections/_category.funcs.php';
require_once $model_path.'items/_item.funcs.php';
require_once $model_path.'users/_user.funcs.php';
require_once $inc_path.'_misc/_resultsel.class.php';
require_once $inc_path.'_misc/_template.funcs.php';    // function to be called from templates
require_once $model_path.'files/_filecache.class.php';
require_once $model_path.'files/_file.class.php';
require_once $model_path.'files/_filerootcache.class.php';
require_once $model_path.'files/_filetype.class.php';
require_once $model_path.'files/_filetypecache.class.php';
require_once $model_path.'items/_itemcache.class.php';
require_once $model_path.'items/_itemtype.class.php';
require_once $model_path.'items/_itemtypecache.class.php';
require_once $model_path.'items/_link.class.php';
require_once $model_path.'items/_linkcache.class.php';
require_once $model_path.'users/_usercache.class.php';
require_once $model_path.'comments/_comment.funcs.php';
if( $use_html_checker ) { require_once $inc_path.'_misc/_htmlchecker.class.php'; }
require_once $model_path.'items/_item.funcs.php';
require_once $inc_path.'_misc/_pingback.funcs.php';
require_once $inc_path.'_misc/_ping.funcs.php';
require_once $model_path.'skins/_skin.funcs.php';
require_once $inc_path.'_misc/_trackback.funcs.php';
require_once $inc_path.'_misc/ext/_xmlrpc.php';
require_once $inc_path.'_misc/ext/_xmlrpcs.php';
require_once $model_path.'comments/_commentlist.class.php';
require_once $model_path.'items/_itemlist.class.php';


/**
 * Optionnaly include obsolete functions
 */
@include_once $inc_path.'_misc/_obsolete092.php';


// Object caches init (we're asking plugins that provide the "CacheObjects" event here first):
$Plugins->get_object_from_cacheplugin_or_create( 'FileRootCache' );
$Plugins->get_object_from_cacheplugin_or_create( 'FiletypeCache' );
$Plugins->get_object_from_cacheplugin_or_create( 'GroupCache', '& new DataObjectCache( \'Group\', true, \'T_groups\', \'grp_\', \'grp_ID\' )' );
$Plugins->get_object_from_cacheplugin_or_create( 'ItemTypeCache', '& new ItemTypeCache( \'ptyp_\', \'ptyp_ID\' )' );
$Plugins->get_object_from_cacheplugin_or_create( 'ItemStatusCache', '& new GenericCache( \'GenericElement\', true, \'T_itemstatuses\', \'pst_\', \'pst_ID\' )' );

// Caches that are not meant to be loaded in total:
$BlogCache = & new BlogCache();
$FileCache = & new FileCache();
$ItemCache = & new ItemCache();
$LinkCache = & new LinkCache();
$UserCache = & new UserCache();


require_once $model_path.'sessions/_hitlog.funcs.php';     // referer logging
require_once dirname(__FILE__).'/_misc/_form.funcs.php';
require_once dirname(__FILE__).'/_misc/_form.class.php';
require_once $model_path.'items/_itemquery.class.php';
require_once dirname(__FILE__).'/_misc/ext/_swfcharts.php';
/**
 * Icon Legend
 */
require_once dirname(__FILE__).'/_misc/_iconlegend.class.php';
$IconLegend = new IconLegend();


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


/*
 * Login procedure: {{{
 */
if( !isset($login_required) )
{
	$login_required = false;
}


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

$Debuglog->add( 'login: '.var_export($login, true), 'login' );
$Debuglog->add( 'pass: '.( empty($pass) ? '' : 'not' ).' empty', 'login' );

// either 'login' (normal) or 'redirect_to_backoffice' may be set here. This also helps to display the login form again, if either login or pass were empty.
$login_action = $Request->param_arrayindex( 'login_action' );

if( ! empty($login_action) || (! empty($login) && ! empty($pass)) )
{ // User is trying to login right now
	$Debuglog->add( 'User is trying to log in.', 'login' );

	$login = strtolower(strip_tags(get_magic_quotes_gpc() ? stripslashes($login) : $login));
	$pass = strip_tags(get_magic_quotes_gpc() ? stripslashes($pass) : $pass);
	$pass_md5 = md5( $pass );

	header_nocache();
	// echo 'Trying to log in right now...';

	$Plugins->trigger_event( 'LoginAttempt', array( 'login' => $login, 'pass' => $pass, 'pass_md5' => $pass_md5 ) );

	if( $Messages->count('login_error') )
	{ // A plugin has thrown a login error..
		// Do nothing, the error will get displayed in the login form..
	}
	else
	// Check login and password
	if( ! user_pass_ok( $login, $pass_md5, true ) )
	{ // Login failed
		$Debuglog->add( 'user_pass_ok() returned false!', 'login' );

		// This will cause the login screen to "popup" (again)
		$Messages->add( T_('Wrong login/password.'), 'login_error' );
	}
	else
	{ // Login succeeded, set cookies
		$Debuglog->add( 'User successfully logged in with username and password...', 'login');
		// set the user from the login that succeeded
		$current_User = & $UserCache->get_by_login($login);
		// save the user for later hits
		$Session->set_User( $current_User );

		// Remove deprecated cookies:
		// We do not use $cookie_user / $cookie_pass (would be set in _obsolete092.php), because it
		//  does not harm really (cookies time out) and would allow to set arbitrary cookies through
		//  register_globals!
		setcookie( 'cookie'.$instance_name.'user', '', 200000000, $cookie_path, $cookie_domain );
		setcookie( 'cookie'.$instance_name.'pass', '', 200000000, $cookie_path, $cookie_domain );
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

	$Debuglog->add( 'Was already logged in... ['.$current_User->get('login').']', 'login' );
}
else
{ // The Session has no user or $login is given (and differs from current user), allow alternate authentication through Plugin:
	if( ($event_return = $Plugins->trigger_event_first_true( 'AlternateAuthentication' ))
	    && $Session->has_User()  # the plugin should have attached the user to $Session
	)
	{
		$Debuglog->add( 'User has been authenticated through plugin #'.$event_return['plugin_ID'].' (AlternateAuthentication)', 'login' );
		$current_User = & $UserCache->get_by_ID( $Session->user_ID );
	}
	elseif( $login_required )
	{ /*
		 * ---------------------------------------------------------
		 * User was not logged in at all, but login is required
		 * ---------------------------------------------------------
		 */
		// echo ' NOT logged in...';
		$Debuglog->add( 'NOT logged in... (did not try)', 'login' );

		$Messages->add( T_('You must log in!'), 'login_error' );
	}
}
unset($pass);


// Check if the user needs to be validated, but is not yet:
if( ! empty($current_User)
		&& ! $current_User->validated
		&& $Settings->get('newusers_mustvalidate')
		&& $current_User->ID != 1       /* not admin user: this is BAD: it makes it look like the validation doesn't work */
		&& $current_User->group_ID != 1 /* not admin group */
		&& $Request->param('action', 'string', '') != 'logout' )
{
	if( $action != 'req_validatemail' && $action != 'validatemail' )
	{ // we're not in that action already:
		$action = 'req_validatemail'; // for login.php
		$Messages->add( sprintf( /* TRANS: %s gets replaced by the user's email address */ T_('You must validate your email address (%s) before you can log in.'), $current_User->dget('email') ), 'login_error' );
	}
}
else
{ // Trigger plugin event that allows the plugins to re-act on the login event:
	if( empty($current_User) )
	{
		$Plugins->trigger_event( 'AfterLoginAnonymousUser', array() );
	}
	else
	{
		$Plugins->trigger_event( 'AfterLoginRegisteredUser', array() );

		if( ! empty($login_action) )
		{ // We're coming from the Login form and need to redirect to the requested page:
			param( 'redirect_to', 'string', $baseurl );

			if( $login_action == 'redirect_to_backoffice' )
			{ // user pressed the "Log into backoffice!" button
				$redirect_to = $admin_url;
			}

			header_redirect( $redirect_to );
			exit();
		}
	}
}

// If there are "login_error" messages, they trigger the login form at the end of this file.

/* Login procedure }}} */


/**
 * The Sessions class
 */
require_once $model_path.'sessions/_sessions.class.php';
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
	/*
	 * User locale selection:
	 * TODO: this should get done before instantiating $current_User, because we already use T_() there...
	 */
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
 * @global string The INPUT/OUTPUT charset (from the locale MESSAGES). MAY be overriden in _blog_main.inc.php.
 */
$io_charset = locale_charset(false);


// Check and adjust $evo_charset if needed:
if( empty($evo_charset) )
{ // Internal encoding follows INPUT/OUTPUT encoding:
	$evo_charset = $io_charset;
}
elseif( $evo_charset != $io_charset )
{ // we have to convert for I/O, which requires mbstrings extension
	if( ! function_exists('mb_convert_encoding') )
	{
		$Debuglog->add( '$evo_charset differs from $io_charset, but mbstrings does not seem to be installed.', array('errors','locale') );
		$evo_charset = $io_charset; // we cannot convert I/O to internal charset
	}
	else
	{ // check if the encodings are supported:
		if( function_exists('mb_list_encodings') ) // PHP5
		{
			$mb_encodings = mb_list_encodings();
		}
		else
		{
			$mb_encodings = NULL;
		}

		if( isset($mb_encodings) && ! in_array( strtoupper($io_charset), $mb_encodings ) )
		{
			$Debuglog->add( 'Cannot I/O convert because I/O charset ['.$io_charset.'] is not in mb_list_encodings()!', array('errors','locale') );
			$evo_charset = $io_charset;
		}
		elseif( isset($mb_encodings) && ! in_array( strtoupper($evo_charset), $mb_encodings ) )
		{
			$Debuglog->add( 'Cannot I/O convert because $evo_charset='.$evo_charset.' is not in mb_list_encodings()!', array('errors','locale') );
			$evo_charset = $io_charset;
		}
		else
		{
			mb_http_output( $io_charset );
			ob_start( 'mb_output_handler' ); // NOTE: this will send a Content-Type header by itself for "text/..."
			$mb_output_handler_started = true; // remember it for _blog_main.inc.php
		}
		unset($mb_encodings);
	}
}

// Tell mbstrings what the internal encoding is:
if( function_exists('mb_internal_encoding') )
{
	mb_internal_encoding( $evo_charset );
}

// Set encoding for MySQL connection:
$DB->set_connection_charset( $evo_charset, true );

$Debuglog->add( 'evo_charset (_main.inc.php): '.$evo_charset, 'locale' );
$Debuglog->add( 'io_charset (_main.inc.php): '.$io_charset, 'locale' );


/**
 * Load the icons
 *
 * Note: we can't do this earlier because need the users locale set there ({@link T_()})
 */
require_once $conf_path.'_icons.php';


// Display login errors (and form). This uses $io_charset, so it's at the end.

if( $Messages->count( 'login_error' ) )
{
	header_nocache();

	require $htsrv_path.'login.php';
	exit();
}


$Timer->pause( 'main.inc');


/**
 * Load hacks file if it exists
 */
$Timer->resume( 'hacks.php' );
@include_once $conf_path.'hacks.php';
$Timer->pause( 'hacks.php' );


/*
 * $Log$
 * Revision 1.31  2006/06/19 21:06:55  blueyed
 * Moved ETag- and GZip-support into transport optimizer plugin.
 *
 * Revision 1.30  2006/06/19 20:59:37  fplanque
 * noone should die anonymously...
 *
 * Revision 1.29  2006/06/19 16:58:11  fplanque
 * minor
 *
 * Revision 1.26  2006/06/13 21:49:14  blueyed
 * Merged from 1.8 branch
 *
 * Revision 1.25  2006/06/01 19:00:07  fplanque
 * no message
 *
 * Revision 1.24  2006/05/28 22:36:47  blueyed
 * Abstracted DB connect into single file.
 *
 * Revision 1.23  2006/05/19 17:03:58  blueyed
 * locale activation fix from v-1-8, abstraction of setting DB connection charset
 *
 * Revision 1.22  2006/05/14 17:59:59  blueyed
 * "try/catch" SET NAMES (Thanks, bodo)
 *
 * Revision 1.21  2006/05/12 22:46:23  blueyed
 *
 * Revision 1.19  2006/05/04 10:18:41  blueyed
 * Added Session property to skip page content caching event.
 *
 * Revision 1.18  2006/05/03 01:53:42  blueyed
 * Encode subject in mails correctly (if mbstrings is available)
 *
 * Revision 1.17  2006/05/02 05:46:08  blueyed
 * fix
 *
 * Revision 1.16  2006/04/29 01:24:04  blueyed
 * More decent charset support;
 * unresolved issues include:
 *  - front office still forces the blog's locale/charset!
 *  - if there's content in utf8, it cannot get displayed with an I/O charset of latin1
 *
 * Revision 1.15  2006/04/24 20:52:30  fplanque
 * no message
 *
 * Revision 1.14  2006/04/22 02:36:38  blueyed
 * Validate users on registration through email link (+cleanup around it)
 *
 * Revision 1.13  2006/04/21 17:05:08  blueyed
 * cleanup
 *
 * Revision 1.12  2006/04/20 22:24:07  blueyed
 * plugin hooks cleanup
 *
 * Revision 1.11  2006/04/19 20:13:48  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.9  2006/04/18 19:28:34  fplanque
 * mambo idea
 *
 * Revision 1.8  2006/04/18 14:18:03  fplanque
 * security fix
 *
 * Revision 1.7  2006/04/14 19:25:31  fplanque
 * evocore merge with work app
 *
 * Revision 1.6  2006/04/06 17:21:34  blueyed
 * Fixed login procedure for https-admin_url: the login form now POSTs to itself and we redirect to the target url after successful login
 *
 * Revision 1.5  2006/03/24 19:40:49  blueyed
 * Only use absolute URLs if necessary because of used <base/> tag. Added base_tag()/skin_base_tag(); deprecated skinbase()
 *
 * Revision 1.4  2006/03/12 23:08:53  fplanque
 * doc cleanup
 *
 * Revision 1.3  2006/03/05 23:53:54  blueyed
 * If we have login_error Messages, do not login the user.
 *
 * Revision 1.2  2006/03/01 01:07:43  blueyed
 * Plugin(s) polishing
 *
 * Revision 1.1  2006/02/23 21:11:55  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.87  2006/02/13 20:20:09  fplanque
 * minor / cleanup
 *
 * Revision 1.86  2006/02/12 14:26:37  blueyed
 * Fixed and enhanced login form (log into backoffice)
 *
 * Revision 1.85  2006/02/10 22:08:07  fplanque
 * Various small fixes
 *
 * Revision 1.84  2006/02/03 21:58:05  fplanque
 * Too many merges, too little time. I can hardly keep up. I'll try to check/debug/fine tune next week...
 *
 * Revision 1.83  2006/02/01 21:36:51  blueyed
 * Trigger CacheObject event
 *
 * Revision 1.80  2006/01/26 23:08:36  blueyed
 * Plugins enhanced.
 *
 * Revision 1.79  2006/01/22 20:52:24  blueyed
 * Documented movin $Plugins/$Session init up.
 *
 * Revision 1.78  2006/01/12 21:55:13  blueyed
 * Fix
 *
 * Revision 1.76  2006/01/02 19:43:57  fplanque
 * just a little new year cleanup
 *
 * Revision 1.75  2005/12/30 20:13:40  fplanque
 * UI changes mostly (need to double check sync)
 *
 * Revision 1.73  2005/12/22 23:13:40  blueyed
 * Plugins' API changed and handling optimized
 *
 * Revision 1.72  2005/12/14 19:36:16  fplanque
 * Enhanced file management
 *
 * Revision 1.71  2005/12/12 19:21:22  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.70  2005/11/25 15:02:00  blueyed
 * $GroupCache: tell him about the name field. This allows to use get_by_name().
 *
 * Revision 1.68  2005/11/24 00:28:18  blueyed
 * Include _vars.inc.php before _conf_error.inc.php!
 *
 * Revision 1.67  2005/11/18 22:28:30  blueyed
 * Fix case for FileCache
 *
 * Revision 1.66  2005/11/14 18:23:13  blueyed
 * Remove experimental memcache support.
 *
 * Revision 1.65  2005/11/09 02:54:42  blueyed
 * Moved inclusion of _file.funcs.php to _misc.funcs.php, because at least bytesreable() gets used in debug_info()
 *
 * Revision 1.64  2005/11/08 15:21:55  blueyed
 * Fix $Debuglog-init when !$debug. We create it now first of class Log and after loading the config it gets re-instantiated of class Log_noop when !$debug. This allows to use $Debuglog during config loading with $debug enabled.
 *
 * Revision 1.63  2005/11/07 18:34:38  blueyed
 * Added class Log_noop, a no-operation implementation of class Log, which gets used if $debug is false.
 *
 * Revision 1.62  2005/11/07 03:27:42  blueyed
 * Use $Session to display a note about already logged in User (because $current_User does not get auto-set if 'login' param is given)
 *
 * Revision 1.61  2005/11/07 03:10:57  blueyed
 * Allow $login to override already logged in user
 *
 * Revision 1.60  2005/11/03 18:23:44  fplanque
 * minor
 *
 * Revision 1.59  2005/11/02 20:11:19  fplanque
 * "containing entropy"
 *
 * Revision 1.58  2005/10/31 23:40:47  blueyed
 * Remove deprecated user/pass cookies
 *
 * Revision 1.57  2005/10/31 06:46:08  blueyed
 * Fix Debuglog for login procedure
 *
 * Revision 1.56  2005/10/31 06:13:03  blueyed
 * Finally merged my work on $Session in.
 *
 * Revision 1.55  2005/10/27 15:25:03  fplanque
 * Normalization; doc; comments.
 *
 * Revision 1.54  2005/10/26 22:42:38  mfollett
 * Modified user retrieval process from using user cookie and password cookie to using session key and session ID to retrieve user information from the sessions table
 *
 * Revision 1.53  2005/10/13 22:17:30  blueyed
 * Moved include of _misc.funcs.inc.php to _main.inc.php
 *
 * Revision 1.52  2005/10/11 19:28:57  blueyed
 * Added decent error message if tables do not exist yet (not installed).
 *
 */
?>
