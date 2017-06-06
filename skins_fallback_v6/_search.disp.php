<?php
/**
 * This is the template that displays the search form for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$params = array_merge( array(
		'pagination'               => array(),
		'search_class'             => 'extended_search_form',
		'search_input_before'      => '',
		'search_input_after'       => '',
		'search_submit_before'     => '',
		'search_submit_after'      => '',
		'search_author'             => true,
		'search_content_age'        => true,
		'search_group_before'       => '<div class="form-inline">',
		'search_group_after'        => '</div>',
		'search_group_label_before' => '<div class="form-group"><label for="$for$">',
		'search_group_label_after'  => ':</label> ',
		'search_group_field_before' => '',
		'search_group_field_after'  => '</div> ',
		'search_use_editor'        => false,
		'search_author_format'     => 'avatar_name',
		'search_cell_author_start' => '<div class="search_info dimmed">',
		'search_cell_author_end'   => '</div>',
		'search_date_format'       => locale_datefmt(),
	), $params );

// ------------------------ START OF SEARCH FORM WIDGET ------------------------
skin_widget( array(
		// CODE for the widget:
		'widget' => 'coll_search_form',
		// Optional display params
		'block_start'          => '<div class="evo_widget $wi_class$">',
		'block_end'            => '</div>',
		'block_display_title'  => false,
		'disp_search_options'  => 0,
		'search_class'         => $params['search_class'],
		'search_input_before'  => $params['search_input_before'],
		'search_input_after'   => $params['search_input_after'],
		'search_submit_before' => $params['search_submit_before'],
		'search_submit_after'  => $params['search_submit_after'],
		'search_content_age'        => $params['search_content_age'],
		'search_author'             => $params['search_author'],
		'search_group_before'       => $params['search_group_before'],
		'search_group_after'        => $params['search_group_after'],
		'search_group_label_before' => $params['search_group_label_before'],
		'search_group_label_after'  => $params['search_group_label_after'],
		'search_group_field_before' => $params['search_group_field_before'],
		'search_group_field_after'  => $params['search_group_field_after'],
		'use_search_disp'      => 1,
	) );
// ------------------------- END OF SEARCH FORM WIDGET -------------------------

// Perform search (after having displayed the first part of the page) & display results:
search_result_block( array(
		'pagination'        => $params['pagination'],
		'use_editor'        => $params['search_use_editor'],
		'author_format'     => $params['search_author_format'],
		'cell_author_start' => $params['search_cell_author_start'],
		'cell_author_end'   => $params['search_cell_author_end'],
		'date_format'       => $params['search_date_format'],
	) );

?>