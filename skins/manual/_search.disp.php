<?php
/**
 * This is the template that displays the search form for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage manual
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
		'disp_search_options' => 0,
		'search_class' => 'extended_search_form',
		'use_search_disp' => 1,
	) );
// ---------------------------------- END OF COMMON LINKS ---------------------------------

// Display the search result
search_result_block( array(
		'use_editor'        => true,
		'author_format'     => 'login',
		'cell_author_start' => '<p class="small text-muted">',
		'cell_author_end'   => '</p>',
		'date_format'       => 'F jS, Y',
	) );

?>