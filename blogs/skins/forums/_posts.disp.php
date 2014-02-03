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
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

//global $Item;

// --------------------------------- START OF POSTS -------------------------------------
global $cat, $MainList;

if( $cat > 0 )
{
?>
<div class="post_panel">
<?php
	$Skin->display_post_button( $cat );
	// BREADCRUMBS
	$Skin->display_breadcrumbs( $cat );
?>
</div>
<div class="clear"></div>
<?php
} // End of the selected category specific display

// Display message if no post:
display_if_empty();

// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
mainlist_page_links( array(
		'block_start' => '<div class="navigation_top"><div class="navigation">'.T_('Page').': ',
		'block_end' => '</div></div>',
		'prev_text' => T_('Previous'),
		'next_text' => T_('Next'),
	) );
// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------

if( isset( $MainList ) && $MainList->result_num_rows > 0 )
{
?>
<table class="bForums bPosts" width="100%" cellspacing="1" cellpadding="2" border="0">
	<tr>
		<th colspan="2"><?php echo T_('Topics'); ?></th>
		<th width="70"><?php echo T_('Replies'); ?></th>
		<th width="100"><?php echo T_('Author'); ?></th>
		<th width="160"><?php echo T_('Last Post'); ?></th>
	</tr>
<?php

// Go Grab the featured posts:
while( $Item = get_featured_Item() )
{	// We have a intro or a featured post to display:
	// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
	skin_include( '_item_list.inc.php', array(
			'feature_block' => true,
			'content_mode'  => 'auto',		// 'auto' will auto select depending on $disp-detail
			'intro_mode'    => 'normal',	// Intro posts will be displayed in normal mode
			'item_class'    => 'featured_post',
			'image_size'    => 'fit-400x320',
		) );
	// ----------------------------END ITEM BLOCK  ----------------------------
}

while( mainlist_get_item() )
{	// For each blog post, do everything below up to the closing curly brace "}"
	// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
	skin_include( '_item_list.inc.php', array(
			'content_mode' => 'auto',		// 'auto' will auto select depending on $disp-detail
			'image_size'   => 'fit-400x320',
		) );
	// ----------------------------END ITEM BLOCK  ----------------------------

} // ---------------------------------- END OF POSTS ------------------------------------
?>
</table>
<?php
}

// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
mainlist_page_links( array(
		'block_start' => '<div class="navigation">'.T_('Page').': ',
		'block_end' => '</div>',
		'prev_text' => T_('Previous'),
		'next_text' => T_('Next'),
	) );
// ------------------------- END OF PREV/NEXT PAGE LINKS -------------------------

?>