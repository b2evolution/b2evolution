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

$params = array_merge( array(
		'pagination'           => array(),
		'search_class'         => 'extended_search_form',
		'search_input_before'  => '',
		'search_input_after'   => '',
		'search_submit_before' => '',
		'search_submit_after'  => '',
	), $params );

// --------------------------------- START OF COMMON LINKS --------------------------------
skin_widget( array(
		// CODE for the widget:
		'widget' => 'coll_search_form',
		// Optional display params
		'block_start'          => '',
		'block_end'            => '',
		'block_display_title'  => false,
		'disp_search_options'  => 0,
		'search_class'         => $params['search_class'],
		'search_input_before'  => $params['search_input_before'],
		'search_input_after'   => $params['search_input_after'],
		'search_submit_before' => $params['search_submit_before'],
		'search_submit_after'  => $params['search_submit_after'],
		'use_search_disp'      => 1,
	) );
// ---------------------------------- END OF COMMON LINKS ---------------------------------

// Display the search result
search_result_block( array(
		'pagination' => $params['pagination']
	) );

?>