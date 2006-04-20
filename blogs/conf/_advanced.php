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
 *
 * @global integer
 */
$debug = 0;

/**
 * Display elements that are different on each request (Page processing time, ..)
 * @global boolean Default: false
 */
$obhandler_debug = false;


/**
 * Demo mode: don't allow changes to the 'demouser' and 'admin' account.
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
	 * @todo move to {@link $Settings}
	 */
	$default_to_blog = 2;
}


/**
 * Set the length of the online session time out (in seconds).
 *
 * This is for the Who's Online block. Default: 5 minutes (300s).
 *
 * @global integer
 */
$timeout_online_user = 300; // Default: 5 minutes (300s).


// Get hostname out of baseurl
// YOU SHOULD NOT EDIT THIS unless you know what you're doing
if( preg_match( '#^(https?://(.+?)(:.+?)?)/#', $baseurl, $matches ) )
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
 * @todo For a $baseurl of "http://www.example.com/" you'll get "b2evo@www.example.com" here! - Should we strip "www." at the beginning (what getBaseDomain() does also)? fp>> ok
 *
 * @global string Default: $instance_name.'@'.$basehost;
 */
$notify_from = $instance_name.'@'.$basehost;


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


// ** DB options **

/**
 * Show MySQL errors? (default: true)
 *
 * This is recommended on production environments.
 */
$EvoConfig->DB['show_errors'] = true;


/**
 * Halt on MySQL errors? (default: true)
 *
 * Setting this to false is not recommended,
 */
$EvoConfig->DB['halt_on_error'] = true;


/**
 * Aliases for table names:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change {@link $tableprefix} in _config.php)
 */
$EvoConfig->DB['aliases'] = array(
		'T_antispam'           => $tableprefix.'antispam',
		'T_basedomains'        => $tableprefix.'basedomains',
		'T_blogs'              => $tableprefix.'blogs',
		'T_categories'         => $tableprefix.'categories',
		'T_coll_group_perms'   => $tableprefix.'bloggroups',
		'T_coll_user_perms'    => $tableprefix.'blogusers',
		'T_coll_settings'      => $tableprefix.'coll_settings',
		'T_comments'           => $tableprefix.'comments',
		'T_files'              => $tableprefix.'files',
		'T_filetypes'          => $tableprefix.'filetypes',
		'T_groups'             => $tableprefix.'groups',
		'T_hitlog'             => $tableprefix.'hitlog',
		'T_links'              => $tableprefix.'links',
		'T_locales'            => $tableprefix.'locales',
		'T_plugins'            => $tableprefix.'plugins',
		'T_pluginevents'       => $tableprefix.'pluginevents',
		'T_pluginsettings'     => $tableprefix.'pluginsettings',
		'T_pluginusersettings' => $tableprefix.'pluginusersettings',
		'T_postcats'           => $tableprefix.'postcats',
		'T_posts'              => $tableprefix.'posts',
		'T_itemstatuses'       => $tableprefix.'poststatuses',
		'T_itemtypes'          => $tableprefix.'posttypes',
		'T_sessions'           => $tableprefix.'sessions',
		'T_settings'           => $tableprefix.'settings',
		'T_subscriptions'      => $tableprefix.'subscriptions',
		'T_users'              => $tableprefix.'users',
		'T_useragents'         => $tableprefix.'useragents',
		'T_usersettings'       => $tableprefix.'usersettings',
	);


/**
 * CREATE TABLE options.
 *
 * Edit those if you have control over you MySQL server and want a more professional
 * database than what is commonly offered by popular hosting providers.
 */
$EvoConfig->DB['table_options'] = ''; 	// Low ranking MySQL hosting compatibility Default
// Recommended settings:
# $EvoConfig->DB['table_options'] = ' ENGINE=InnoDB ';
// Development settings:
# $EvoConfig->DB['table_options'] = ' ENGINE=InnoDB DEFAULT CHARSET=utf8 ';


/**
 * Use transactions in DB?
 *
 * You need to use InnoDB in order to enable this. See the {@link $EvoConfig->DB "table_options" key}.
 */
$EvoConfig->DB['use_transactions'] = false;
// Recommended settings:
# $EvoConfig->DB['use_transactions'] = true;


/**
 * Foreign key options.
 *
 * Set this to true if your MySQL supports Foreign keys.
 * Recommended for professional use and DEVELOPMENT only.
 * As of today, upgrading is not guaranteed when foreign keys are enabled.
 *
 * Typically requires InnoDB to be set in $EvoConfig->DB['table_options'].
 *
 * This is used during table CREATION only.
 *
 * @todo provide an advanced install menu allowing to install/remove the foreign keys on an already installed db.
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

/*
blueyed>>TODO:
- Cookie needs hash of domain name in its name, eg:
	$cookie_session = 'cookie'.small_hash($Cookies->domain).$instancename.'user'
	(Because: cookies for .domain.tld have higher priority over .sub.domain.tld, with the same cookie name,
		the hash would put that into the name)
	[ Related to PHP bug #32802 (http://bugs.php.net/bug.php?id=32802 - fixed in 5.0.5 (also backported)), but which only affects paths.
		Also see http://www.faqs.org/rfcs/rfc2965:
		"If multiple cookies satisfy the criteria above, they are ordered in
		the Cookie header such that those with more specific Path attributes
		precede those with less specific.  Ordering with respect to other
		attributes (e.g., Domain) is unspecified."
	]
	- Transform: catch existing cookies, transform to new format

fplanque>>What's a real world scenario where this is a problem?
blueyed>> e.g. demo.b2evolution.net and b2evolution.net; or example.com and private.example.com (both running (different) b2evo instances (but with same $instancename)
fplanque>>that's what I thought. This is exactly why we have instance names in the first place. So we won't add a second mecanism. We can however use one of these two enhancements: 1) have the default conf use a $baseurl hash for instance name instead of 'b2evo' or 2) generate a random instance name at install and have it saved in the global params in the DB. NOTE: we also need to check if this can be broken when using b2evo in multiple domain mode.
- Use object to handle cookies
	- We need to know for example if a cookie is about to be sent (because then we don't want to send a 304 response).

fplanque>>What's a real world scenario where this is a problem?
blueyed>>When we detect that the content hasn't changed and are about to send a 304 response code we won't do it if we now that (login) cookies should be sent.
fplanque>>ok. If you do it, please do it in a generic $Response object which will not only handle cookies but also stuff like charset translations, format_to_output(), etc.
*/

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
 * @global string Default: ( strpos($basehost, '.') ) ? '.'. $basehost : '';
 */
$cookie_domain = ( strpos($basehost, '.') ? '.'. $basehost : '' );
//echo 'cookie_domain='. $cookie_domain. ' cookie_path='. $cookie_path;

/**#@+
 * Names for cookies.
 */
// This is mainly used for storing the prefered skin:
// Note: This is not a SESSION variable. It is a user pref that works even for non registered users.
$cookie_state   = 'cookie'.$instance_name.'state';
// The following remember the comment meta data for non registered users:
$cookie_name    = 'cookie'.$instance_name.'name';
$cookie_email   = 'cookie'.$instance_name.'email';
$cookie_url     = 'cookie'.$instance_name.'url';
// The following handles the session:
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
// echo ' conf_path='.$conf_path;

$basepath = preg_replace( '#/'.$conf_subdir.'$#', '', $conf_path ).'/'; // Remove this file's subpath
// echo ' basepath='.$basepath;

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
$model_path = $inc_path.'MODEL/';	  		 	// You should not need to change this
$view_path = $inc_path.'VIEW/';						// You should not need to change this
$control_path = $inc_path.'CONTROL/';			// You should not need to change this

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
$skins_url = $baseurl.$skins_subdir;     // You should not need to change this


/**
 * Location of the admin interface dispatcher
 */
$dispatcher = 'admin.php';
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
 * Location of the install folder.
 * @global string $install_subdir
 */
$install_subdir = 'install/';            // Subdirectory relative to base

/**
 * Location of the root media folder.
 *
 * Note: This folder MAY or MAY NOT NEED to be accessible by PHP AND/OR through HTTP.
 * Exact requirements depend on $public_access_to_media .
 *
 * @global string $media_subdir
 */
$media_subdir = 'media/';                // Subdirectory relative to base
$media_url = $baseurl.$media_subdir;     // You should not need to change this


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
$upload_maxmaxkb = 2048;

/**
 * The admin can configure the regexp for valid file names in the Settings interface
 * However if the following values are set to non empty, the admin will not be able to customize these values.
 */
$force_regexp_filename = '';
$force_regexp_dirname = '';


/**
 * Set this to 1 to disable using PHP's register_shutdown_function(),
 * but not everywhere.
 *
 * This is NOT recommened, because it affects things that should be done after delivering the page.
 *
 * Currently, it disables using register_shutdown_function() for hit_doublecheck_referer
 * ({@link Hit::log()}), but not for {@link Session::dbsave()}.
 *
 * It's probably only useful for debugging to disable this feature.
 * @global int $debug_no_register_shutdown
 */
$debug_no_register_shutdown = 0;


// ----- CHANGE THE FOLLOWING ONLY IF YOU KNOW WHAT YOU'RE DOING! -----
$evonetsrv_host = 'b2evolution.net';
$evonetsrv_port = 80;
$evonetsrv_uri = '/evonetsrv/xmlrpc.php';

?>