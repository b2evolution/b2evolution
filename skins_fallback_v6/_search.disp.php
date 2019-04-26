<?php
/**
 * This is the template that displays the search form for a blog
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$params = array_merge( array(
		'pagination'                 => array(),
		'search_class'               => 'extended_search_form',
		'search_input_before'        => '<div class="col-sm-12"><div class="input-group">',
		'search_input_after'         => '',
		'search_submit_before'       => '<span class="input-group-btn">',
		'search_submit_after'        => '</span></div></div>',
		'search_input_author_before' => '<div class="col-sm-12 col-md-12 col-lg-5">',
		'search_input_author_after'  => '</div>',
		'search_input_age_before'    => '<div class="col-sm-12 col-md-12 col-lg-4">',
		'search_input_age_after'     => '</div>',
		'search_input_type_before'   => '<div class="col-sm-12 col-md-12 col-lg-3">',
		'search_input_type_after'    => '</div>',
		'search_line_before'         => '<div style="text-align: left; margin: .5em 0;" class="row">',
		'search_line_after'          => '</div>',
		'search_template'            => '$input_keywords$$button_search$'."\n".'$input_author$ $input_age$ $input_content_type$',
		'search_use_editor'          => false,
		'search_author_format'       => 'avatar_name',
		'search_cell_author_start'   => '<div class="search_info dimmed">',
		'search_cell_author_end'     => '</div>',
		'search_date_format'         => locale_datefmt(),
	), $params );

// ------------------------ START OF SEARCH FORM WIDGET ------------------------
skin_widget( array(
		// CODE for the widget:
		'widget' => 'coll_search_form',
		// Optional display params
		'block_start'                => '<div class="evo_widget $wi_class$">',
		'block_end'                  => '</div>',
		'block_display_title'        => false,
		'disp_search_options'        => 0,
		'search_class'               => $params['search_class'],
		'search_input_before'        => '<div class="col-sm-12"><div class="input-group">',
		'search_input_after'         => '',
		'search_submit_before'       => '<span class="input-group-btn">',
		'search_submit_after'        => '</span></div></div>',
		'search_input_author_before' => '<div class="col-sm-12 col-md-12 col-lg-5">',
		'search_input_author_after'  => '</div>',
		'search_input_age_before'    => '<div class="col-sm-12 col-md-12 col-lg-4">',
		'search_input_age_after'     => '</div>',
		'search_input_type_before'   => '<div class="col-sm-12 col-md-12 col-lg-3">',
		'search_input_type_after'    => '</div>',
		'search_line_before'         => '<div style="text-align: left; margin: .5em 0;" class="row">',
		'search_line_after'          => '</div>',
		'search_template'            => '$input_keywords$$button_search$'."\n".'$input_author$ $input_age$ $input_content_type$',
		'use_search_disp'            => 1,
		'show_advanced_options'      => true
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