<?php
/*
 * b2evolution advanced config
 * Version of this file: 0.8.9+CVS
 *
 * This file includes advanced settings for b2evolution
 */

# Comments: set this to 1 to require e-mail and name, or 0 to allow comments without e-mail/name
$require_name_email = 1;


# Set the blog number to be used when not otherwise specified
# 2 is the default setting, since it is the first user blog created by b2evo
# 1 is also a popular choice, since it is a special blog aggregating all the others
if( !isset($default_to_blog) ) $default_to_blog = 2;


// Get hostname out of baseurl
// YOU SHOULD NOT EDIT THIS unless you know what you're doing
preg_match( '#https?://([^:/]+)#', $baseurl, $matches );
$basehost = $matches[1];


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


// ** DB table names **

/**#@+
 * database tables' names
 *
 * (change them if you want to have multiple b2's in a single database)
 */
$tableposts      = 'evo_posts';
$tableusers      = 'evo_users';
$tablesettings   = 'evo_settings';
$tablecategories = 'evo_categories';
$tablecomments   = 'evo_comments';
$tableblogs      = 'evo_blogs';
$tablepostcats   = 'evo_postcats';
$tablehitlog     = 'evo_hitlog';
$tableantispam   = 'evo_antispam';
$tablegroups     = 'evo_groups';
$tableblogusers  = 'evo_blogusers';
$tablelocales    = 'evo_locales';
/**#@-*/

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


// ** Saving bandwidth **

/**
 * use output buffer.
 *
 * This is required for gzip and ETags (see below).
 * Recommended to be enabled. Turn it of only if you should encounter problems.
 * Even without using gzip compression or ETags this allows to send a Content-Length, which
 * is good for large pages.
 */
$use_obhandler = 1;

/**
 * gzip compression.
 *
 * Can actually be done either by PHP or by Apache (if your Apache has mod_gzip).
 * Set this to 1 if you want PHP to do gzip compression
 * Set this to 0 if you want to let Apache do the job instead of PHP (you must enable this there)
 *
 * {@internal Letting apache do the compression will make PHP debugging easier }}
 */
$use_gzipcompression = 0;

/**
 * ETags support.
 *
 * This will send an ETag with every page, so we can say "Not Modified." if exactly the same
 * page had been sent before.
 * Etags don't work right in some versions of IE. You won't be able to force refreshment of a page.
 */
$use_etags = 0;


// ** Cookies **

# This is the path that will be associated to cookies
# That means cookies set by this b2evo install won't be seen outside of this path on the domain below
$cookie_path = preg_replace('#https?://[^/]+#', '', $baseurl ).'/';

/**
 * Cookie domain
 *
 * That means cookies set by this b2evo install won't be seen outside of this domain
 */
$cookie_domain = ($basehost == 'localhost') ? '' : '.'. $basehost;
//echo 'domain='. $cookie_domain. ' path='. $cookie_path;

# Cookie names:
$cookie_user  = 'cookie'. $b2evo_name. 'user';
$cookie_pass  = 'cookie'. $b2evo_name. 'pass';
$cookie_state = 'cookie'. $b2evo_name. 'state';
$cookie_name  = 'cookie'. $b2evo_name. 'name';
$cookie_email = 'cookie'. $b2evo_name. 'email';
$cookie_url   = 'cookie'. $b2evo_name. 'url';

# Expiration (values in seconds)
# Set this to 0 if you wish to use non permanent cookies (erased when browser is closed)
$cookie_expires = time() + 31536000;		// Default: one year from now

# Expired time used to erase cookies:
$cookie_expired = time() - 86400;				// Default: 24 hours ago


# Location of the b2evolution subdirectories:
# You should only move these around if you really need to.
# You should keep everything as subdirectories of the base folder
# ($baseurl which is set in _config.php, default is the /blogs folder)
# Remember you can set the baseurl to your website root.
# NOTE: ALL PATHS MUST HAVE NO LEADING AND NO TRAILING SLASHES !!!
# Example of a possible setting:
# $admin_subdir = 'backoffice/b2evo';				// Subdirectory relative to base
# $admin_dirout = '../..';									// Relative path to go back to base

# Location of the backoffice (admin) folder:
$admin_subdir = 'admin';                     // Subdirectory relative to base
$admin_dirout = '..';                        // Relative path to go back to base
$admin_url = $baseurl. '/'. $admin_subdir;   // You should not need to change this
# Location of the HTml SeRVices folder:
$htsrv_subdir = 'htsrv';                     // Subdirectory relative to base
$htsrv_dirout = '..';                        // Relative path to go back to base
$htsrv_url = $baseurl. '/'. $htsrv_subdir;   // You should not need to change this
# Location of the XML SeRVices folder:
$xmlsrv_subdir = 'xmlsrv';                   // Subdirectory relative to base
$xmlsrv_dirout = '..';                       // Relative path to go back to base
$xmlsrv_url = $baseurl. '/'. $xmlsrv_subdir; // You should not need to change this
# Location of the skins folder:
$skins_subdir = 'skins';                     // Subdirectory relative to base
$skins_dirout = '..';                        // Relative path to go back to base
$skins_url = $baseurl. '/'. $skins_subdir;   // You should not need to change this
# Location of the core (the "includes") files:
$core_subdir = 'b2evocore';                 // Subdirectory relative to base
$core_dirout = '..';                        // Relative path to go back to base
# Location of the locales folder:
$locales_subdir = 'locales';                // Subdirectory relative to base
$locales_dirout = '..';                     // Relative path to go back to base
# Location of the configuration files:
$conf_subdir = 'conf';                      // Subdirectory relative to base
$conf_dirout = '..';                        // Relative path to go back to base
# Location of the install folder:
$install_dirout = '..';                     // Relative path to go back to base


# CHANGE THE FOLLOWING ONLY IF YOU KNOW WHAT YOU'RE DOING!
$use_cache = 1;							// Not using this will dramatically overquery the DB !
$sleep_after_edit = 0;			// let DB do its stuff...

?>
