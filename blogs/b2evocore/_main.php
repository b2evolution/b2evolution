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
{	// Prevent double loading since require_once won't work in all situations
	// on windows when some subfolders have caps :(
	// (Check it out on static page generation)
	return;
}
$main_init = true;

/**
 * Includes:
 */
require_once( dirname(__FILE__).'/../conf/_config.php' );
require_once( dirname(__FILE__).'/_class_log.php' );
$Debuglog = new Log( 'note' );
$Messages = new Log( 'error' );

require_once( dirname(__FILE__).'/_functions.php' );
timer_start();
require_once( dirname(__FILE__).'/_vars.php' );	// sets various arrays and vars for use in b2
require_once( dirname(__FILE__).'/_class_generalsettings.php' );
require_once( dirname(__FILE__).'/_class_db.php' );
require_once( dirname(__FILE__).'/_functions_template.php' );	// function to be called from templates
require_once( dirname(__FILE__).'/_functions_xmlrpc.php' );	
require_once( dirname(__FILE__).'/_functions_xmlrpcs.php' );
require_once( dirname(__FILE__).'/_class_blog.php' );
require_once( dirname(__FILE__).'/_class_itemlist.php' );
require_once( dirname(__FILE__).'/_class_itemcache.php' );
require_once( dirname(__FILE__).'/_class_commentlist.php' );
require_once( dirname(__FILE__).'/_class_archivelist.php' );
require_once( dirname(__FILE__).'/_class_dataobjectcache.php' );
require_once( dirname(__FILE__).'/_class_calendar.php' );
require_once( dirname(__FILE__).'/_functions_hitlogs.php' ); // referer logging
require_once( dirname(__FILE__).'/_functions_forms.php' );
require_once( dirname(__FILE__).'/_class_renderer.php' );
require_once( dirname(__FILE__).'/_class_toolbars.php' );


if( !$config_is_done )
{	// base config is not done.
	$error_message = 'Base configuration is not done.';
	require dirname(__FILE__).'/_conf_error.page.php';	// error & exit
}


if( !function_exists( 'gzencode' ) )
{ // when there is no function to gzip, we won't do it
	$use_gzipcompression = false;
}

if( $use_obhandler )
{ // register output buffer handler
	ob_start( 'obhandler' );
}


// Connecting to the db:
$DB = new DB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );

$Settings = & new GeneralSettings();

$servertimenow = time();
$localtimenow = $servertimenow + ($Settings->get('time_difference') * 3600);


$Debuglog->add('default_locale from conf: '.$default_locale);

locale_overwritefromDB();
$Debuglog->add('default_locale from DB: '.$default_locale);

$default_locale = locale_from_httpaccept(); // set default locale by autodetect
$Debuglog->add('default_locale from HTTP_ACCEPT: '.$default_locale);

// Activate default locale:
locale_activate( $default_locale );


// Object caches init:
$GroupCache = & new DataObjectCache( 'Group', true, $tablegroups, 'grp_', 'grp_ID' );
$BlogCache = & new BlogCache();
$ItemCache = & new ItemCache();


// Plug-ins init:
$Renderer = & new Renderer();
$Toolbars = & new Toolbars();


// Login procedure:
if( !isset($login_required) ) $login_required = false;
if( $error = veriflog( $login_required ) )
{	// Login failed:
	require(dirname(__FILE__) . "/$core_dirout/$htsrv_subdir/login.php");
}

if( is_logged_in() && $current_User->get('locale') != $default_locale )
{ // change locale to users preference
	$default_locale = $current_User->get('locale');
	locale_activate( $default_locale );
	$Debuglog->add('default_locale from user profile: '.$default_locale);
}


/**
 * check if the URI has been requested from same IP/useragent in past reloadpage_timeout seconds.
 */
$uri_reloaded = (bool)$DB->get_var("SELECT visitID FROM $tablehitlog
									WHERE	visitURL = ".$DB->quote($ReqURI)."
												AND UNIX_TIMESTAMP( visitTime ) - $localtimenow < ".(int)$Settings->get('reloadpage_timeout')."
												AND hit_remote_addr = ".$DB->quote($_SERVER['REMOTE_ADDR'])."
												AND hit_user_agent = ".$DB->quote($HTTP_USER_AGENT) );
if( $uri_reloaded )
	$Debuglog->add( 'URI-reload!' );


// Load hacks file if it exists
@include_once( dirname(__FILE__) . '/../conf/hacks.php' );
?>