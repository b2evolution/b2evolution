<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file initializes everythin BUT the blog!
 * It is usefull when you want to do very customized templates!
 */
require_once (dirname(__FILE__).'/../conf/b2evo_config.php');
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


timer_start();

if( $use_gzipcompression && extension_loaded('zlib') )
{	// gzipping the output of the script
	ob_start( 'ob_gzhandler' );
}

// Connecting to the db:
dbconnect();

// Getting settings from db
$archive_mode = get_settings('archive_mode');
$time_difference = get_settings('time_difference');

?>