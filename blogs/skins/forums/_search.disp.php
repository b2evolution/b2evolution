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
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $MainList;

// --------------------------------- START OF COMMON LINKS --------------------------------
skin_widget( array(
		// CODE for the widget:
		'widget' => 'coll_search_form',
		// Optional display params
		'block_start' => '',
		'block_end' => '',
		'block_display_title' => false,
		'disp_search_options' => 1,
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
		'block_start' => '<div class="navigation">',
		'block_end' => '</div>',
		'prev_text' => '&lt;&lt;',
		'next_text' => '&gt;&gt;',
	) );
// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------

?>
<table class="bForums bPosts" width="100%" cellspacing="1" cellpadding="2" border="0">
	<tr>
		<th>&nbsp;</th>
		<th><?php echo T_('Forum'); ?></th>
		<th><?php echo T_('Topics'); ?></th>
		<th width="70"><?php echo T_('Replies'); ?></th>
		<th width="100"><?php echo T_('Author'); ?></th>
		<th width="160"><?php echo T_('Last Post'); ?></th>
	</tr>
<?php
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

?>
</table>
<?php


// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
mainlist_page_links( array(
		'block_start' => '<div class="navigation">',
		'block_end' => '</div>',
		'prev_text' => '&lt;&lt;',
		'next_text' => '&gt;&gt;',
	) );
// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------

}

?>