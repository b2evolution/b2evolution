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

// If not in sidebar or bookmarklet
if( ! isset($mode) ) $mode = '';

// Do the MAIN initializations:
$login_required = true;
require_once(dirname(__FILE__)."/$b2inc/_main.php");

// Getting (more) settings from db
$posts_per_page = get_settings('posts_per_page');
$time_difference = get_settings('time_difference');
$autobr = get_settings('AutoBR');

param( 'blog', 'integer', $default_to_blog, true );
if( $blog != '' ) 
	get_blogparams();

?>