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
				'page_current_template' => ' ',
				'block_start' => '<div class="page_navigation center">',
				'next_start'  => '<div class="next_nav_section">',
				'next_text' => '<span class="pb_icon next" title="'.T_('Next').'"></span>',
				'next_no_item' => get_icon( 'pixel', 'imgtag', array( 'size' => array( 29, 29 ), 'xy' => array( 13, 13 ), 'class' => 'no_nav' ) ),
				'next_end'    => '</div>',
				'prev_start'  => '<div class="prev_nav_section">',
				'prev_text' => '<span class="pb_icon prev" title="'.T_('Previous').'"></span>',
				'prev_no_item' => '',
				'prev_end'    => '</div>',
				'block_end'   => '</div>',
	) );
// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------

// --------------------------------- START OF POSTS -------------------------------------
// Display message if no post:
display_if_empty();

while( mainlist_get_item() )
{ // For each blog post, do everything below up to the closing curly brace "}"

	// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
	skin_include( '_item_block.inc.php', array_merge( array(
			'content_mode' => 'auto', // 'auto' will auto select depending on $disp-detail
		), $params ) );
	// ----------------------------END ITEM BLOCK  ----------------------------

} // ---------------------------------- END OF POSTS ------------------------------------

// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
mainlist_page_links( array(
				'page_current_template' => ' ',
				'block_start' => '<div class="page_navigation center">',
				'next_start'  => '<div class="next_nav_section">',
				'next_text' => '<span class="pb_icon next" title="'.T_('Next').'"></span>',
				'next_no_item' => get_icon( 'pixel', 'imgtag', array( 'size' => array( 29, 29 ), 'xy' => array( 13, 13 ), 'class' => 'no_nav' ) ),
				'next_end'    => '</div>',
				'prev_start'  => '<div class="prev_nav_section">',
				'prev_text' => '<span class="pb_icon prev" title="'.T_('Previous').'"></span>',
				'prev_no_item' => '',
				'prev_end'    => '</div>',
				'block_end'   => '</div>',
	) );
// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------

?>