<?php
/**
 * This is the template that displays the post index for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=postidx
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// --------------------------------- START OF POST LIST --------------------------------
skin_widget( array(
		// CODE for the widget:
		'widget' => 'coll_post_list',
		// Optional display params
		'block_start' => '',
		'block_end' => '',
		'block_display_title' => false,
		'order_by' => 'title',
		'order_dir' => 'ASC',
		'limit' => 1000,
		'page' => param( 'coll_post_list_paged', 'integer', 1 ),
	) );
// ---------------------------------- END OF POST LIST ---------------------------------


/*
 * $Log$
 * Revision 1.5  2011/09/04 22:13:24  fplanque
 * copyright 2011
 *
 * Revision 1.4  2011/05/31 14:20:28  efy-asimo
 * paged nav on ?disp=postidx
 *
 * Revision 1.3  2010/11/08 16:13:52  efy-asimo
 * disp=postidx shows 1000 posts
 *
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