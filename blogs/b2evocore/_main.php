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

require_once( dirname(__FILE__). '/../conf/_config.php');
require_once( dirname(__FILE__). '/_functions.php');

timer_start();

require_once( dirname(__FILE__). '/_class_db.php');
require_once( dirname(__FILE__). '/_vars.php');	// sets various arrays and vars for use in b2
require_once( dirname(__FILE__). '/_functions_template.php');	// function to be called from templates
require_once( dirname(__FILE__). '/_functions_xmlrpc.php');	
require_once( dirname(__FILE__). '/_functions_xmlrpcs.php');
require_once( dirname(__FILE__). '/_class_blog.php');
require_once( dirname(__FILE__). '/_class_itemlist.php');
require_once( dirname(__FILE__). '/_class_commentlist.php');
require_once( dirname(__FILE__). '/_class_archivelist.php');
require_once( dirname(__FILE__). '/_class_dataobjectcache.php');
require_once( dirname(__FILE__). '/_class_calendar.php');
require_once( dirname(__FILE__). '/_functions_hitlogs.php'); // referer logging
require_once( dirname(__FILE__). '/_functions_forms.php');
require_once( dirname(__FILE__). '/_functions_forms.php');
require_once( dirname(__FILE__). '/_class_renderer.php');


if( !function_exists( 'gzencode' ) )
{ // when there is no function to gzip, we won't do it
	$use_gzipcompression = false;
}

if( $use_obhandler )
{ // register output buffer handler
	ob_start( 'obhandler' );
}
/* @outforrelease
if( $use_gzipcompression && extension_loaded('zlib') )
{	// gzipping the output of the script
	ob_start( 'ob_gzhandler' );
}*/


// Connecting to the db:
dbconnect();
$DB = new DB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );

// Getting settings from db
$archive_mode = get_settings('archive_mode');
$time_difference = get_settings('time_difference');
$posts_per_page = get_settings('posts_per_page');
$what_to_show = get_settings('what_to_show');
$autobr = get_settings('AutoBR');

$servertimenow = time();
$localtimenow = $servertimenow + ($time_difference * 3600);

// Object caches init:
$GroupCache = & new DataObjectCache( 'Group', true, $tablegroups, 'grp_', 'grp_ID' );
// $BlogCache = & new DataObjectCache( 'Blog', false, $tableblogs, 'blog_', 'blog_ID' );
$ItemCache = & new DataObjectCache( 'Item', false, $tableposts, 'post_', 'ID' );

// Renderer init:
$Renderer = & new Renderer();

// Login procedure:
if( !isset($login_required) ) $login_required = false;
if( $error = veriflog( $login_required ) )
{	// Login failed:
	require(dirname(__FILE__) . "/$core_dirout/$htsrv_subdir/login.php");
}

// Load hacks file if it exists
@include_once( dirname(__FILE__) . '/../conf/hacks.php' );
?>