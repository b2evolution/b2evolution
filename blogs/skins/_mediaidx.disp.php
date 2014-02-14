<?php
/**
 * This is the template that displays the media index for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=arcdir
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// --------------------------------- START OF MEDIA INDEX --------------------------------
skin_widget( array(
		// CODE for the widget:
		'widget' => 'coll_media_index',
		// Optional display params
		'block_start' => '',
		'block_end' => '',
		'block_display_title' => false,
		'thumb_size' => 'fit-80x80',
		'thumb_layout' => 'grid',
		'grid_start' => '<table class="image_index" cellspacing="3">',
		'grid_end' => '</table>',
		'grid_nb_cols' => 8,
		'grid_colstart' => '<tr>',
		'grid_colend' => '</tr>',
		'grid_cellstart' => '<td>',
		'grid_cellend' => '</td>',
		'order_by' => $Blog->get_setting('orderby'),
		'order_dir' => $Blog->get_setting('orderdir'),
		'limit' => 1000,
	) );
// ---------------------------------- END OF MEDIA INDEX ---------------------------------

?>