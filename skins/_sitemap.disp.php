<?php
/**
 * This is the template that displays the site map (the real one, not the XML thing) for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=postidx
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Note: this is a very imperfect sitemap, but it's a start :)

echo '<h3>'.T_('Common links').'</h3>';
// --------------------------------- START OF COMMON LINKS --------------------------------
skin_widget( array(
		// CODE for the widget:
		'widget' => 'coll_common_links',
		// Optional display params
		'block_start' => '',
		'block_end' => '',
		'block_display_title' => false,
		'show_recently' => 1,
		'show_postidx' => 0,
		'show_archives' => 1,
		'show_categories' => 0,
		'show_mediaidx' => 1,
		'show_latestcomments' => 1,
		'show_owneruserinfo' => 1,
		'show_ownercontact' => 1,
		'show_sitemap' => 0,
	) );
// ---------------------------------- END OF COMMON LINKS ---------------------------------


echo '<h3>'.T_('Pages').'</h3>';
// --------------------------------- START OF PAGE LIST --------------------------------
skin_widget( array(
		// CODE for the widget:
		'widget' => 'coll_page_list',
		// Optional display params
		'block_start' => '',
		'block_end' => '',
		'block_display_title' => false,
		'order_by' => 'title',
		'order_dir' => 'ASC',
		'limit' => NULL,
	) );
// ---------------------------------- END OF PAGE LIST ---------------------------------


echo '<h3>'.T_('Categories').'</h3>';
// --------------------------------- START OF CATEGORY LIST --------------------------------
skin_widget( array(
		// CODE for the widget:
		'widget' => 'coll_category_list',
		// Optional display params
		'block_start' => '',
		'block_end' => '',
		'block_display_title' => false,
	) );
// ---------------------------------- END OF CATEGORY LIST ---------------------------------


echo '<h3>'.T_('Posts').'</h3>';
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
		'limit' => NULL,
	) );
// ---------------------------------- END OF POST LIST ---------------------------------

?>