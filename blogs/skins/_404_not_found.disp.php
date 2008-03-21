<?php
/**
 * This is the template that displays the 404 disp content
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $disp_detail, $baseurl, $app_name;

echo '<div class="error_404">';

echo '<h2>404 Not Found</h2>';

echo '<p><a href="'.$baseurl.'">'.$app_name.'</a> cannot resolve the requested URL.</p>';

// You may use this to further customize this page:
// echo $disp_detail;

echo '</div>';

/*
 * $Log$
 * Revision 1.1  2008/03/21 17:41:56  fplanque
 * custom 404 pages
 *
 */
?>