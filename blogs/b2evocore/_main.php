<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file initializes everything BUT the blog!
 * It is useful when you want to do very customized templates!
 * It is also called by more complete initializers.
 */
if( isset( $main_init ) )
{	// Prevent double loading since require_once won't work in all situations
	// on windows when some subfolders have caps :(
	// (Check it out on static page generation)
	return;
}
$main_init = true;

require_once (dirname(__FILE__).'/../conf/_config.php');
require_once (dirname(__FILE__).'/_vars.php');					// sets various arrays and vars for use in b2
require_once (dirname(__FILE__).'/_functions_template.php');	// function to be called from templates
require_once (dirname(__FILE__).'/_functions.php');  
require_once (dirname(__FILE__).'/_functions_xmlrpc.php');	
require_once (dirname(__FILE__).'/_functions_xmlrpcs.php');
require_once (dirname(__FILE__).'/_class_itemlist.php');
require_once (dirname(__FILE__).'/_class_commentlist.php');
require_once (dirname(__FILE__).'/_class_archivelist.php');
require_once (dirname(__FILE__).'/_class_calendar.php');
require_once (dirname(__FILE__).'/_functions_hitlogs.php'); // referer logging
require_once (dirname(__FILE__).'/_functions_forms.php');


timer_start();


if( !isset($debug) ) $debug=0;
if( !isset($demo_mode) ) $demo_mode = 0;


if( $use_gzipcompression && extension_loaded('zlib') )
{	// gzipping the output of the script
	ob_start( 'ob_gzhandler' );
}

// Connecting to the db:
dbconnect();

// Getting settings from db
$archive_mode = get_settings('archive_mode');
$time_difference = get_settings('time_difference');
$posts_per_page = get_settings('posts_per_page');
$what_to_show = get_settings('what_to_show');
$autobr = get_settings('AutoBR');

$servertimenow = time();
$localtimenow = $servertimenow + ($time_difference * 3600);

// Login procedure:
if( !isset($login_required) ) $login_required = false;
if( $error = veriflog( $login_required ) )
{	// Login failed:
	require(dirname(__FILE__)."/$core_dirout/$htsrv_subdir/login.php");
}
?>