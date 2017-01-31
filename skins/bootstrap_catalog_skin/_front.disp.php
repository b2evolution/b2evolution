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