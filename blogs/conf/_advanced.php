<?php
/**
 * This file includes advanced settings for b2evolution.
 *
 * Last significant changes to this file: version 0.9.1
 *
 * Please NOTE: You should not comment variables out to prevent
 * URL overrides.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package conf
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Display debugging informations?
 * @global boolean Default: false
 */
$debug = false;

/**
 * Display elements that are different on each request (Page processing time, ..)
 * @global boolean Default: false
 */
$obhandler_debug = false;

/**
 * Demo mode: don't allow changes to the 'demouser' account.
 * @global boolean Default: false
 */
$demo_mode = false;


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


if( !isset($default_to_blog) )
{ /**
	 * Set the blog number to be used when not otherwise specified.
	 * 2 is the default setting, since it is the first user blog created by b2evo.
	 * 1 is also a popular choice, since it is a special blog aggregating all the others.
	 * @global int $default_to_blog
	 * @todo move to $Settings
	 */
	$default_to_blog = 2;
}

/**
 * Set the length of the online session time out (in seconds).
 *
 * This is for the Who's Online block. Default: 5 minutes (300s).
 *
 * @global int $online_session_timeout
 * @todo move to $Settings
 * @todo Rename to "Who's online timeout"
 */
$online_session_timeout = 300;


// Get hostname out of baseurl
// YOU SHOULD NOT EDIT THIS unless you know what you're doing
if( preg_match( '#(https?://(.+?)(:.+?)?)/#', $baseurl, $matches ) )
{
	$baseurlroot = $matches[1];
	// echo "baseurlroot=$baseurlroot <br />";
	$basehost = $matches[2];
	// echo "basehost=$basehost <br />";
}
else
{
	die( 'Your baseurl ('.$baseurl.') set in _config.php seems invalid. You probably missed the "http://" prefix or the trailing slash. Please correct that.' );
}

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


/**
 * Default email address for sending notifications (comments, trackbacks,
 * user registrations...).
 *
 * Set a custom address like this:
 * <code>$notify_from = 'b2evolution@your_server.com';</code>
 *
 * Alternatively you can use this automated address generation:
 * <code>$notify_from = $instance_name.'@'.$basehost;</code>
 *
 * @global string Default: $instance_name.'@'.$basehost;
 */
$notify_from = $instance_name.'@'.$basehost;


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


/**
 * When pinging http://blo.gs, use extended ping to RSS?
 *
 * @var integer Default: 1
 */
$use_rss = 1;

/**
 * If a particular post is requested (by id or title) but on the wrong blog,
 * do you want to automatically redirect to the right blog?
 *
 * This is overly usefull if you move posts or categories from one blog to another
 *
 * If this is disabled, the post will be displayed in the wrong blog template.
 *
 * @var boolean Default: false
 */
$redirect_to_postblog = false;


// ** DB table names **

/**
 * Aliases for class DB:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change $tableprefix in _config.php)
 * @global array
 */
$db_aliases = array(
		'T_antispam'         => $tableprefix.'antispam',
		'T_basedomains'      => $tableprefix.'basedomains',
		'T_blogs'            => $tableprefix.'blogs',
		'T_blogusers'        => $tableprefix.'blogusers',
		'T_categories'       => $tableprefix.'categories',
		'T_comments'         => $tableprefix.'comments',
		'T_files'            => $tableprefix.'files',
		'T_groups'           => $tableprefix.'groups',
		'T_hitlog'           => $tableprefix.'hitlog',
		'T_links'            => $tableprefix.'links',
		'T_locales'          => $tableprefix.'locales',
		'T_plugins'          => $tableprefix.'plugins',
		'T_postcats'         => $tableprefix.'postcats',
		'T_posts'            => $tableprefix.'posts',
		'T_poststatuses'     => $tableprefix.'poststatuses',
		'T_posttypes'        => $tableprefix.'posttypes',
		'T_sessions'         => $tableprefix.'sessions',
		'T_settings'         => $tableprefix.'settings',
		'T_users'            => $tableprefix.'users',
		'T_useragents'       => $tableprefix.'useragents',
		'T_usersettings'     => $tableprefix.'usersettings',
	);


/**
 * CREATE TABLE options.
 *
 * Edit those if you have control over you MySQL server and want a more professional
 * database than what is commonly offered by popular hosting providers.
 *
 * @global string $db_table_options
 */
// Low ranking MySQL hosting compatibility Default:
$db_table_options = '';
// Recommended settings:
# $db_table_options = ' ENGINE=InnoDB ';
// Development settings:
# $db_table_options = ' ENGINE=InnoDB DEFAULT CHARSET=utf8 ';


/**
 * Foreign key options.
 *
 * Set this to true if your MySQL supports Foreign keys.
 * Recommended for professional use.
 * Typically requires InnoDB to be set in $db_table_options.
 *
 * @global boolean $db_use_fkeys
 */
$db_use_fkeys = false;


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

/**
 * This is the path that will be associated to cookies
 *
 * That means cookies set by this b2evo install won't be seen outside of this path on the domain below
 *
 * @global string Default: preg_replace( '#https?://[^/]+#', '', $baseurl )
 */
$cookie_path = preg_replace( '#https?://[^/]+#', '', $baseurl );

/**
 * Cookie domain.
 *
 * That means cookies set by this b2evo install won't be seen outside of this domain.
 *
 * @global string Default: ($basehost == 'localhost') ? '' : '.'. $basehost;
 */
$cookie_domain = ($basehost == 'localhost') ? '' : '.'. $basehost;
//echo 'domain='. $cookie_domain. ' path='. $cookie_path;

/**#@+
 * Names for cookies.
 */
$cookie_user    = 'cookie'.$instance_name.'user';
$cookie_pass    = 'cookie'.$instance_name.'pass';
$cookie_state   = 'cookie'.$instance_name.'state';
$cookie_name    = 'cookie'.$instance_name.'name';
$cookie_email   = 'cookie'.$instance_name.'email';
$cookie_url     = 'cookie'.$instance_name.'url';
$cookie_session = 'cookie'.$instance_name.'session';
/**#@-*/

/**
 * Expiration for cookies.
 *
 * Value in seconds, set this to 0 if you wish to use non permanent cookies (erased when browser is closed).
 *
 * @global int Default: time() + 31536000; // One year from now
 */
$cookie_expires = time() + 31536000;

/**
 * Expired-time used to erase cookies.
 *
 * @global int time() - 86400;    // 24 hours ago
 */
$cookie_expired = time() - 86400;


// ** Location of the b2evolution subdirectories **

/*
	- You should only move these around if you really need to.
	- You should keep everything as subdirectories of the base folder
		($baseurl which is set in _config.php, default is the /blogs/ folder)
	- Remember you can set the baseurl to your website root (-> _config.php).

	NOTE: All paths must have a trailing slash!

	Example of a possible setting:
		$admin_subdir = 'backoffice/b2evo/'; // Subdirectory relative to base
		$admin_dirout = '../../';            // Relative path to go back to base
*/
/**
 * Location of the configuration files.
 * @global string $conf_subdir
 */
$conf_subdir = 'conf/';                  // Subdirectory relative to base
$conf_dirout = '../';                    // Relative path to go back to base

$conf_path = str_replace( '\\', '/', dirname(__FILE__) ).'/';
$basepath = preg_replace( '#/'.$conf_subdir.'$#', '', $conf_path ).'/'; // Remove his file's subpath

/**
 * Location of the backoffice (admin) folder.
 * @global string $admin_subdir
 */
$admin_subdir = 'admin/';                // Subdirectory relative to base
$admin_dirout = '../';                   // Relative path to go back to base
$admin_url = $baseurl.$admin_subdir;     // You should not need to change this
/**
 * Location of the HTml SeRVices folder.
 * @global string $htsrv_subdir
 */
$htsrv_subdir = 'htsrv/';                // Subdirectory relative to base
$htsrv_dirout = '../';                   // Relative path to go back to base
$htsrv_url = $baseurl.$htsrv_subdir;     // You should not need to change this
/**
 * Location of the XML SeRVices folder.
 * @global string $xmlsrv_subdir
 */
$xmlsrv_subdir = 'xmlsrv/';              // Subdirectory relative to base
$xmlsrv_dirout = '../';                  // Relative path to go back to base
$xmlsrv_url = $baseurl.$xmlsrv_subdir;   // You should not need to change this
/**
 * Location of the IMG folder.
 * @global string $img_subdir
 */
$img_subdir = 'img/';                    // Subdirectory relative to base
$img_url = $baseurl.$img_subdir;         // You should not need to change this
/**
 * Location of the RSC folder.
 * @global string $rsc_subdir
 */
$rsc_subdir = 'rsc/';                    // Subdirectory relative to base
$rsc_url = $baseurl.$rsc_subdir;         // You should not need to change this
/**
 * Location of the skins folder.
 * @global string $skins_subdir
 */
$skins_subdir = 'skins/';                // Subdirectory relative to base
$skins_dirout = '../';                   // Relative path to go back to base
$skins_url = $baseurl.$skins_subdir;     // You should not need to change this
/**
 * Location of the admin skins folder.
 * @global string $adminskins_subdir
 */
$adminskins_subdir = 'skins/';             // Subdirectory relative to ADMIN
$adminskins_dirout = '../';                // Relative path to go back to ADMIN
$adminskins_url = $baseurl.$admin_subdir.$skins_subdir;  // You should not need to change this
/**
 * Location of the core (the "includes") files.
 * @global string $core_subdir
 */
$core_subdir = 'evocore/';               // Subdirectory relative to base
$core_dirout = '../';                    // Relative path to go back to base
/**
 * Location of the lib (the "external includes") files.
 * @global string $lib_subdir
 */
$lib_subdir = 'lib/';                    // Subdirectory relative to base
$lib_dirout = '../';                     // Relative path to go back to base
/**
 * Location of the locales folder.
 * @global string $locales_subdir
 */
$locales_subdir = 'locales/';            // Subdirectory relative to base
$locales_dirout = '../';                 // Relative path to go back to base
/**
 * Location of the plug-ins.
 * @global string $plugins_subdir
 */
$plugins_subdir = 'plugins/';            // Subdirectory relative to base
$plugins_dirout = '../';                 // Relative path to go back to base
/**
 * Location of the install folder.
 * @global string $install_subdir
 */
$install_subdir = 'install/';            // Subdirectory relative to base
$install_dirout = '../';                 // Relative path to go back to base
/**
 * Location of the root media folder.
 * @global string $media_subdir
 */
$media_subdir = 'media/';                // Subdirectory relative to base
$media_dirout = '../';                   // Relative path to go back to base
$media_url = $baseurl.$media_subdir;     // You should not need to change this


// ----- CHANGE THE FOLLOWING ONLY IF YOU KNOW WHAT YOU'RE DOING! -----
$evonetsrv_host = 'b2evolution.net';
$evonetsrv_port = 80;
$evonetsrv_uri = '/evonetsrv/xmlrpc.php';


/**
 * Regular expression to match image filenames.
 * @global string Default: '/\.(jpe?g|gif|png|swf)$/i'
 */
$regexp_images = '/\.(jpe?g|gif|png|swf)$/i';

?>