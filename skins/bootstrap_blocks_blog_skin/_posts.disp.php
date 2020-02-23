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

echo '<div class="row evo_tiles evo_tiles__shadow">';
while( $Item = & mainlist_get_item() )
{ // For each blog post, do everything below up to the closing curly brace "}"
	echo '<div class="'.$Skin->get_setting( 'posts_list_block_class' ).'">';
	echo '<div class="evo_tile evo_tile__shadow">';
	echo '<a href="'.$Item->get_permanent_url().'"></a>';
	echo '<div>';
	$item_cover_image_url = $Item->get_cover_image_url( 'cover,teaser' );
	echo '<div class="evo_tile_cover"'.( $item_cover_image_url ? ' style="background-image:url('.$item_cover_image_url.')"' : '' ).'>';
	if( $item_main_Chapter = & $Item->get_main_Chapter() )
	{
		echo $item_main_Chapter->get_name();
	}
	echo '</div>'; // End of evo_tile_cover
	echo '<div class="evo_tile_text">';
		echo '<h2>'.$Item->dget( 'title' ).'</h2>';
		$Item->excerpt();
	echo '</div>'; // End of evo_tile_text
	echo '</div>';
	echo '</div>'; // End of evo_tile
	echo '</div>'; // End of Settings:posts_list_block_class
} // ---------------------------------- END OF POSTS ------------------------------------
echo '</div>';

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