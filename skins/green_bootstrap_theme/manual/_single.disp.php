<?php
/**
 * This is the template that displays a post for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?p=123
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $cat;

// Display message if no post:
display_if_empty();

if( $Item = & mainlist_get_item() )
{ // For each blog post, do everything below up to the closing curly brace "}"
	echo '<div class="evo_content_block">'; // Beginning of posts display

	if( empty( $cat ) )
	{ // Set a category as main of current Item
		$cat = $Item->main_cat_ID;

		// Display the breadcrumbs only when global $cat is empty before line above
		// Otherwise it is already displayed in header file
		skin_widget( array(
				// CODE for the widget:
				'widget' => 'breadcrumb_path',
				// Optional display params
				'block_start'      => '<ol class="breadcrumb">',
				'block_end'        => '</ol>',
				'separator'        => '',
				'item_mask'        => '<li><a href="$url$">$title$</a></li>',
				'item_active_mask' => '<li class="active">$title$</li>',
			) );
	}

	if( $Skin->get_setting( 'page_navigation' ) )
	{	// Display navigation between posts in the same category:
		// ------------------- PREV/NEXT POST LINKS (SINGLE POST MODE) -------------------
		item_prevnext_links( array(
				'block_start'     => '<ul class="pager">',
				'prev_start'      => '<li class="previous">',
				'prev_text'       => '<span aria-hidden="true">&larr;</span> $title$',
				'prev_end'        => '</li>',
				'separator'       => ' ',
				'next_start'      => '<li class="next">',
				'next_text'       => '$title$ <span aria-hidden="true">&rarr;</span>',
				'next_end'        => '</li>',
				'block_end'       => '</ul>',
				'target_blog'     => $Blog->ID,	// this forces to stay in the same blog, should the post be cross posted in multiple blogs
				'post_navigation' => 'same_category', // force to stay in the same category in this skin
				'featured'        => false, // don't include the featured posts into navigation list
			) );
		// ------------------------- END OF PREV/NEXT POST LINKS -------------------------
	}

	// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
	skin_include( '_item_block.inc.php', array_merge( array(
			'content_mode' => 'auto',		// 'auto' will auto select depending on $disp-detail
			'item_class'   => 'evo_post evo_content_block',
		), $Skin->get_template( 'disp_params' ) ) );
	// ----------------------------END ITEM BLOCK  ----------------------------
	echo '</div>'; // End of posts display
}

?>