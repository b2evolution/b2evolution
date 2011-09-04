<?php
/**
 * This is the template that displays the 404 disp content
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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


echo '<div class="error_additional_content">';
// --------------------------------- START OF CLOUD TAG --------------------------------
// Call the coll_search_form widget:
skin_widget( array(
		// CODE for the widget:
		'widget' => 'coll_tag_cloud',
		// Optional display params:
		'block_start' => '<div class="tag_cloud">',
		'block_end' => '</div>',
		'block_title_start' => '<h2>',
		'block_title_end' => '</h2>',
	) );
// ---------------------------------- END OF CLOUD TAG ---------------------------------
echo '</div>';


/*
 * $Log$
 * Revision 1.5  2011/09/04 22:13:24  fplanque
 * copyright 2011
 *
 * Revision 1.4  2010/02/08 17:56:10  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.3  2009/03/08 23:57:52  fplanque
 * 2009
 *
 * Revision 1.2  2008/03/23 23:40:42  fplanque
 * no message
 *
 * Revision 1.1  2008/03/21 17:41:56  fplanque
 * custom 404 pages
 *
 */
?>