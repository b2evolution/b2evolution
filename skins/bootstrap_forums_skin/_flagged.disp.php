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
 * @subpackage bootstrap_forums_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Breadcrumbs
skin_widget( array(
		// CODE for the widget:
		'widget' => 'breadcrumb_path',
		// Optional display params
		'block_start'      => '<ol class="breadcrumb">',
		'block_end'        => '</ol><div class="clear"></div>',
		'separator'        => '',
		'item_mask'        => '<li><a href="$url$">$title$</a></li>',
		'item_active_mask' => '<li class="active">$title$</li>',
		'suffix_text'      => T_('Flagged topics'),
	) );

// ---------------------------------- START OF POSTS ------------------------------------
?>
<div class="panel panel-default forums_list">
	<section class="table table-hover">
<?php

if( $MainList->result_num_rows > 0 )
{
	while( mainlist_get_item() )
	{ // For each blog post, do everything below up to the closing curly brace "}"

		// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
		skin_include( '_item_list.inc.php', array(
				'content_mode' => 'auto',		// 'auto' will auto select depending on $disp-detail
				'image_size'   => 'fit-1280x720',
			) );
		// ----------------------------END ITEM BLOCK  ----------------------------
	}
}
?>
	</section>

	<?php
		// -------------------- PREV/NEXT PAGE LINKS (POST LIST MODE) --------------------
		mainlist_page_links( array(
				'block_start'           => '<div class="panel-body comments_link__pagination"><ul class="pagination">',
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
</div>
<?php
// ---------------------------------- END OF POSTS ------------------------------------
?>