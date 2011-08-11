<?php
/**
 * This file is the template that includes required css files to display messages
 * 
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 * 
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $htsrv_url;

add_js_headline( "// Paths used by JS functions:
		var imgpath_expand = '".get_icon( 'expand', 'url' )."';
		var imgpath_collapse = '".get_icon( 'collapse', 'url' )."';
		var htsrv_url = '$htsrv_url';" );

// Require results.css to display message query results in a table
require_css( 'results.css' );

require $ads_current_skin_path.'index.main.php';

/*
 * $Log$
 * Revision 1.1  2011/08/11 09:05:10  efy-asimo
 * Messaging in front office
 *
 */
?>