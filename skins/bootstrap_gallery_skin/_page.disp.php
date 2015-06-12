<?php
/**
 * This is the template that displays a page for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?p=123
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_gallery_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $single_Item;

// Display message if no post:
display_if_empty();

if( isset( $single_Item ) )
{ // Use Item that already is defined above
	$Item = & $single_Item;
}
else
{ // Get next Item object
	$Item = & mainlist_get_item();
}

if( $Item )
{
	// ---------------------- ITEM BLOCK INCLUDED HERE ------------------------
	skin_include( '_item_block.inc.php', array(
			'content_mode'  => 'full', // We want regular "full" content, even in category browsing: i-e no excerpt or thumbnail
		) );
	// ----------------------------END ITEM BLOCK  ----------------------------
}

?>