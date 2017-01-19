<?php
/**
 * This is the template that displays the front page of a collection (when front page enabled)
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in a *.main.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// ------------------ "Front Page Main Area" CONTAINER EMBEDDED HERE -------------------
// Display container and contents:
/* This is regular layout of disp=front
skin_container( NT_('Front Page Main Area'), array(
		// The following params will be used as defaults for widgets included in this container:
		'author_link_text'        => $params['author_link_text'],
		'featured_intro_before'   => $params['featured_intro_before'],
		'featured_intro_after'    => $params['featured_intro_after'],
		'block_start'             => $params['front_block_start'],
		'block_end'               => $params['front_block_end'],
		'block_first_title_start' => $params['front_block_first_title_start'],
		'block_first_title_end'   => $params['front_block_first_title_end'],
		'block_title_start'       => $params['front_block_title_start'],
		'block_title_end'         => $params['front_block_title_end'],
	) );
// --------------------- END OF "Front Page Main Area" CONTAINER -----------------------
*/
/*
// --------------------------------- START OF CATEGORY LIST --------------------------------
skin_widget( array(
		// CODE for the widget:
		'widget'              => 'coll_category_list',
		
		// Category items layout
		'block_start'         => '<div class="evo_widget $wi_class$">',
			'item_start' 	      => '<div class="category-item col-md-3">',
			'item_end' 	 		  => '</div>',
		'block_end'           => '</div>',
		
		'block_display_title' => true,
		'level' 			  => 1, 	// Display only main categories
		'option_all' 		  => false, // DO NOT display "All"
	) );
// ---------------------------------- END OF CATEGORY LIST ---------------------------------

global $cat, $disp;
// Get only root categories of this blog
$ChapterCache = & get_ChapterCache();
$Chapters = $ChapterCache->get_chapters( $Blog->ID, $cat, true );

	if( count( $Chapters ) > 0 ) {
		foreach( $Chapters as $root_Chapter )
		{ // Loop through categories:Chapter...
			$count_post = get_postcount_in_category( $root_Chapter->ID );
			if ( $count_post > 0 ) {
				echo '<div class="category-item' . $root_Chapter->get('ID') . '"><a href="' . $root_Chapter->get_permanent_url() . '">' . $root_Chapter->dget('name') . ' ' . $root_Chapter->get('ID') . '</a></div>';
			}
		}
	}

// function get_post_IDs_by_cat_ID($category_id)
// {
	// global $DB;
	// count all posts awaiting for moderation in the required blogs, group by blog/post_status/author_level
	// $SQL = new SQL();
	// $SQL->SELECT( 'post_ID' );
	// $SQL->FROM( 'T_items__item' );
	// $SQL->WHERE( 'post_main_cat_ID = ' . $category_id );
	// $SQL->GROUP_BY( 'post_datemodified' );
	// $blog_posts = $DB->get_results( $SQL->get() );
	// $blog_posts_array = array();
	// foreach( $blog_posts as $blog_post) {
		// $blog_posts_array[] = $blog_post;
	// }
	
	// return $blog_posts_array;
// }

function get_post_IDs_by_cat_ID($category_id)
{
	global $DB;
	// count all posts awaiting for moderation in the required blogs, group by blog/post_status/author_level
	$SQL = new SQL();
	$SQL->SELECT( 'post_ID' );
	$SQL->FROM( 'T_items__item' );
	$SQL->WHERE( 'post_main_cat_ID = ' . $category_id );
	$SQL->GROUP_BY( 'post_datemodified' );
	$SQL->LIMIT( 1 );
	$blog_posts = $DB->get_results( $SQL->get() );
	$blog_posts_array = array();
	foreach( $blog_posts as $blog_post) {
		$blog_posts_array[] = $blog_post;
	}
	
	return $blog_posts_array;
}

// $Item = & $ItemCache->get_by_ID( $post_ID, false, false );
var_dump( get_post_IDs_by_cat_ID(5) );
echo get_post_IDs_by_cat_ID(5);
	
// Display message if no post:
display_if_empty();

echo '<div class="row">';
	while( mainlist_get_item() )
	{ // For each blog post, do everything below up to the closing curly brace "}"

		// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
		skin_include( '_item_block.inc.php', array_merge( array(
				'content_mode' => 'excerpt', // 'auto' will auto select depending on $disp-detail
			), $params ) );
		// ----------------------------END ITEM BLOCK  ----------------------------

	} // ---------------------------------- END OF POSTS ------------------------------------
echo '</div>';

*/

$params = array_merge( array(
	'author_link_text'              => 'auto',
	'featured_intro_before'         => '',
	'featured_intro_after'          => '',
	'front_block_start'             => '<div class="evo_widget $wi_class$">',
	'front_block_end'               => '</div>',
	'front_block_first_title_start' => '<h3>',
	'front_block_first_title_end'   => '</h3>',
	'front_block_title_start'       => '<h3>',
	'front_block_title_end'         => '</h3>',
), $params );

while( mainlist_get_item() )
{ // For each blog post, do everything below up to the closing curly brace "}"
	skin_include( '_item_block.inc.php', array_merge( array(
			'content_mode' => 'excerpt', // 'auto' will auto select depending on $disp-detail
		), $params ) );
} // ---------------------------------- END OF POSTS ------------------------------------

?>
<div class="evo_container evo_container__front_page_secondary">
<?php // ------------------ "Front Page Secondary Area" CONTAINER EMBEDDED HERE -------------------
skin_container( NT_('Front Page Secondary Area'), array(
		// The following params will be used as defaults for widgets included in this container:
		'block_start'             => $params['front_block_start'],
		'block_end'               => $params['front_block_end'],
		'block_first_title_start' => $params['front_block_first_title_start'],
		'block_first_title_end'   => $params['front_block_first_title_end'],
		'block_title_start'       => $params['front_block_title_start'],
		'block_title_end'         => $params['front_block_title_end'],
	) );
// --------------------- END OF "Front Page Secondary Area" CONTAINER ----------------------- ?>
</div>