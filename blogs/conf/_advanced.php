<?php
/**
 * This is b2evolution's advanced config file
 *
 * This file includes advanced settings for b2evolution
 * Last significant changes to this file: version 0.9.1
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package conf
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

# General params:
# (these must be forced to prevent URL overrides).
$debug = false;
$obhandler_debug = false;
$demo_mode = false;


# Comments: set this to 1 to require e-mail and name, or 0 to allow comments without e-mail/name
$require_name_email = 1;


# Set the blog number to be used when not otherwise specified
# 2 is the default setting, since it is the first user blog created by b2evo
# 1 is also a popular choice, since it is a special blog aggregating all the others
if( !isset($default_to_blog) ) $default_to_blog = 2;

# Set the length of the online session time out. This is for the Who's Online block.
$online_session_timeout = 300;  // TODO: into backoffice?

// Get hostname out of baseurl
// YOU SHOULD NOT EDIT THIS unless you know what you're doing
if( preg_match( '#(https?://(.+?)(:.+?)?)/#', $baseurl.'/', $matches ) )
{
	$baseurlroot = $matches[1];
	// echo "baseurlroot=$baseurlroot <br />";
	$basehost = $matches[2];
	// echo "basehost=$basehost <br />";
}
else
{
	die( 'Your baseurl ('.$baseurl.') set in _config.php seems invalid. You probably missed the "http://" prefix. Please correct that.' );
}

# Short name of this system (will be used for cookies and notification emails)
# Change this only if you install mutliple b2evolutions on the same website.
# WARNING: don't play with this or you'll have tons of cookies sent away and your
# readers surely will complain about it!
# You can change notification email address alone a few lines below
$b2evo_name = 'b2evo';		// MUST BE A SINGLE WORD! NO SPACES!!


# default email address for sending notifications (comments, trackbacks, user registrations...)
# Set a custom address like this:
# $notify_from = 'b2evolution@your_server.com'; // uncomment this line if you want to customize
# Alternatively you can use this automated address generation
$notify_from = $b2evo_name.'@'.$basehost; // comment this line if you want to customize


// ** Configuration for htsrv/getmail.php **
// (skip this if you don't intend to blog via email)
# mailserver settings
$mailserver_url = 'mail.example.com';
$mailserver_login = 'login@example.com';
$mailserver_pass = 'password';
$mailserver_port = 110;
# by default posts will have this category
$default_category = 1;
# subject prefix
$subjectprefix = 'blog:';
# body terminator string (starting from this string, everything will be ignored, including this string)
$bodyterminator = "___";
# set this to 1 to run in test mode
$thisisforfunonly = 0;
### Special Configuration for some phone email services
# some mobile phone email services will send identical subject & content on the same line
# if you use such a service, set $use_phoneemail to 1, and indicate a separator string
# when you compose your message, you'll type your subject then the separator string
# then you type your login:password, then the separator, then content
$use_phoneemail = 0;
$phoneemail_separator = ':::';


# When pinging http://blo.gs, use extended ping to RSS?
$use_rss = 1;

# If a particular post is requested (by id or title) but on the wrong blog,
# do you want to automatically redirect to the right blog?
# This is overly usefull if you move posts or categories from one blog to another
# If this is disabled, the post will be displayed in the wrong blog template.
$redirect_to_postblog = 1;


// ** DB table names **

/**#@+
 * database tables' names
 *
 * (You should not need to change them. 
 *  If you want to have multiple b2evo installations in a single database you should
 *  change $tableprefix in _config.php)
 */
$tableposts        = $tableprefix.'posts';
$tableusers        = $tableprefix.'users';
$tablesettings     = $tableprefix.'settings';
$tablecategories   = $tableprefix.'categories';
$tablecomments     = $tableprefix.'comments';
$tableblogs        = $tableprefix.'blogs';
$tablepostcats     = $tableprefix.'postcats';
$tablehitlog       = $tableprefix.'hitlog';
$tableantispam     = $tableprefix.'antispam';
$tablegroups       = $tableprefix.'groups';
$tableblogusers    = $tableprefix.'blogusers';
$tablelocales      = $tableprefix.'locales';
$tablesessions     = $tableprefix.'sessions';
$tableusersettings = $tableprefix.'usersettings';
/**#@-*/

/**
 * Aliases for class DB:
 */
$db_aliases = array(
		'EVO_posts'        => $tableposts       ,
		'EVO_users'        => $tableusers       ,
		'EVO_settings'     => $tablesettings    ,
		'EVO_categories'   => $tablecategories  ,
		'EVO_comments'     => $tablecomments    ,
		'EVO_blogs'        => $tableblogs       ,
		'EVO_postcats'     => $tablepostcats    ,
		'EVO_hitlog'       => $tablehitlog      ,
		'EVO_antispam'     => $tableantispam    ,
		'EVO_groups'       => $tablegroups      ,
		'EVO_blogusers'    => $tableblogusers   ,
		'EVO_locales'      => $tablelocales     ,
		'EVO_sessions'     => $tablesessions    ,
		'EVO_usersettings' => $tableusersettings,
	);

/**#@+
 * Old b2 tables used exclusively by the upgrade mode of the install script
 *
 * You can ignore those if you're not planning to upgrade from an original b2 database.
 */
$oldtableposts      = 'b2posts';
$oldtableusers      = 'b2users';
$oldtablesettings   = 'b2settings';
$oldtablecategories = 'b2categories';
$oldtablecomments   = 'b2comments';
/**#@-*/

/**
 * WordPress table prefix used exclusively by the upgrade script
 * You can ignore this if you're not planning to upgrade from a WordPress database.
 */
$wp_prefix = 'wp_';


// ** Saving bandwidth **

/**
 * use output buffer.
 *
 * This is required for gzip and ETags (see below).
 *
 * Disabled by default.
 *
 * Even without using gzip compression or ETags this allows to send a Content-Length.
 * 
 * Warning: this will prevent from sending the output progressively to the webserver. 
 * If a long page takes 2 seconds to be generated completely on a loaded server, the top 
 * of the page will only be sent after those 2 seconds, and the user won't see anything 
 * during at least 2 seconds (generation + transmission time). Without this setting, the
 * output will be sent progressively starting at 0 seconds and the user will start seeing 
 * something earlier (0 + transmission time).
 *
 * @global boolean $use_obhandler
 */
$use_obhandler = 0;

/**
 * GZip compression.
 *
 * Disabled by default.
 *
 * Can actually be done either by PHP or your webserver [default] 
 * (for example if you use Apache with mod_gzip).
 * - Set this to 1 if you want PHP to do gzip compression
 * - Set this to 0 if you want to let Apache do the job instead of PHP (you must enable this there)
 * Letting apache do the compression will make PHP debugging easier. 
 * Thus it is recommended to keep it that way.
 *
 * @global boolean $use_gzipcompression
 */
$use_gzipcompression = 0;

/**
 * ETags support.
 *
 * Disabled by default.
 *
 * This will send an ETag with every page, so we can say "Not Modified." if exactly the same
 * page had been sent before.
 *
 * Etags don't work right in some versions of IE. You won't be able to force refreshment of a page.
 *
 * @global boolean $use_etags
 */
$use_etags = 0;


// ** Cookies **

# This is the path that will be associated to cookies
# That means cookies set by this b2evo install won't be seen outside of this path on the domain below
$cookie_path = preg_replace('#https?://[^/]+#', '', $baseurl ).'/';

/**
 * Cookie domain.
 *
 * That means cookies set by this b2evo install won't be seen outside of this domain
 */
$cookie_domain = ($basehost == 'localhost') ? '' : '.'. $basehost;
//echo 'domain='. $cookie_domain. ' path='. $cookie_path;

/**#@+
 * Names for cookies.
 */
$cookie_user  = 'cookie'. $b2evo_name. 'user';
$cookie_pass  = 'cookie'. $b2evo_name. 'pass';
$cookie_state = 'cookie'. $b2evo_name. 'state';
$cookie_name  = 'cookie'. $b2evo_name. 'name';
$cookie_email = 'cookie'. $b2evo_name. 'email';
$cookie_url   = 'cookie'. $b2evo_name. 'url';
/**#@-*/

/**
 * Expiration for cookies.
 *
 * Value in seconds, set this to 0 if you wish to use non permanent cookies (erased when browser is closed).
 */
$cookie_expires = time() + 31536000;		// Default: one year from now

/**
 * Expired-time used to erase cookies.
 */
$cookie_expired = time() - 86400;				// Default: 24 hours ago


// ** Location of the b2evolution subdirectories **

/*
	- You should only move these around if you really need to.
	- You should keep everything as subdirectories of the base folder
		($baseurl which is set in _config.php, default is the /blogs folder)
	- Remember you can set the baseurl to your website root (-> _config.php).
	
	NOTE: ALL PATHS MUST HAVE NO LEADING AND NO TRAILING SLASHES !!!

	Example of a possible setting:
		$admin_subdir = 'backoffice/b2evo';				// Subdirectory relative to base
		$admin_dirout = '../..';									// Relative path to go back to base
*/
/**
 * Location of the configuration files.
 */
$conf_subdir = 'conf';                       // Subdirectory relative to base
$conf_dirout = '..';                         // Relative path to go back to base

$conf_path = str_replace( '\\', '/', dirname(__FILE__) );
$basepath = preg_replace( '#/'.$conf_subdir.'$#', '', $conf_path );	// Remove his file's subpath

/**
 * Location of the backoffice (admin) folder.
 */
$admin_subdir = 'admin';                     // Subdirectory relative to base
$admin_dirout = '..';                        // Relative path to go back to base
$admin_url = $baseurl.'/'.$admin_subdir;     // You should not need to change this
/**
 * Location of the HTml SeRVices folder.
 */
$htsrv_subdir = 'htsrv';                     // Subdirectory relative to base
$htsrv_dirout = '..';                        // Relative path to go back to base
$htsrv_url = $baseurl.'/'.$htsrv_subdir;     // You should not need to change this
/**
 * Location of the XML SeRVices folder.
 */
$xmlsrv_subdir = 'xmlsrv';                   // Subdirectory relative to base
$xmlsrv_dirout = '..';                       // Relative path to go back to base
$xmlsrv_url = $baseurl.'/'.$xmlsrv_subdir;   // You should not need to change this
/**
 * Location of the IMG folder.
 */
$img_subdir = 'img';                     // Subdirectory relative to base
$img_url = $baseurl.'/'.$img_subdir;     // You should not need to change this
/**
 * Location of the skins folder.
 */
$skins_subdir = 'skins';                     // Subdirectory relative to base
$skins_dirout = '..';                        // Relative path to go back to base
$skins_url = $baseurl.'/'.$skins_subdir;     // You should not need to change this
/**
 * Location of the core (the "includes") files.
 */
$core_subdir = 'b2evocore';                  // Subdirectory relative to base
$core_dirout = '..';                         // Relative path to go back to base
/**
 * Location of the locales folder.
 */
$locales_subdir = 'locales';                 // Subdirectory relative to base
$locales_dirout = '..';                      // Relative path to go back to base
/**
 * Location of the plug-ins.
 */
$plugins_subdir = 'plugins';                 // Subdirectory relative to base
$plugins_subdir_dirout = '..';               // Relative path to go back to base
/**
 * Location of the install folder.
 */
$install_subdir = 'install';                 // Subdirectory relative to base
$install_dirout = '..';                      // Relative path to go back to base
/**
 * Location of the root media folder.
 */
$media_subdir = 'media';                     // Subdirectory relative to base
$media_dirout = '..';                        // Relative path to go back to base


// ** CHANGE THE FOLLOWING ONLY IF YOU KNOW WHAT YOU'RE DOING! **
$use_cache = 1;							// Not using this will dramatically overquery the DB !
$sleep_after_edit = 0;			// let DB do its stuff...
$evonetsrv_host = 'b2evolution.net';
$evonetsrv_port = 80;
$evonetsrv_uri = '/evonetsrv/xmlrpc.php';
?>