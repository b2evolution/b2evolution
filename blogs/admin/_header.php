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
$login_required = true;
require_once(dirname(__FILE__)."/$b2inc/_main.php");

param( 'blog', 'integer', $default_to_blog, true );
if( $blog != '' ) 
	get_blogparams();

param( 'mode', 'string', '' );		// Sidebar, bookmarklet
?>