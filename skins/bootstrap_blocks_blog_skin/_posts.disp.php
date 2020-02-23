<?php
/**
 * This is the template that displays the posts for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=posts
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ------------------------- "Item List" CONTAINER EMBEDDED HERE --------------------------
// Display container contents:
widget_container( 'item_list', array(
	// The following (optional) params will be used as defaults for widgets included in this container:
	'container_display_if_empty' => false, // If no widget, don't display container at all
	// This will enclose each widget in a block:
	'block_start'           => '<div class="evo_widget $wi_class$">',
	'block_end'             => '</div>',
	// This will enclose the title of each widget:
	'block_title_start'     => '<h3>',
	'block_title_end'       => '</h3>',
	// The following params will be used as default for widgets
	'widget_coll_item_list_pages_params' => array(
		'block_start'              => '<div class="center"><ul class="pagination">',
		'block_end'                => '</ul></div>',
		'page_item_before'         => '<li>',
		'page_item_after'          => '</li>',
		'page_item_current_before' => '<li class="active">',
		'page_item_current_after'  => '</li>',
		'page_current_template'    => '<span>$page_num$</span>',
		'prev_text'                => '<i class="fa fa-angle-double-left"></i>',
		'next_text'                => '<i class="fa fa-angle-double-right"></i>',
	)
) );
// ----------------------------- END OF "Item List" CONTAINER -----------------------------

// --------------------------------- START OF POSTS -------------------------------------
// Display message if no post:
display_if_empty();

echo '<div class="evo_tiles row">';

global $cat;
$item_template_params = array_merge( $params, array(
		'post_navigation' => 'same_category',			// Stay in the same category if Item is cross-posted
		'nav_target'      => $cat,	// for use with 'same_category' : set the category ID as nav target
		'target_blog'     => 'auto', 						// Stay in current collection if it is allowed for the Item
		// Params that would be set by master template:
		'rwd_cols'                  => 'col-xs-12 col-sm-6 col-md-6 col-lg-4',
		'evo_tile__modifiers'       => 'evo_tile__md evo_tile__grey_bg evo_tile__shadow',
		'evo_tile_image__modifiers' => '',
		'evo_tile_image__size'       => 'fit-400x320',
		'evo_tile_image__sizes'      => 'max-width: 430px) 400px, (max-width: 670px) 640px, (max-width: 767px) 720px, (max-width: 991px) 345px, (max-width: 1199px) 334px, (max-width: 1799px) 262px, 400px]',
		'evo_tile_text__modifiers'  => 'evo_tile_text__gradient',
	) );



while( $Item = & mainlist_get_item() )
{ // For each blog post, do everything below up to the closing curly brace "}"

	// Render Item by quick template:
	echo render_template_code( 'content_tiles_item', $item_template_params, array( 'Item' => $Item ) );
}

echo '</div>';

// ---------------------------------- END OF POSTS ------------------------------------

// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
mainlist_page_links( array(
		'block_start'           => '<div class="center"><ul class="pagination">',
		'block_end'             => '</ul></div>',
		'page_current_template' => '<span>$page_num$</span>',
		'page_item_before'      => '<li>',
		'page_item_after'       => '</li>',
		'page_item_current_before' => '<li class="active">',
		'page_item_current_after'  => '</li>',
		'prev_text'             => '<i class="fa fa-angle-double-left"></i>',
		'next_text'             => '<i class="fa fa-angle-double-right"></i>',
	) );
// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------

?>