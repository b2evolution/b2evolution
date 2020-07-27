<?php
/**
 * This is the template that displays a post in a collection
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?p=123
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// --------------------------------- START OF POSTS -------------------------------------
// Display message if no post:
display_if_empty();

if( mainlist_get_item() )
{	// For each blog post, do everything below up to the closing curly brace "}"

	// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
	skin_include( '_item_block.inc.php', array_merge( array(
			'content_mode' => 'auto',		// 'auto' will auto select depending on $disp-detail
			'image_size'   => get_skin_setting( 'main_content_image_size', 'fit-1280x720' ),
// TODO: it makes no sense so limit image_size without limiting image_sizes
			'image_sizes'  => '(max-width: 430px) 400px, (max-width: 670px) 640px, (max-width: 991px) 720px, (max-width: 1199px) 698px, 848px',
										// Note: first we handle margins 15+640+15 = 670 in the fluid domain, then we work with bootstrap breakpoints
		), $params ) );
	// ----------------------------END ITEM BLOCK  ----------------------------

} // ---------------------------------- END OF POSTS ------------------------------------

?>