<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file initializes the backoffice!
 */

require_once(dirname(__FILE__)."/../conf/b2evo_config.php");
require_once(dirname(__FILE__)."/../conf/b2evo_admin.php");

// Do the MAIN initializations:
require_once(dirname(__FILE__)."/$b2inc/_main.php");

// If not in sidebar or bookmarklet
if( ! isset($mode) ) $mode = '';

if( ! is_loggued_in() )
{	// If user is not loggued in:
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");

	$error = T_('You must log in!');
	$redirect_to = $pathserver.'/'.$pagenow;
	require(dirname(__FILE__)."/$pathadmin_out/$pathhtsrv/login.php");
	exit();
}

// Getting (more) settings from db
$posts_per_page = get_settings('posts_per_page');
$time_difference = get_settings('time_difference');
$autobr = get_settings('AutoBR');

set_param( 'blog', 'integer', $default_to_blog, true );

if( $blog != '' ) 
	get_blogparams();


/*
 This sounds totally ridiculous:

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

*/


?>