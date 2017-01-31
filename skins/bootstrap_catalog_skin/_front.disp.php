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

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// --------------------------------- START OF CATEGORY LIST --------------------------------
$ChapterCache = & get_ChapterCache();
$chapters = $ChapterCache->get_chapters( $Blog->ID );

if( count( $chapters ) > 0 )
{ // If category is found

echo '<section class="maincategories_section">';
	echo '<h3 class="maincategories_section__title">' . T_( 'Categories' ) . '</h3>';
	$section_is_started = false;
	
	echo '<div class="row">';
	foreach( $chapters as $root_Chapter )
	{ // Loop through categories:
		echo '<div class="col-md-3">';
		echo '<div class="category-item">';
		echo '<a href="' . $root_Chapter->get_permanent_url() . '" class="rootcat rootcat_' . $root_Chapter->dget( 'ID' ) . '">' . $root_Chapter->dget( 'name' ) . '</a>';
		echo '</div>';
		echo '</div>';
	} // End of categories loop.
	echo '</div>';
echo '</section>';
}
// ---------------------------------- END OF CATEGORY LIST ---------------------------------


// ---------------------------------- START OF POSTS ----------------------------------

echo '<h3>' . T_( 'Articles on sale' ) . '</h3>';
echo '<section class="row">';
while( mainlist_get_item() )
{ // For each blog post, do everything below up to the closing curly brace "}"
	skin_include( '_item_block.inc.php', array_merge( array(
			'content_mode' => 'excerpt', // 'auto' will auto select depending on $disp-detail
		), $params ) );
}
echo '</section>';
// ---------------------------------- END OF POSTS ------------------------------------


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