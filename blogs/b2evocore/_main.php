<?php
/**
 * This file initializes everything BUT the blog!
 *
 * It is useful when you want to do very customized templates!
 * It is also called by more complete initializers.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */
if( isset( $main_init ) )
{ // Prevent double loading since require_once won't work in all situations
	// on windows when some subfolders have caps :(
	// (Check it out on static page generation)
	return;
}
$main_init = true;


/**
 * Load base + advanced configuration:
 */
require_once( dirname(__FILE__).'/../conf/_config.php' );
if( !$config_is_done )
{ // base config is not done!
	$error_message = 'Base configuration is not done.';
	require dirname(__FILE__).'/_conf_error.page.php';	// error & exit
}
/*
 * Check conf...
 */
if( !function_exists( 'gzencode' ) )
{ // when there is no function to gzip, we won't do it
	$use_gzipcompression = false;
}


/**
 * Load logging class
 */
require_once( dirname(__FILE__).'/_class_log.php' );
/**
 * Debug message log for debugging only (initialized here)
 * @global Log $Debuglog
 */
$Debuglog = new Log( 'note' );
/**
 * Info & error message log for end user (initialized here)
 * @global Log $Debuglog
 */
$Messages = new Log( 'error' );


/**
 * Includes:
 */
require_once( dirname(__FILE__).'/_functions.php' );

timer_start();

require_once( dirname(__FILE__).'/_vars.php' );                  // sets various arrays and vars for use in b2


/**
 * Database connection (connection opened here)
 *
 * @global DB $DB
 */
require_once( dirname(__FILE__).'/_class_db.php' );
$DB = new DB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST, $db_aliases );


/**
 * Interface to general settings
 *
 * @global GeneralSettings $Settings
 */
require_once( dirname(__FILE__).'/_class_generalsettings.php' );
$Settings = & new GeneralSettings();

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
 * Interface to user settings
 *
 * @global UserSettings $UserSettings
 */
require_once( dirname(__FILE__).'/_class_usersettings.php' );
$UserSettings = & new UserSettings();


/**
 * Includes:
 */
require_once( dirname(__FILE__).'/_functions_template.php' );    // function to be called from templates
require_once( dirname(__FILE__).'/_functions_xmlrpc.php' );
require_once( dirname(__FILE__).'/_functions_xmlrpcs.php' );
require_once( dirname(__FILE__).'/_class_blog.php' );
require_once( dirname(__FILE__).'/_class_itemlist.php' );
require_once( dirname(__FILE__).'/_class_itemcache.php' );
require_once( dirname(__FILE__).'/_class_commentlist.php' );
require_once( dirname(__FILE__).'/_class_archivelist.php' );

require_once( dirname(__FILE__).'/_class_dataobjectcache.php' );
// Object caches init:
$GroupCache = & new DataObjectCache( 'Group', true, $tablegroups, 'grp_', 'grp_ID' );
$BlogCache = & new BlogCache();
$ItemCache = & new ItemCache();

require_once( dirname(__FILE__).'/_class_calendar.php' );
require_once( dirname(__FILE__).'/_functions_hitlogs.php' );     // referer logging
require_once( dirname(__FILE__).'/_functions_forms.php' );
require_once dirname(__FILE__).'/lib/_swfcharts.php';

/**
 * Plug-ins init:
 */
require_once( dirname(__FILE__).'/_class_plugins.php' );
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
$Debuglog->add('default_locale from conf: '.$default_locale);

locale_overwritefromDB();
$Debuglog->add('default_locale from DB: '.$default_locale);

$default_locale = locale_from_httpaccept(); // set default locale by autodetect
$Debuglog->add('default_locale from HTTP_ACCEPT: '.$default_locale);

// Activate default locale:
locale_activate( $default_locale );


/**
 * Login procedure:
 */
if( !isset($login_required) ) $login_required = false;
if( $error = veriflog( $login_required ) )
{ // Login failed:
	require( dirname(__FILE__).'/'.$core_dirout.$htsrv_subdir.'login.php' );
}

// Update the active session for the current user:
$Debuglog->add('Updating the active session for the current user');
online_user_update();


/**
 * User locale selection:
 */
if( is_logged_in() && $current_User->get('locale') != $default_locale )
{ // change locale to users preference
	$default_locale = $current_User->get('locale');
	locale_activate( $default_locale );
	$Debuglog->add('default_locale from user profile: '.$default_locale);
}


/**
 * Hit type - determines if hit will be logged and/or increase view count for Items
 *
 * Possible values are:
 * - 'badchar' : referer contains junk or spam : no logging, no counting
 * - 'reload' : page is reloaded : no logging, no counting
 * - 'robot' : page is loaded by a robot: log but don't count view
 * - 'blacklist' (should be 'hidden') : we want to hide the referer, but we count the hit : log & count
 * - 'rss' : RSS feed : log & count
 * - 'invalid' : normal without a referer : log & count
 * - 'search' : referer is a search engine : log & count
 * - 'no' : normal with referer (default) : log & count
 * - 'preview' : preview mode : no logging, no counting
 * - 'already_logged' : this hit has already been logged : no relogging, no recounting
 *
 * @global string $hit_type
 */
$hit_type = filter_hit();


/**
 * Load hacks file if it exists
 */
@include_once( dirname(__FILE__) . '/../conf/hacks.php' );
?>