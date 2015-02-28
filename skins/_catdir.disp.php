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
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
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

?>