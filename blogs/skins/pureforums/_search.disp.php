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
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage pureforums
 *
 * @version $Id: _search.disp.php 7043 2014-07-02 08:35:45Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $MainList;

echo '<table class="forums_table topics_table" cellspacing="0" cellpadding="0">';

// --------------------------------- START OF COMMON LINKS --------------------------------
skin_widget( array(
		// CODE for the widget:
		'widget' => 'coll_search_form',
		// Optional display params
		'block_start' => '<tr class="ft_search"><td colspan="4">',
		'block_end' => '</td></tr>',
		'block_display_title' => false,
		'disp_search_options' => 0,
		'use_search_disp' => 1,
		'button' => T_('Search')
	) );
// ---------------------------------- END OF COMMON LINKS ---------------------------------

// Display message if no post:
display_if_empty( array(
				'before'      => '<p class="msg_nothing" style="margin: 2em 0">',
				'after'       => '</p>',
				'msg_empty'   => T_('Sorry, we could not find anything matching your request, please try to broaden your search.'),
			) );

if( isset( $MainList ) && $MainList->result_num_rows > 0 )
{

// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
mainlist_page_links( array(
		'block_start' => '<tr class="panel white"><td colspan="4"><div class="navigation">',
		'block_end' => '</div></td></tr>',
		'page_current_template' => '<strong class="current_page">$page_num$</strong>',
		'page_item_before'      => '',
		'page_item_after'       => '',
		'prev_text'             => T_('Previous'),
		'next_text'             => T_('Next'),
		'prev_class'            => 'prev',
		'next_class'            => 'next',
	) );
// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------

// --------------------------------- START OF POSTS -------------------------------------
while( mainlist_get_item() )
{	// For each blog post, do everything below up to the closing curly brace "}"

	// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
	skin_include( '_item_list.inc.php', array(
			'content_mode'         => 'auto', // 'auto' will auto select depending on $disp-detail
			'image_size'           => 'fit-400x320',
			'display_column_forum' => true,
			'item_link_type'       => 'permalink', // Use 'permalink' to display title of all posts as links (used especially for intro-cat posts)
		) );
	// ----------------------------END ITEM BLOCK  ----------------------------

} // ---------------------------------- END OF POSTS ------------------------------------

// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
mainlist_page_links( array(
		'block_start' => '<tr class="panel white"><td colspan="4"><div class="navigation">',
		'block_end' => '</div></td></tr>',
		'page_current_template' => '<strong class="current_page">$page_num$</strong>',
		'page_item_before'      => '',
		'page_item_after'       => '',
		'prev_text'             => T_('Previous'),
		'next_text'             => T_('Next'),
		'prev_class'            => 'prev',
		'next_class'            => 'next',
	) );
// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------

}

echo '</table>';
?>