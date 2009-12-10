<?php
/**
 * This file includes advanced settings for the evoCore framework.
 *
 * Please NOTE: You should not comment variables out to prevent
 * URL overrides.
 *
 * @package conf
 *
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * No Translation. Does nothing.
 *
 * Nevertheless, the string will be extracted by the gettext tools
 */
function NT_( $string )
{
	return $string;
}

/**
 * Display debugging informations?
 *
 * 0 = no
 * 1 = yes
 * 2 = yes and potentially die() to display debug info (needed before redirects, e-g message_send.php)
 * 'pwd' = require password
 *
 * @global integer
 */
$debug = 'pwd';

/**
 * When $debug is 'pwd' and you set a /password/ below,
 * you can turn on debugging at any time by adding ?debug=1&debug_pwd=YOUR_PASSWORD to your url.
 * You can turn off by adding just ?debug
 *
 * @var string
 */
$debug_pwd = '';


// Most of the time you'll want to see all errors, including notices:
// b2evo should run notice free! (plugins too!)
if( ! defined( 'E_DEPRECATED' ) )
{
	error_reporting( E_ALL );
}
else
{	// Hopefully temporary fix for PHP >= v5.3 (Assigning the return value of new by reference is deprecated)
	// Tblue> This doesn't work properly on PHP 5.3... For some reason, you have to set error_reporting to
	// the desired value in php.ini -- error_reporting() and even ini_set() fail.
	error_reporting( E_ALL & ~E_DEPRECATED );
}

// To help debugging severe errors, you'll probably want PHP to display the errors on screen.
// In this case, uncomment the following line:
// ini_set( 'display_errors', 'on' );

// If you get blank pages, PHP may be crashing because it doesn't have enough memory.
// The default is 8 MB (in PHP < 5.2) and 128 MB (in PHP > 5.2)
// Try uncommmenting the following line:
// ini_set( 'memory_limit', '32M' );


/**
 * Log application errors through {@link error_log() PHP's logging facilities}?
 *
 * This means that they will get logged according to PHP's error_log configuration directive.
 *
 * Experimental! This may be changed to use regular files instead/optionally.
 *
 * @todo Provide logging into normal file instead (more useful for backtraces/multiline error messages)
 *
 * @global integer 0: off; 1: log errors; 2: include function backtraces (Default: 1)
 */
$log_app_errors = 1;


/**
 * Thumbnail size definitions.
 *
 * NOTE: this gets used for general resizing, too. E.g. in the coll_avatar_Widget.
 *
 * type, width, height, quality
 */
$thumbnail_sizes = array(
			'fit-720x500' => array( 'fit', 720, 500, 90 ),
			'fit-640x480' => array( 'fit', 640, 480, 90 ),
			'fit-520x390' => array( 'fit', 520, 390, 90 ),
			'fit-400x320' => array( 'fit', 400, 320, 85 ),
			'fit-320x320' => array( 'fit', 320, 320, 85 ),
			'fit-160x160' => array( 'fit', 160, 160, 80 ),
			'fit-160x120' => array( 'fit', 160, 120, 80 ),
			'fit-80x80' => array( 'fit', 80, 80, 80 ),
			'crop-80x80' => array( 'crop', 80, 80, 85 ),
			'crop-64x64' => array( 'crop', 64, 64, 85 ),
			'crop-48x48' => array( 'crop', 48, 48, 85 ),
			'crop-32x32' => array( 'crop', 32, 32, 85 ),
			'crop-15x15' => array( 'crop', 15, 15, 85 ),
	);


/**
 * Demo mode
 *  - Do not allow update of files in the file manager
 *  - Do not allow changes to the 'demouser' and 'admin' account/group
 *  - Blog media directories can only be configured to be inside of {@link $media_path}
 * @global boolean Default: false
 */
$demo_mode = false;


/**
 * URL of the Home link at the top left.
 *
 * By default this is the base url. And unless you do a complex installation, there is no need to change this.
 */
$home_url = $baseurl;


/**
 * Comments: Set this to 1 to require e-mail and name, or 0 to allow comments
 * without e-mail/name.
 * @global boolean $require_name_email
 */
$require_name_email = 1;

/**
 * Minimum interval (in seconds) between consecutive comments from same IP.
 * @global int $minimum_comment_interval
 */
$minimum_comment_interval = 30;


/**
 * Check antispam blacklist for private messages.
 *
 * Do you want to check the antispam blocklist when a message form is submitted?
 *
 * @global boolean $antispam_on_message_form
 */
$antispam_on_message_form = 1;


// Get hostname out of baseurl
// YOU SHOULD NOT EDIT THIS unless you know what you're doing
if( preg_match( '#^(https?://(.+?)(:(.+?))?)(/.*)$#', $baseurl, $matches ) )
{
	$baseurlroot = $matches[1]; // no ending slash!
	// echo "baseurlroot=$baseurlroot <br />";
	$basehost = $matches[2];
	// echo "basehost=$basehost <br />";
	$baseport =  $matches[4];
	// echo "baseport=$baseport <br />";
	$basesubpath =  $matches[5];
	// echo "basesubpath=$basesubpath <br />";
}
else
{
	die( 'Your baseurl ('.$baseurl.') set in _basic_config.php seems invalid. You probably missed the "http://" prefix or the trailing slash. Please correct that.' );
}


/**
 * Base domain of b2evolution.
 *
 * By default we try to extract it automagically from $basehost (itself extracted from $abaseurl)
 * But you may need to adjust this manually.
 *
 * @todo does anyone have a clean way of handling stuff like .co.uk ?
 *
 * @global string
 */
$basedomain = preg_replace( '/^( .* \. )? (.+? \. .+? )$/xi', '$2', $basehost );


/**
 * Short name of this system (will be used for cookies and notification emails).
 *
 * Change this only if you install mutliple b2evolutions on the same website.
 *
 * WARNING: don't play with this or you'll have tons of cookies sent away and your
 * readers surely will complain about it!
 *
 * You can change the notification email address alone a few lines below.
 *
 * @todo generate a random instance name at install and have it saved in the global params in the DB
 *
 * @global string Default: 'b2evo'
 */
$instance_name = 'b2evo'; // MUST BE A SINGLE WORD! NO SPACES!!


/**
 * Default email address for sending notifications (comments, trackbacks,
 * user registrations...).
 *
 * Set a custom address like this:
 * <code>$notify_from = 'b2evolution@your_server.com';</code>
 *
 * Alternatively you can use this automated address generation (which removes "www." from
 * the beginning of $basehost):
 * <code>$notify_from = $instance_name.'@'.preg_replace( '/^www\./i', '', $basehost );</code>
 *
 * @global string Default: $instance_name.'@'.$basehost;
 */
$notify_from = $instance_name.'@'.preg_replace( '/^www\./i', '', $basehost );


// ** DB options **

/**
 * Show MySQL errors? (default: true)
 *
 * This is recommended on production environments.
 */
$db_config['show_errors'] = true;


/**
 * Halt on MySQL errors? (default: true)
 *
 * Setting this to false is not recommended,
 */
$db_config['halt_on_error'] = true;


/**
 * CREATE TABLE options.
 *
 * DO NOT USE unless you know what you're doing -- For most options, we want to work on a table by table basis.
 */
$db_config['table_options'] = ''; 	// Low ranking MySQL hosting compatibility Default


/**
 * Use transactions in DB?
 *
 * You need to use InnoDB in order to enable this.
 */
$db_config['use_transactions'] = true;


/**
 * Display elements that are different on each request (Page processing time, ..)
 *
 * Set this to true to prevent displaying minor changing elements (like time) in order not to have artificial content changes
 *
 * @global boolean Default: false
 */
$obhandler_debug = false;


// ** Cookies **

/**
 * This is the path that will be associated to cookies.
 *
 * That means cookies set by this b2evo install won't be seen outside of this path on the domain below.
 *
 * @global string Default: preg_replace( '#https?://[^/]+#', '', $baseurl )
 */
$cookie_path = preg_replace( '#https?://[^/]+#', '', $baseurl );

/**
 * Cookie domain.
 *
 * That means cookies set by this b2evo install won't be seen outside of this domain.
 *
 * We'll take {@link $basehost} by default (the leading dot includes subdomains), but
 * when there's no dot in it, at least Firefox will not set the cookie. The best
 * example for having no dot in the host name is 'localhost', but it's the case for
 * host names in an intranet also.
 *
 * Note: ".domain.com" cookies will be sent to sub.domain.com too.
 * But, see http://www.faqs.org/rfcs/rfc2965:
 *	"If multiple cookies satisfy the criteria above, they are ordered in
 *	the Cookie header such that those with more specific Path attributes
 *	precede those with less specific.  Ordering with respect to other
 *	attributes (e.g., Domain) is unspecified."
 *
 * @global string Default: ( strpos($basehost, '.') ) ? '.'. $basehost : '';
 */
if( strpos($basehost, '.') === false )
{	// localhost or windows machine name:
	$cookie_domain = '';
}
elseif( preg_match( '~^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$~i', $basehost ) )
{	// Use the basehost as it is:
	$cookie_domain = $basehost;
}
else
{
	$cookie_domain = preg_replace( '/^(www\. )? (.+)$/xi', '.$2', $basehost );

	// When hosting multiple domains (not just subdomains) on a single instance of b2evo,
	// you may want to try this:
	// $cookie_domain = '.'.$_SERVER['HTTP_HOST'];
	// or this: -- Have a cookie domain of 2 levels only, base on current basehost.
	// $cookie_domain = preg_replace( '/^( .* \. )? (.+? \. .+? )$/xi', '.$2', $basehost );
	// fp> pb with domains like .co.uk !?
}

// echo $cookie_domain;

/**#@+
 * Names for cookies.
 */
// The following remember the comment meta data for non registered users:
$cookie_name    = 'cookie'.$instance_name.'name';
$cookie_email   = 'cookie'.$instance_name.'email';
$cookie_url     = 'cookie'.$instance_name.'url';
// The following handles the session:
$cookie_session = 'cookie'.$instance_name.'session';
/**#@-*/

/**
 * Expiration for comment meta data cookies.
 *
 * Note: user sessions use different settings (config in admin)
 *
 * Value in seconds, set this to 0 if you wish to use non permanent cookies (erased when browser is closed).
 * Default: time() + 31536000 (one year from now)
 *
 * @global int $cookie_expires
 */
$cookie_expires = time() + 31536000;

/**
 * Expired-time used to erase comment meta data cookies.
 *
 * Note: user sessions use different settings (config in admin)
 *
 * Default: time() - 86400 (24 hours ago)
 *
 * @global int $cookie_expired
 */
$cookie_expired = time() - 86400;


// ** Location of the b2evolution subdirectories **

/*
	- You should only move these around if you really need to.
	- You should keep everything as subdirectories of the base folder
		($baseurl which is set in _basic_config.php, default is the /blogs/ folder)
	- Remember you can set the baseurl to your website root (-> _basic_config.php).

	NOTE: All paths must have a trailing slash!

	Example of a possible setting:
		$conf_subdir = 'settings/b2evo/';   // Subdirectory relative to base
		$conf_subdir = '../../';            // Relative path to go back to base
*/
/**
 * Location of the configuration files.
 *
 * Note: This folder NEEDS to by accessible by PHP only.
 *
 * @global string $conf_subdir
 */
$conf_subdir = 'conf/';                  // Subdirectory relative to base
$conf_path = str_replace( '\\', '/', dirname(__FILE__) ).'/';

/**
 * @global string Path of the base.
 *                fp> made [i]nsensitive to case because of Windows URL oddities)
 */
$basepath = preg_replace( '#/'.$conf_subdir.'$#i', '', $conf_path ).'/';
// echo '<br/>basepath='.$basepath;

/**
 * Location of the include folder.
 *
 * Note: This folder NEEDS to by accessible by PHP only.
 *
 * @global string $inc_subdir
 */
$inc_subdir = 'inc/';   		             	// Subdirectory relative to base
$inc_path = $basepath.$inc_subdir; 		   	// You should not need to change this
$misc_inc_path = $inc_path.'_misc/';	   	// You should not need to change this

/**
 * Location of the HTml SeRVices folder.
 *
 * Note: This folder NEEDS to by accessible through HTTP.
 *
 * @global string $htsrv_subdir
 */
$htsrv_subdir = 'htsrv/';                // Subdirectory relative to base
$htsrv_path = $basepath.$htsrv_subdir;   // You should not need to change this
$htsrv_url = $baseurl.$htsrv_subdir;     // You should not need to change this

/**
 * Sensitivee URL to the htsrv folder.
 *
 * Set this separately (based on {@link $htsrv_url}), if you want to use
 * SSL for login, registration and profile updates (where passwords are
 * involved), but not for the whole htsrv scripts.
 *
 * @global string
 */
$htsrv_url_sensitive = $htsrv_url;

/**
 * Location of the XML SeRVices folder.
 * @global string $xmlsrv_subdir
 */
$xmlsrv_subdir = 'xmlsrv/';              // Subdirectory relative to base
$xmlsrv_url = $baseurl.$xmlsrv_subdir;   // You should not need to change this

/**
 * Location of the RSC folder.
 *
 * Note: This folder NEEDS to by accessible through HTTP.
 *
 * @global string $rsc_subdir
 */
$rsc_subdir = 'rsc/';                    // Subdirectory relative to base
$rsc_path = $basepath.$rsc_subdir;       // You should not need to change this
$rsc_url = $baseurl.$rsc_subdir;         // You should not need to change this

/**
 * Location of the skins folder.
 * @global string $skins_subdir
 */
$skins_subdir = 'skins/';                // Subdirectory relative to base
$skins_path = $basepath.$skins_subdir;   // You should not need to change this
$skins_url = $baseurl.$skins_subdir;     // You should not need to change this


/**
 * Location of the admin interface dispatcher
 */
$dispatcher = 'admin.php'; // DEPRECATED
$admin_url = $baseurl.$dispatcher;


/**
 * Location of the admin skins folder.
 *
 * Note: This folder NEEDS to by accessible by both PHP AND through HTTP.
 *
 * @global string $adminskins_subdir
 */
$adminskins_subdir = 'skins_adm/';         // Subdirectory relative to ADMIN
$adminskins_path = $basepath.$adminskins_subdir; // You should not need to change this
$adminskins_url = $baseurl.$adminskins_subdir;   // You should not need to change this

/**
 * Location of the locales folder.
 *
 * Note: This folder NEEDS to by accessible by PHP AND MAY NEED to be accessible through HTTP.
 * Exact requirements depend on future uses like localized icons.
 *
 * @global string $locales_subdir
 */
$locales_subdir = 'locales/';            // Subdirectory relative to base
$locales_path = $basepath.$locales_subdir;  // You should not need to change this

/**
 * Location of the plugins.
 *
 * Note: This folder NEEDS to by accessible by PHP AND MAY NEED to be accessible through HTTP.
 * Exact requirements depend on installed plugins.
 *
 * @global string $plugins_subdir
 */
$plugins_subdir = 'plugins/';            // Subdirectory relative to base
$plugins_path = $basepath.$plugins_subdir;  // You should not need to change this
$plugins_url = $baseurl.$plugins_subdir;    // You should not need to change this

/**
 * Location of the cron folder.
 *
 * Note: Depebding on how you will set up cron execution, this folder may or may not NEED to be accessible by PHP through HTTP.
 *
 * @global string $cron_subdir
 */
$cron_subdir = 'cron/';   		             	// Subdirectory relative to base
$cron_url = $baseurl.$cron_subdir;    // You should not need to change this

/**
 * Location of the install folder.
 * @global string $install_subdir
 */
$install_subdir = 'install/';            	  // Subdirectory relative to base
$install_path = $basepath.$install_subdir;  // You should not need to change this

/**
 * Location of the rendered page cache folder.
 *
 * Note: This folder does NOT NEED to be accessible through HTTP.
 * This folder MUST be writable by PHP.
 *
 * @global string $cache_subdir
 */
$cache_subdir = 'cache/';                // Subdirectory relative to base
$cache_path = $basepath.$cache_subdir;   // You should not need to change this


/**
 * Location of the root media folder.
 *
 * Note: This folder MAY or MAY NOT NEED to be accessible by PHP AND/OR through HTTP.
 * Exact requirements depend on $public_access_to_media .
 *
 * @global string $media_subdir
 */
$media_subdir = 'media/';                // Subdirectory relative to base
$media_path = $basepath.$media_subdir;   // You should not need to change this
$media_url = $baseurl.$media_subdir;     // You should not need to change this


/**
 * Location of the backup folder.
 *
 * Note: This folder does NOT NEED to be accessible through HTTP.
 * This folder MUST be writable by PHP.
 *
 * @global string $backup_subdir
 */
$backup_subdir = '_backup/';				// Subdirectory relative to base
$backup_path = $basepath.$backup_subdir;	// You should not need to change this


/**
 * Location of the upgrade folder.
 *
 * Note: This folder does NOT NEED to be accessible through HTTP.
 * This folder MUST be writable by PHP.
 *
 * @global string $upgrade_subdir
 */
$upgrade_subdir = '_upgrade/';              // Subdirectory relative to base
$upgrade_path = $basepath.$upgrade_subdir;  // You should not need to change this


// Define default avatar image URL
// fp> TODO: do not use a setting for this.
// fp> put the file into the shared files directory with the other sample "admin" avatars. That way it is very easy to replace with another default.
// fp> PS: I like the ? image ;)
$default_avatar = $rsc_url.'img/default_avatar.jpg';


/**
 * Do you want to allow public access to the media dir?
 *
 * WARNING: If you set this to false, evocore will use /htsrv/getfile.php as a stub
 * to access files and getfile.php will check the User permisssion to view files.
 * HOWEVER this will not prevent users from hitting directly into the media folder
 * with their web browser. You still need to restrict access to the media folder
 * from your webserver.
 *
 * @global boolean
 */
$public_access_to_media = true;

/**
 * File extensions that the admin will not be able to enable in the Settings
 */
$force_upload_forbiddenext = array( 'cgi', 'exe', 'htaccess', 'htpasswd', 'php', 'php3', 'php4', 'php5', 'php6', 'phtml', 'pl', 'vbs' );

/**
 * Admin can configure max file upload size, but he won't be able to set it higher than this "max max" value.
 */
$upload_maxmaxkb = 10000;

/**
 * The admin can configure the regexp for valid file names in the Settings interface
 * However if the following values are set to non empty, the admin will not be able to customize these values.
 */
$force_regexp_filename = '';
$force_regexp_dirname = '';


/**
 * XMLRPC logging. Set this to 1 to log XMLRPC calls received by this server (into /xmlsrv/xmlrpc.log).
 *
 * Default: 0
 *
 * @global int $debug_xmlrpc_logging
 */
$debug_xmlrpc_logging = 0;


/**
 * Seconds after which a scheduled task is considered to be timed out.
 */
$cron_timeout_delay = 1800; // 30 minutes

/**
 * Enable a workaround to allow accessing posts with URL titles ending with
 * a dash (workaround for old bug).
 *
 * In b2evolution v2.4.5 new tag URLs were introduced: You could choose
 * to have tag URLs ending with a dash. This lead to problems with post
 * URL titles accidentially ending with a dash (today, URL titles cannot
 * end with a dash anymore): Instead of displaying the post, the post
 * title was handled as a tag name. When this setting is enabled, all tag
 * names which are exactly 40 chars long and end with a dash are handled
 * in the following way:
 * Try to find a post with the given tag name as the URL title. If there
 * is a matching post, display it; otherwise, display the normal tag page.
 *
 * Note: If you use a 39 chars-long tag name, have an URL title which is
 * the same as the tag *but* additionally has a dash at the end and you
 * use the dash as a tag URL "marker", you won't be able to access either
 * the post or the tag page, depending on the value of this setting.
 *
 * @global boolean $tags_dash_fix
 *
 * @internal Tblue> We perhaps should notify the user if we detect bogus
 *                  post URLs (check on upgrade?) and recommend enabling
 *                  this setting.
 */
$tags_dash_fix = 0;


/**
 * Use hacks file (DEPRECATED) -- see /inc/_main.inc.php
 */
$use_hacks = false;



// ----- CHANGE THE FOLLOWING SETTINGS ONLY IF YOU KNOW WHAT YOU'RE DOING! -----
$evonetsrv_host = 'rpc.b2evolution.net';
$evonetsrv_port = 80;
$evonetsrv_uri = '/evonetsrv/xmlrpc.php';

$antispamsrv_host = 'antispam.b2evolution.net';
$antispamsrv_port = 80;
$antispamsrv_uri = '/evonetsrv/xmlrpc.php';
?>
