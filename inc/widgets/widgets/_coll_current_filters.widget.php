<?php
/**
 * This file implements the Current filters Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2008 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_current_filters_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'coll_current_filters' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'current-filters-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Current filters');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output( $this->disp_params['title'] );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Summary of the current filters.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array local params
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
			'title' => array(
					'type' => 'text',
					'label' => T_('Block title'),
					'defaultvalue' => T_('Current filters'),
					'maxlength' => 100,
				),
			'show_filters' => array(
					'type' => 'checklist',
					'label' => T_('Show filters'),
					'options' => array(
						array( 'category', T_('Category'), 1 ),
						array( 'archive', T_('Archive'), 1 ),
						array( 'keyword', T_('Keyword'), 1 ),
						array( 'tag', T_('Tag'), 1 ),
						array( 'author', T_('Author'), 1 ),
						array( 'assignee', T_('Assignee'), 1 ),
						array( 'locale', T_('Locale'), 1 ),
						array( 'status', T_('Status'), 1 ),
						array( 'visibility', T_('Visibility'), 1 ),
						array( 'time', T_('Past/Future'), 0 ),
						array( 'limit', T_('Limit by days'), 1 ),
						array( 'flagged', T_('Flagged'), 1 ) ),
				),
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $MainList;

		$params = array_merge( array(
				'ItemList'             => $MainList,
				'display_button_reset' => true, // Display a button to reset all filters
				'display_empty_filter' => false, // Display a block with text "No filters"
			), $params );

		if( empty( $params['ItemList'] ) )
		{ // Empty ItemList object
			return false;
		}

		if( isset( $params['show_filters'] ) )
		{ // Get the predefined filters
			$show_filters = $params['show_filters'];
			unset( $params['show_filters'] );
		}

		$this->init_display( $params );

		if( isset( $show_filters ) )
		{ // Rewrite default filters by predefined
			$this->disp_params['show_filters'] = array_merge( $this->disp_params['show_filters'], $show_filters );
		}

		$filters =  implode( ' '.T_('AND').' ', $params['ItemList']->get_filter_titles( array(), array(
				'categories_text'     => '',
				'categories_nor_text' => T_('NOT').' ',
				'tags_nor_text'       => T_('NOT').' ',
				'authors_nor_text'    => T_('NOT').' ',
				'group_mask'          => '$filter_items$',
				'filter_mask'         => '<div class="filter_item $filter_class$">'."\n"
						.'<div class="group">$group_title$</div>'."\n"
						.'<div class="name">$filter_name$</div>'."\n"
						.'<div class="icon">$clear_icon$</div>'."\n"
					.'</div>',
				'filter_mask_nogroup' => '<div class="filter_item $filter_class$">'."\n"
						.'<div class="name">$filter_name$</div>'."\n"
						.'<div class="icon">$clear_icon$</div>'."\n"
					.'</div>',
				'before_items'       => '( ',
				'after_items'        => ' )',
				'separator_and'      => ' '.T_('AND').' ',
				'separator_or'       => ' '.T_('OR').' ',
				'separator_nor'      => ' '.T_('NOR').' ',
				'separator_comma'    => ' '.T_('OR').' ',
				'display_category'   => ! empty( $this->disp_params['show_filters']['category'] ),
				'display_archive'    => ! empty( $this->disp_params['show_filters']['archive'] ),
				'display_keyword'    => ! empty( $this->disp_params['show_filters']['keyword'] ),
				'display_tag'        => ! empty( $this->disp_params['show_filters']['tag'] ),
				'display_author'     => ! empty( $this->disp_params['show_filters']['author'] ),
				'display_assignee'   => ! empty( $this->disp_params['show_filters']['assignee'] ),
				'display_locale'     => ! empty( $this->disp_params['show_filters']['locale'] ),
				'display_status'     => ! empty( $this->disp_params['show_filters']['status'] ),
				'display_visibility' => ! empty( $this->disp_params['show_filters']['visibility'] ),
				'display_time'       => ! empty( $this->disp_params['show_filters']['time'] ),
				'display_limit'      => ! empty( $this->disp_params['show_filters']['limit'] ),
				'display_flagged'    => ! empty( $this->disp_params['show_filters']['flagged'] ),
			) ) );

		if( empty( $filters ) && ! $this->disp_params['display_empty_filter'] )
		{ // No filters
			return;
		}

		// START DISPLAY:
		echo $this->disp_params['block_start'];

		// Display title if requested
		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		if( empty( $filters ) )
		{ // No filters
			if( $this->disp_params['display_empty_filter'] )
			{
				if( is_admin_page() && get_param( 'tab' ) == 'type' )
				{ // Try to get a title for current selected post type on back-office pages:
					$current_post_type_title = '"'.get_param( 'tab_type' ).'"';
				}
				if( empty( $current_post_type_title ) )
				{ // Use this title by default for unknown selected post type:
					$current_post_type_title = T_('items');
				}
				echo sprintf( T_('No filters - Showing all %s'), $current_post_type_title );
			}
		}
		else
		{ // Display the filters
			echo $filters;

			if( $params['display_button_reset'] )
			{ // Button to reset all filters
				echo '<p>'.action_icon( T_('Reset all filters!'), 'reset_filters',
					regenerate_url( 'catsel,cat,'
						.$params['ItemList']->param_prefix.'tag,'
						.$params['ItemList']->param_prefix.'author,'
						.$params['ItemList']->param_prefix.'author_login,'
						.$params['ItemList']->param_prefix.'assgn,'
						.$params['ItemList']->param_prefix.'assgn_login,'
						.$params['ItemList']->param_prefix.'author_assignee,'
						.$params['ItemList']->param_prefix.'lc,'
						.$params['ItemList']->param_prefix.'status,'
						.$params['ItemList']->param_prefix.'show_statuses,'
						.$params['ItemList']->param_prefix.'types,'
						.$params['ItemList']->param_prefix.'s,'
						.$params['ItemList']->param_prefix.'sentence,'
						.$params['ItemList']->param_prefix.'exact,'
						.$params['ItemList']->param_prefix.'p,'
						.$params['ItemList']->param_prefix.'title,'
						.$params['ItemList']->param_prefix.'pl,'
						.$params['ItemList']->param_prefix.'m,'
						.$params['ItemList']->param_prefix.'w,'
						.$params['ItemList']->param_prefix.'dstart,'
						.$params['ItemList']->param_prefix.'dstop,'
						.$params['ItemList']->param_prefix.'show_past,'
						.$params['ItemList']->param_prefix.'show_future,'
						.$params['ItemList']->param_prefix.'flagged' ),
					' '.T_('Reset all filters!'), 3, 4 ).'<p>';
			}
		}

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>