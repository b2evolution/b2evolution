<?php
/**
 * This file includes advanced settings for the evoCore framework.
 *
 * Please NOTE: You should not comment variables out to prevent
 * URL overrides.
 *
 * @package conf
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

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
$debug_jslog = 'pwd';

/**
 * When $debug is 'pwd' and you set a /password/ below,
 * you can turn on debugging at any time by adding ?debug=YOUR_PASSWORD to your url.
 * You can turn off by adding just ?debug
 *
 * You can ALSO turn on debugging of JavaScript(AJAX Requests) by adding ?jslog=YOUR_PASSWORD to your url.
 * You can turn off by adding just ?jslog
 *
 * @var string
 */
$debug_pwd = '';

// Most of the time you'll want to see all errors, including notices:
// b2evo should run without any notices! (plugins too!)
if( version_compare( phpversion(), '5.3', '>=' ) )
{	// sam2kb> Disable E_STRICT messages on PHP > 5.3, there are numerous E_STRICT warnings displayed throughout the app
	error_reporting( E_ALL & ~E_STRICT );
}
else
{
	error_reporting( E_ALL );
}
/**
 * Do we want to display errors, even when not in debug mode?
 *
 * You are welcome to change/override this if you know what you're doing.
 * This is turned on by default so that newbies can quote meaningful error messages in the forums.
 */
$display_errors_on_production = true;


/**
 * Do you want to display the "Dev" menu in the evobar?
 * This allows to display the dev menu without necessarily enabling debugging.
 * This is useful for skin development on local machines.
 *
 * @var boolean - set to 1 to display the dev menu in the evobar.
 */
$dev_menu = 0;


// If you get blank pages or missing thumbnail images, PHP may be crashing because it doesn't have enough memory.
// The default is 8 MB (in PHP < 5.2) and 128 MB (in PHP > 5.2)
// Try uncommmenting the following line:
// ini_set( 'memory_limit', '128M' );


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
 * Allows to force a timezone if PHP>=5.1
 * See: http://b2evolution.net/man/date_default_timezone-forcing-a-timezone
 */
$date_default_timezone = '';

/**
 * Thumbnail size definitions.
 *
 * NOTE: this gets used for general resizing, too. E.g. in the coll_avatar_Widget.
 *
 * type, width, height, quality, percent of blur effect
 */
$thumbnail_sizes = array(
			'fit-1280x720' => array( 'fit', 1280, 720, 85 ),
			'fit-720x500' => array( 'fit', 720, 500, 90 ),
			'fit-640x480' => array( 'fit', 640, 480, 90 ),
			'fit-520x390' => array( 'fit', 520, 390, 90 ),
			'fit-400x320' => array( 'fit', 400, 320, 85 ),
			'fit-320x320' => array( 'fit', 320, 320, 85 ),
			'fit-256x256' => array( 'fit', 256, 256, 85 ),
			'fit-192x192' => array( 'fit', 192, 192, 85 ),
			'fit-160x160' => array( 'fit', 160, 160, 80 ),
			'fit-160x160-blur-13' => array( 'fit', 160, 160, 80, 13 ),
			'fit-160x160-blur-18' => array( 'fit', 160, 160, 80, 18 ),
			'fit-160x120' => array( 'fit', 160, 120, 80 ),
			'fit-128x128' => array( 'fit', 128, 128, 80 ),
			'fit-80x80' => array( 'fit', 80, 80, 80 ),
			'crop-480x320' => array( 'crop', 480, 320, 90 ),
			'crop-256x256' => array( 'crop', 256, 256, 85 ),
			'crop-192x192' => array( 'crop', 192, 192, 85 ),
			'crop-128x128' => array( 'crop', 128, 128, 85 ),
			'crop-80x80' => array( 'crop', 80, 80, 85 ),
			'crop-64x64' => array( 'crop', 64, 64, 85 ),
			'crop-48x48' => array( 'crop', 48, 48, 85 ),
			'crop-32x32' => array( 'crop', 32, 32, 85 ),
			'crop-15x15' => array( 'crop', 15, 15, 85 ),
			'crop-top-320x320-blur-8' => array( 'crop-top', 320, 320, 80, 8 ),
			'crop-top-320x320' => array( 'crop-top', 320, 320, 85 ),
			'crop-top-160x160' => array( 'crop-top', 160, 160, 85 ),
			'crop-top-80x80' => array( 'crop-top', 80, 80, 85 ),
			'crop-top-64x64' => array( 'crop-top', 64, 64, 85 ),
			'crop-top-48x48' => array( 'crop-top', 48, 48, 85 ),
			'crop-top-32x32' => array( 'crop-top', 32, 32, 85 ),
			'crop-top-15x15' => array( 'crop-top', 15, 15, 85 ),
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
 * If enabled, this will create more demo contents and enable more features during install.
 * This may result in an overloaded/bloated blog.
 *
 * @global boolean
 */
$test_install_all_features = false;


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


/**
 * By default images get copied into b2evo cache without resampling if they are smaller
 * than requested thumbnails.
 *
 * Althought, if you want to use the BeforeThumbCreate event (Watermark plugin),
 * this should be set to 'true' in order to process smaller images.
 *
 * @global boolean Default: false
 */
$resample_all_images = false;


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
 * By default we try to extract it automagically from $basehost (itself extracted from $baseurl)
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
 * @global string Default: 'b2evo'
 */
$instance_name = 'b2evo'; // MUST BE A SINGLE WORD! NO SPACES!!


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
{	// The basehost is an IP address, use the basehost as it is:
	$cookie_domain = $basehost;
}
else
{	// Keep the part of the basehost after the www. :
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
$cookie_session = str_replace( '.', '_', 'session_'.$instance_name.'_'.$cookie_domain );
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

/**
 * Crumb expiration time
 *
 * Default: 2 hours
 *
 * @global int $crumb_expires
 */
$crumb_expires = 7200;

/**
 * Page cache expiration time
 * How old can a cached object get before we consider it outdated
 *
 * Default: 15 minutes
 *
 * @global int $pagecache_max_age
 */
$pagecache_max_age = 900;


/**
 * Dummy field names to obfuscate spamboots
 *
 * We use funky field names to defeat the most basic spambots in the front office public forms
 */
$dummy_fields = array(
	'login' => 'x',
	'pwd' => 'q',
	'pass1' => 'm',
	'pass2' => 'c',
	'email' => 'u',
	'name' => 'i',
	'url' => 'h',
	'subject' => 'd',
	'content' => 'g'
);


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
 * Sensitive URL to the htsrv folder.
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
$rsc_uri = $basesubpath.$rsc_subdir;

/**
 * Location of the skins folder.
 * @global string $skins_subdir
 */
$skins_subdir = 'skins/';                // Subdirectory relative to base
$skins_path = $basepath.$skins_subdir;   // You should not need to change this
$skins_url = $baseurl.$skins_subdir;     // You should not need to change this

/**
 * Location of the site skins folder.
 * @global string $siteskins_subdir
 */
$siteskins_subdir = 'skins_site/';       		    // Subdirectory relative to base
$siteskins_path = $basepath.$siteskins_subdir;  // You should not need to change this
$siteskins_url = $baseurl.$siteskins_subdir;    // You should not need to change this

/**
 * Location of the email skins folder.
 * @global string $emailskins_subdir
 */
$emailskins_subdir = 'skins_email/';               // Subdirectory relative to base
$emailskins_path = $basepath.$emailskins_subdir;   // You should not need to change this
$emailskins_url = $baseurl.$emailskins_subdir;     // You should not need to change this

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
$cache_subdir = '_cache/';             // Subdirectory relative to base
$cache_path = $basepath.$cache_subdir; // You should not need to change this

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
 * Do you want to stay in the current blog when you click on a post title or permalink,
 * even if the post main cat belongs to another blog?
 *
 * @global boolean
 */
$cross_post_nav_in_same_blog = true;


/**
 * File extensions that the admin will not be able to enable in the Settings
 */
$force_upload_forbiddenext = array( 'cgi', 'exe', 'htaccess', 'htpasswd', 'php', 'php3', 'php4', 'php5', 'php6', 'phtml', 'pl', 'vbs' );

/**
 * Admin can configure max file upload size, but he won't be able to set it higher than this "max max" value.
 */
$upload_maxmaxkb = 32000;

/**
 * The admin can configure the regexp for valid file names in the Settings interface
 * However if the following values are set to non empty, the admin will not be able to customize these values.
 */
$force_regexp_filename = '';
$force_regexp_dirname = '';

/**
 * The maximum length of a file name. On new uploads file names with more characters are not allowed.
 */
$filename_max_length = 64;

/**
 * The maximum length of a file absolute path. Creating folders/files with longer path then this value is not allowed.
 * Note: 247 is the max length of an absolute path what the php file operation functions can handle on windows.
 * On unix systems the file path length is not an issue, so there we can allow a higher value.
 * The OS independent max length is 767, because that is what b2evolution can handle correctly.
 */
$dirpath_max_length = ( ( ( strtoupper( substr( PHP_OS, 0, 3 ) ) ) === 'WIN' ) ? ( 247 - 35 /* the maximum additional path length because of the _evocache folder */ ) : 767 ) - $filename_max_length;


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
 * Password change request delay in seconds. Only one email can be requested for one login or email address in each x seconds defined below.
 */
$pwdchange_request_delay = 300; // 5 minutes


/**
 * Account activation reminder settings.
 * Each element of the array is given in seconds
 * Assume that the number of element in the array below is n then the following must be followed:
 * n must be greater then 1; n - 1 will be the max number of account activation reminder emails.
 * The first element of the array ( in position 0 ) shows the time in seconds when the firs reminder email must be sent after the new user was registered, or the user status was changed to new, deactivated or emailchanged status
 * Each element between the postion [1 -> (n - 1)) shows the time in seconds when the next reminder email must be sent after the previous one
 * The last element of the array shows when an account status will be set to 'failedactivation' if it was not activated after the last reminder email. This value must be the highest value of the array!
 *
 * E.g. $activate_account_reminder_config = array( 86400, 129600, 388800, 604800 ); = array( 1 day, 1.5 days, 4.5 days, 7 days )
 * At most 3 reminder will be sent, the first 1 day after the registration or deactivation, the seond in 1.5 days after the first one, and the third one after 2.5 days after the second one.
 * 7 days after the last reminder email the account status will be set to 'failedactivation' and no more reminder will be sent.
 */
$activate_account_reminder_config = array( 86400/* one day */, 129600/* 1.5 days */, 388800/* 4.5 days */, 604800/* 7 days */ );


/**
 * Account activation reminder threshold given in seconds.
 * A user may receive Account activation reminder if the account was created at least x ( = threshold value defined below ) seconds ago.
 */
$activate_account_reminder_threshold = 86400; // 24 hours


/**
 * Comment moderation reminder threshold given in seconds.
 * A moderator user may receive Comment moderation reminder if there are comments awaiting moderation which were created at least x ( = threshold value defined below ) seconds ago.
 */
$comment_moderation_reminder_threshold = 86400; // 24 hours


/**
 * Post moderation reminder threshold given in seconds.
 * A moderator user may receive Post moderation reminder if there are posts awaiting moderation which were created at least x ( = threshold value defined below ) seconds ago.
 */
$post_moderation_reminder_threshold = 86400; // 24 hours


/**
 * Unread private messages reminder threshold given in seconds.
 * A user may receive unread message reminder if it has unread private messages at least as old as this threshold value.
 */
$unread_messsage_reminder_threshold = 86400; // 24 hours


/**
 * Unread message reminder is sent in every y days in case when a user last logged in date is below x days.
 * The array below is in x => y format.
 * The values of this array must be ascendant.
 */
$unread_message_reminder_delay = array(
	10  => 3,  // less than 10 days -> 3 days spacing
	30  => 6,  // 10 to 30 days -> 6 days spacing
	90  => 15, // 30 to 90 days -> 15 days spacing
	180 => 30, // 90 to 180 days -> 30 days spacing
	365 => 60, // 180 to 365 days -> 60 days spacing
	730 => 120 // 365 to 730 days -> 120 days spacing
	// more => "The user has not logged in for x days, so we will not send him notifications any more".
);


/**
 * Cleanup scheduled jobs threshold given in days.
 * The scheduled jobs older than x ( = threshold value ) days will be removed
 */
$cleanup_jobs_threshold = 45;


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


/**
 * If user tries to login 10 times during X seconds we refuse login (even if password is correct)
 * If set to 0, then there is never a lockout
 */
$failed_logins_lockout = 600; // 10 minutes


/**
 * Most of the time, the best security practice is to NOT allow redirects from your current site to another domain.
 * That is, unless you specifically configured a redirected post.
 * If this doesn't work for you, you can change this security policy here.
 *
 * Possible values:
 *  - 'always' : Always allow redirects to a different domain
 *  - 'all_collections_and_redirected_posts' ( Default ): Allow redirects to all collection domains or redirect of posts with redirected status
 *  - 'only_redirected_posts' : Allow redirects to a different domain only in case of posts with redirected status
 *  - 'never' : Force redirects to the same domain in all of the cases, and never allow redirect to a different domain
 */
$allow_redirects_to_different_domain = 'all_collections_and_redirected_posts';


/**
 * Additional params you may want to pass to sendmail when sending emails
 * For setting the return-path, some Linux servers will require -r, others will require -f.
 * Allowed placeholders: $from-address$ , $return-address$
 *
 * @global string $sendmail_additional_params
 */
$sendmail_additional_params = '-r $return-address$';


/**
 * Would you like to use CDNs as definied in the array $library_cdn_urls below 
 * or do you prefer to load all files from the local source as defined in the array $library_local_urls below?
 *
 * @global boolean $use_cdns
 */
$use_cdns = false;		// Use false by default so b2evo works on intranets, local tests on laptops and in countries with firewalls...

/**
 * Which CDN do you want to use for loading common libraries?
 *
 * If you don't want to use a CDN and want to use the local version, comment out the line.
 * Each line starts with the js or css alias.
 * The first string is the production (minified URL), the second is the development URL (optional).
 * By default, only the most trusted CDNs are enabled while the other ones are commented out.
 */
$library_cdn_urls = array(
		'#jquery#' => array( '//code.jquery.com/jquery-1.11.1.min.js', '//code.jquery.com/jquery-1.11.1.js' ),
		// jqueryUI is commented out because b2evo uses only a subset... ?
		//'#jqueryUI#' => array( '//code.jquery.com/ui/1.10.4/jquery-ui.min.js', '//code.jquery.com/ui/1.10.4/jquery-ui.js' ),
		//'#jqueryUI_css#' => array( '//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.min.css', '//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css' ),
		'#bootstrap#' => array( '//netdna.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js', '//netdna.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.js' ),
		'#bootstrap_css#' => array( '//netdna.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css', '//netdna.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.css' ),
		'#bootstrap_theme_css#' => array( '//netdna.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap-theme.min.css', '//netdna.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap-theme.css' ),
		// The following are other possible public shared CDNs we are aware of
		// but it is not clear whether or not they are:
		// - Future proof (will they continue to serve old versions of the library in the future?)
		// - Secure (who guarantees the code is genuine?)
		//'#bootstrap_typeahead#' => array( '//cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.10.1/typeahead.bundle.min.js', '//cdnjs.cloudflare.com/ajax/libs/typeahead.js/0.10.1/typeahead.bundle.js' ),
		//'#easypiechart#' => array( '//cdnjs.cloudflare.com/ajax/libs/easy-pie-chart/2.1.1/jquery.easypiechart.min.js', '//cdnjs.cloudflare.com/ajax/libs/easy-pie-chart/2.1.1/jquery.easypiechart.js' ),
		//'#scrollto#' => array( '//cdnjs.cloudflare.com/ajax/libs/jquery-scrollTo/1.4.2/jquery.scrollTo.min.js' ),
		//'#touchswipe#' => array( '//cdn.jsdelivr.net/jquery.touchswipe/1.6.5/jquery.touchSwipe.min.js', '//cdn.jsdelivr.net/jquery.touchswipe/1.6.5/jquery.touchSwipe.js' ),
		/*'#jqplot#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/jquery.jqplot.min.js' ),
			'#jqplot_barRenderer#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/plugins/jqplot.barRenderer.min.js' ),
			'#jqplot_canvasAxisTickRenderer#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/plugins/jqplot.canvasAxisTickRenderer.min.js' ),
			'#jqplot_canvasTextRenderer#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/plugins/jqplot.canvasTextRenderer.min.js' ),
			'#jqplot_categoryAxisRenderer#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/plugins/jqplot.categoryAxisRenderer.min.js' ),
			'#jqplot_enhancedLegendRenderer#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/plugins/jqplot.enhancedLegendRenderer.min.js' ),
			'#jqplot_highlighter#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/plugins/jqplot.highlighter.min.js' ),
			'#jqplot_canvasOverlay#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/plugins/jqplot.canvasOverlay.min.js' ),
			'#jqplot_donutRenderer#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/plugins/jqplot.donutRenderer.min.js' ),
			'#jqplot_css#' => array( '//cdnjs.cloudflare.com/ajax/libs/jqPlot/1.0.8/jquery.jqplot.min.css' ),*/
		//'#tinymce#' => array( '//tinymce.cachefly.net/4.1/tinymce.min.js' ),
		//'#flowplayer#' => array( '//releases.flowplayer.org/5.4.4/flowplayer.min.js', '//releases.flowplayer.org/5.4.4/flowplayer.js' ),
		//'#mediaelement#' => array( '//cdnjs.cloudflare.com/ajax/libs/mediaelement/2.13.2/js/mediaelement-and-player.min.js', '//cdnjs.cloudflare.com/ajax/libs/mediaelement/2.13.2/js/mediaelement-and-player.js' ),
		//'#mediaelement_css#' => array( '//cdnjs.cloudflare.com/ajax/libs/mediaelement/2.13.2/css/mediaelementplayer.min.css', '//cdnjs.cloudflare.com/ajax/libs/mediaelement/2.13.2/css/mediaelementplayer.css' ),
		//'#videojs#' => array( 'http://vjs.zencdn.net/c/video.js' ),
		//'#videojs_css#' => array( 'http://vjs.zencdn.net/c/video-js.css' ),
	);

/**
 * The aliases for all local JS and CSS files that are used when CDN url is not defined in $library_cdn_urls
 *
 * Each line starts with the js or css alias.
 * The first string is the production (minified URL), the second is the development URL (optional).
 */
$library_local_urls = array(
		'#jquery#' => array( 'jquery.min.js', 'jquery.js' ),
		'#jqueryUI#' => array( 'jquery/jquery.ui.b2evo.min.js', 'jquery/jquery.ui.b2evo.js' ),
		'#jqueryUI_css#' => array( 'jquery/smoothness/jquery-ui.b2evo.min.css', 'jquery/smoothness/jquery-ui.b2evo.css' ),
# Uncomment the following lines if your plugins need more jQueryUI features than the ones loaded by b2evo:
#		'#jqueryUI#' => array( 'jquery/jquery.ui.all.min.js', 'jquery/jquery.ui.all.js' ),
#		'#jqueryUI_css#' => array( 'jquery/smoothness/jquery-ui.css' ),
		'#bootstrap#' => array( 'bootstrap/bootstrap.min.js', 'bootstrap/bootstrap.js' ),
		'#bootstrap_css#' => array( 'bootstrap/bootstrap.min.css', 'bootstrap/bootstrap.css' ),
		'#bootstrap_theme_css#' => array( 'bootstrap/bootstrap-theme.min.css', 'bootstrap/bootstrap-theme.css' ),
		'#bootstrap_typeahead#' => array( 'bootstrap/typeahead.bundle.min.js', 'bootstrap/typeahead.bundle.js' ),
		'#easypiechart#' => array( 'jquery/jquery.easy-pie-chart.min.js', 'jquery/jquery.easy-pie-chart.js' ),
		'#scrollto#' => array( 'jquery/jquery.scrollto.min.js', 'jquery/jquery.scrollto.js' ),
		'#touchswipe#' => array( 'jquery/jquery.touchswipe.min.js', 'jquery/jquery.touchswipe.js' ),
		'#jqplot#' => array( 'jquery/jqplot/jquery.jqplot.min.js' ),
		'#jqplot_barRenderer#' => array( 'jquery/jqplot/jqplot.barRenderer.min.js' ),
		'#jqplot_canvasAxisTickRenderer#' => array( 'jquery/jqplot/jqplot.canvasAxisTickRenderer.min.js' ),
		'#jqplot_canvasTextRenderer#' => array( 'jquery/jqplot/jqplot.canvasTextRenderer.min.js' ),
		'#jqplot_categoryAxisRenderer#' => array( 'jquery/jqplot/jqplot.categoryAxisRenderer.min.js' ),
		'#jqplot_enhancedLegendRenderer#' => array( 'jquery/jqplot/jqplot.enhancedLegendRenderer.min.js' ),
		'#jqplot_highlighter#' => array( 'jquery/jqplot/jqplot.highlighter.min.js' ),
		'#jqplot_canvasOverlay#' => array( 'jquery/jqplot/jqplot.canvasOverlay.min.js' ),
		'#jqplot_donutRenderer#' => array( 'jquery/jqplot/jqplot.donutRenderer.min.js' ),
		'#jqplot_css#' => array( 'jquery/jquery.jqplot.min.css', 'jquery/jquery.jqplot.css' ),
		'#tinymce#' => array( 'tiny_mce/tinymce.min.js' ),
		'#tinymce_gzip#' => array( 'tiny_mce/tinymce.gzip.js' ),
		'#flowplayer#' => array( 'flowplayer/flowplayer.min.js', 'flowplayer/flowplayer.js' ),
		'#mediaelement#' => array( 'mediaelement/mediaelement-and-player.min.js', 'mediaelement/mediaelement-and-player.js' ),
		'#mediaelement_css#' => array( 'mediaelement/mediaelementplayer.min.css', 'mediaelement/mediaelementplayer.css' ),
		'#videojs#' => array( 'videojs/video.min.js', 'videojs/video.js' ),
		'#videojs_css#' => array( 'videojs/video-js.min.css', 'videojs/video-js.css' ),
		'#jcrop#' => array( 'jquery/jquery.jcrop.min.js', 'jquery/jquery.jcrop.js' ),
		'#jcrop_css#' => array( 'jquery/jcrop/jquery.jcrop.min.css', 'jquery/jcrop/jquery.jcrop.css' ),
	);


/**
 * Proxy configuration for all outgoing connections (like pinging b2evolution.net or twitter, etc...)
 * Leave empty if you don't want to use a proxy.
 */
$outgoing_proxy_hostname = '';
$outgoing_proxy_port = '';
$outgoing_proxy_username = '';
$outgoing_proxy_password = '';


/**
 * Check for old browsers like IE and display info message.
 * Set to false if you don't want this check and never inform users if they use an old browser.
 */
$check_browser_version = true;


// ----- CHANGE THE FOLLOWING SETTINGS ONLY IF YOU KNOW WHAT YOU'RE DOING! -----
$evonetsrv_host = 'rpc.b2evo.net';
$evonetsrv_port = 80;
$evonetsrv_uri = '/evonetsrv/xmlrpc.php';

$antispamsrv_host = 'antispam.b2evo.net';
$antispamsrv_port = 80;
$antispamsrv_uri = '/evonetsrv/xmlrpc.php';

// This is for plugins to add CS files to the TinyMCE editor window:
$tinymce_content_css = array();
?>