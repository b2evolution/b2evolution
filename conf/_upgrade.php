<?php
/**
 * This file includes upgrade settings for b2evolution.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package conf
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**#@+
 * Old b2 tables used exclusively by the cafelog upgrade mode of the install script.
 *
 * @global string
 */
$oldtableposts      = 'b2posts';
$oldtableusers      = 'b2users';
$oldtablesettings   = 'b2settings';
$oldtablecategories = 'b2categories';
$oldtablecomments   = 'b2comments';
/**#@-*/


/**
 * Variables to be used to upgrade from previous versions on b2evolution.
 *
 * These are variables that were previously located in /conf/
 * If you want to preserve those settings copy your values over here
 * before running the upgrade script.
 */

// Moved from /blogs/conf/_advanced.php:

/**
 * Set this to 0 to disable file upload, or 1 to enable it
 * @global boolean $use_fileupload
 * @deprecated since 1.6: this is only used for creating the defaults when upgrading
 *             This has been used until EVO_NEW_VERSION by XMLRPC though.
 */
$use_fileupload = 1;

/**
 * Enter the real path of the directory where you'll upload the pictures.
 *
 * If you're unsure about what your real path is, please ask your host's support staff.
 * Note that the  directory must be writable by the webserver (ChMod 766).
 * Note for windows-servers users: use forwardslashes instead of backslashes.
 * Example: $fileupload_realpath = '/home/example/public_html/media/';	# WITH traling slash!
 * Alternatively you may want to use a path relative to $basepath.
 *
 * @global string $fileupload_realpath
 * @deprecated 1.6: the user uploads to his own media folder (or somewhere else with write permissions)
 *             This has been used until EVO_NEW_VERSION by XMLRPC/MMS though.
 */
$fileupload_realpath = $basepath.'media/';	# WARNING: slashes moved!

/**
 * Enter the URL of that directory
 *
 * This is used to generate the links to the pictures
 * Example: $fileupload_url = 'http://example.com/media/';	# WITH traling slash!
 * Alternatively you may want to use an URL relatibe to $baseurl
 *
 * @global string $fileupload_url
 * @deprecated 1.6: the user uploads to his own media folder (or somewhere else with write permissions)
 *             This has been used until EVO_NEW_VERSION by XMLRPC/MMS though.
 */
$fileupload_url = $baseurl.'media/';				# WARNING: slashes moved!


// Moved from /blogs/conf/_advanced.php:

/**
 * Accepted file types, you can add to that list if you want.
 *
 * Note: add a space before and after each file type.
 * Example: $fileupload_allowedtypes = ' jpg gif png ';
 *
 * @global string $fileupload_allowedtypes
 * @deprecated 1.6: this is only used for creating the defaults when upgrading
 */
$fileupload_allowedtypes = ' jpg gif png txt ';


/**
 * by default, most servers limit the size of uploads to 2048 KB
 * if you want to set it to a lower value, here it is (you cannot set a higher value)
 *
 * @global int $fileupload_maxk
 * @deprecated 1.6: this is only used for creating the defaults when upgrading
 */
$fileupload_maxk = '96'; // in kilo bytes


// Moved from /blogs/conf/_stats.php:

/**
 * How many days of stats do you want to keep before auto pruning them?
 *
 * Set to 0 to disable auto pruning
 *
 * @global int $stats_autoprune
 * @deprecated 1.6: this is only used for creating the defaults when upgrading
 */
$stats_autoprune = 30; // Default: 30 days

?>