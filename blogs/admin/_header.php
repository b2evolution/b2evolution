<?php

require_once(dirname(__FILE__)."/../conf/b2evo_config.php");
require_once(dirname(__FILE__)."/../conf/b2evo_admin.php");
require_once(dirname(__FILE__)."/$b2inc/_vars.php");
require_once(dirname(__FILE__)."/$b2inc/_functions_locale.php"); // locale related functions
require_once(dirname(__FILE__)."/$b2inc/_functions_template.php");
require_once(dirname(__FILE__)."/$b2inc/_functions.php");
require_once(dirname(__FILE__)."/$b2inc/_functions_xmlrpc.php");
require_once(dirname(__FILE__)."/$b2inc/_functions_xmlrpcs.php");

if (!isset($debug))		$debug=0;

timer_start();

if( $use_gzipcompression && extension_loaded('zlib') )
{	// gzipping the output of the script
	ob_start( 'ob_gzhandler' );
}

// Connecting to the db:
dbconnect();

if(!veriflog())
{	// If user is not loggued in:
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");
	require(dirname(__FILE__).'/b2login.php');
	exit();
}

get_currentuserinfo();

$request = " SELECT * FROM $tablesettings ";
$result = mysql_query($request);
$querycount++;
while($row = mysql_fetch_object($result)) 
{
	$posts_per_page=$row->posts_per_page;
	$what_to_show=$row->what_to_show;
	$archive_mode=$row->archive_mode;
	$time_difference=$row->time_difference;
	$autobr=$row->AutoBR;
}

// let's deactivate quicktags on IE Mac and Lynx, because they don't work there.
if (($is_macIE) || ($is_lynx))
	$use_quicktags=0;

$b2varstoreset = array( 'profile','standalone','redirect','redirect_url','a','popuptitle','popupurl','text', 'trackback', 'pingback');
for ($i=0; $i<count($b2varstoreset); $i += 1) {
	$b2var = $b2varstoreset[$i];
	if (!isset($$b2var)) {
		if (empty($HTTP_POST_VARS["$b2var"])) {
			if (empty($HTTP_GET_VARS["$b2var"])) {
				$$b2var = '';
			} else {
				$$b2var = $HTTP_GET_VARS["$b2var"];
			}
		} else {
			$$b2var = $HTTP_POST_VARS["$b2var"];
		}
	}
}

set_param( 'blog', 'integer', $default_to_blog, true );

if( $blog != '' ) 
	get_blogparams();

?>