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
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $thumbnail_sizes;

if( empty( $params ) )
{ // Initialize array with params
	$params = array();
}

// Merge the params from current skin
$params = array_merge( array(
		'mediaidx_thumb_size' => 'fit-80x80'
	), $params );

$photocell_styles = '';
if( isset( $thumbnail_sizes[ $params['mediaidx_thumb_size'] ] ) )
{
	$photocell_styles = ' style="width:'.$thumbnail_sizes[ $params['mediaidx_thumb_size'] ][1].'px;'
		.'height:'.$thumbnail_sizes[ $params['mediaidx_thumb_size'] ][2].'px"';
}

// --------------------------------- START OF MEDIA INDEX --------------------------------
skin_widget( array(
		// CODE for the widget:
		'widget' => 'coll_media_index',
		// Optional display params
		'block_start' => '',
		'block_end' => '',
		'block_display_title' => false,
		'thumb_size' => $params['mediaidx_thumb_size'],
		'thumb_layout' => 'grid',
		'grid_start' => '<div class="image_index">',
		'grid_end' => '</div>',
		'grid_nb_cols' => 8,
		'grid_colstart' => '',
		'grid_colend' => '',
		'grid_cellstart' => '<div><span'.$photocell_styles.'>',
		'grid_cellend' => '</span></div>',
		'order_by' => $Blog->get_setting('orderby'),
		'order_dir' => $Blog->get_setting('orderdir'),
		'limit' => 1000,
	) );
// ---------------------------------- END OF MEDIA INDEX ---------------------------------

?>