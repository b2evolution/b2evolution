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
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage pureforums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

echo '<table class="forums_table topics_table" cellspacing="0" cellpadding="0">';

// --------------------------------- START OF COMMON LINKS --------------------------------
skin_widget( array(
		// CODE for the widget:
		'widget' => 'coll_search_form',
		// Optional display params
		'block_start' => '<tr class="ft_search"><td>',
		'block_end' => '</td></tr>',
		'block_display_title' => false,
		'disp_search_options' => 0,
		'search_class' => 'extended_search_form',
		'use_search_disp' => 1,
		'button' => T_('Search')
	) );
// ---------------------------------- END OF COMMON LINKS ---------------------------------

// Display the search result
search_result_block( array(
		'title_prefix_post'     => T_('Topic: '),
		'title_prefix_comment'  => T_('Reply: '),
		'title_prefix_category' => T_('Forum: '),
		'title_prefix_tag'      => T_('Tag: '),
		'block_start' => '',
		'block_end'   => '',
		'row_start'   => '<tr><td>',
		'row_end'     => '</td></tr>',
		'pagination'  => array(
				'block_start' => '<tr class="panel white"><td><div class="navigation">',
				'block_end' => '</div></td></tr>',
				'page_current_template' => '<strong class="current_page">$page_num$</strong>',
				'page_item_before'      => '',
				'page_item_after'       => '',
				'prev_text'             => T_('Previous'),
				'next_text'             => T_('Next'),
				'prev_class'            => 'prev',
				'next_class'            => 'next',
			)
	) );

echo '</table>';
?>