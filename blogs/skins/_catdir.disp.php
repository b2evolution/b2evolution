<?php
/**
 * This is the template that displays the category directory for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display the archive directory, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=catdir
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// --------------------------------- START OF CATEGORY LIST --------------------------------
skin_widget( array(
		// CODE for the widget:
		'widget' => 'coll_category_list',
		// Optional display params
		'block_start' => '',
		'block_end' => '',
		'block_display_title' => false,
	) );
// ---------------------------------- END OF CATEGORY LIST ---------------------------------


/*
 * $Log$
 * Revision 1.4  2007/07/01 03:55:04  fplanque
 * category plugin replaced by widget
 *
 * Revision 1.3  2007/04/26 00:11:03  fplanque
 * (c) 2007
 *
 * Revision 1.2  2007/03/18 01:39:55  fplanque
 * renamed _main.php to main.page.php to comply with 2.0 naming scheme.
 * (more to come)
 *
 * Revision 1.1  2007/03/04 21:42:49  fplanque
 * category directory / albums
 *
 */
?>