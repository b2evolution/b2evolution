<?php
/**
 * This file includes upgrade settings for b2evolution.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
