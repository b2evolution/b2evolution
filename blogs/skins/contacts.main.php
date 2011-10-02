<?php
/**
 * This file is the template that includes required css files to display contacts
 * 
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * 
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $htsrv_url;

add_js_headline( "// Paths used by JS functions:
		var bgxy_expand = '".get_icon( 'expand', 'xy' )."';
		var bgxy_collapse = '".get_icon( 'collapse', 'xy' )."';
		var htsrv_url = '$htsrv_url';" );

// Require results.css to display contact query results in a table
require_css( 'results.css' );

require $ads_current_skin_path.'index.main.php';

/*
 * $Log$
 * Revision 1.3  2011/10/02 12:38:33  efy-yurybakh
 * fix sprite icons
 *
 * Revision 1.2  2011/09/04 22:13:24  fplanque
 * copyright 2011
 *
 * Revision 1.1  2011/08/11 09:05:10  efy-asimo
 * Messaging in front office
 *
 */
?>
