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
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Note: this is a very imperfect sitemap, but it's a start :)

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
	) );
// ---------------------------------- END OF COMMON LINKS ---------------------------------

// Display message if no post:
display_if_empty( array(
				'before'      => '<p class="msg_nothing" style="margin: 2em 0">',
				'after'       => '</p>',
				'msg_empty'   => T_('Sorry, we could not find anything matching your request, please try to broaden your search.'),
			) );

// --------------------------------- START OF POSTS -------------------------------------
while( mainlist_get_item() )
{	// For each blog post, do everything below up to the closing curly brace "}"

	// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
	skin_include( '_item_block.inc.php', array(
			'content_mode' => 'auto',		// 'auto' will auto select depending on $disp-detail
			'image_size'	 =>	'fit-400x320',
		) );
	// ----------------------------END ITEM BLOCK  ----------------------------

} // ---------------------------------- END OF POSTS ------------------------------------



/*
 * $Log$
 * Revision 1.2  2010/02/08 17:56:14  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.1  2009/12/22 23:13:39  fplanque
 * Skins v4, step 1:
 * Added new disp modes
 * Hooks for plugin disp modes
 * Enhanced menu widgets (BIG TIME! :)
 *
 */
?>