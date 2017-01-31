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
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
mainlist_page_links( array(
		'block_start'           => '<div class="center"><ul class="pagination">',
		'block_end'             => '</ul></div>',
		'page_item_before'      => '<li>',
		'page_item_after'       => '</li>',
		'page_item_current_before' => '<li class="active">',
		'page_item_current_after'  => '</li>',
		'page_current_template' => '<span>$page_num$</span>',
		'prev_text'             => '<i class="fa fa-angle-double-left"></i>',
		'next_text'             => '<i class="fa fa-angle-double-right"></i>',
	) );
// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------

global $cat;

// Get ID of single selected category:
$single_cat_ID = intval( $cat );
if( $single_cat_ID )
{
$ChapterCache = & get_ChapterCache();
$chapters = $ChapterCache->get_chapters( $Blog->ID, $single_cat_ID, true );

	if( count( $chapters ) > 0 )
	{ // If category is found

		echo '<section class="subcategories_section">';
		echo '<h3 class="subcategories_section__title">' . T_( 'Subcategories' ) . '</h3>';
		$section_is_started = false;
		
		foreach( $chapters as $Chapter )
		{ // Loop through categories:
		
			// Simple category with posts
			$chapters_children = array( $Chapter );

			if( ! $section_is_started )
			{
				$section_is_started = true;
				echo '<section class="row">';
			}

			foreach( $chapters_children as $Chapter )
			{ // Loop through categories:
				echo '<div class="col-md-3">';
				echo '<div class="category-item">';
				echo '<a href="' . $Chapter->get_permanent_url() . '" class="subcat subcat_' . $Chapter->dget( 'ID' ) . '">' . $Chapter->dget( 'name' ) . '</a>';
				echo '</div>';
				echo '</div>';
				
				if( $Chapter->dget( 'description' ) != '' )
				{
					echo '<br /><span class="ft_desc">'.$Chapter->dget( 'description' ).'</span>';
				}
			}
		} // End of categories loop.
		if( $section_is_started )
		{
			echo '</section>';
			echo '</section><div class="clearfix"></div>';
		}
	}
}

	
// --------------------------------- START OF POSTS -------------------------------------
// Display message if no post:
display_if_empty();
echo '<section class="row">';
while( mainlist_get_item() )
{ // For each blog post, do everything below up to the closing curly brace "}"

	// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
	skin_include( '_item_block.inc.php', array_merge( array(
			'content_mode' => 'auto', // 'auto' will auto select depending on $disp-detail
		), $params ) );
	// ----------------------------END ITEM BLOCK  ----------------------------

}
echo '<//section>';
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