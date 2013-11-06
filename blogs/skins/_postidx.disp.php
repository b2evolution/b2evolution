<?php
/**
 * This is the template that displays the post index for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=postidx
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// --------------------------------- START OF POST LIST --------------------------------
skin_widget( array(
		// CODE for the widget:
		'widget' => 'coll_post_list',
		// Optional display params
		'block_start' => '',
		'block_end' => '',
		'block_display_title' => false,
		'order_by' => 'title',
		'order_dir' => 'ASC',
		'limit' => 1000,
		'page' => param( 'coll_post_list_paged', 'integer', 1 ),
	) );
// ---------------------------------- END OF POST LIST ---------------------------------


/*
 * $Log$
 * Revision 1.6  2013/11/06 08:05:36  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>