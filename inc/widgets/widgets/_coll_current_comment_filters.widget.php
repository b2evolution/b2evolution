<?php
/**
 * This file implements the Current Comment filters Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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
class coll_current_comment_filters_Widget extends ComponentWidget
{
	var $icon = 'filter';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'coll_current_comment_filters' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'current-comment-filters-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Current Comment filters');
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
		return T_('Summary of the current Comment filters.');
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
					'defaultvalue' => T_('Current Comment filters'),
					'maxlength' => 100,
				),
			'show_filters' => array(
					'type' => 'checklist',
					'label' => T_('Show filters'),
					'options' => array(
						array( 'visibility', T_('Visibility'), 0 ),
						array( 'keyword', T_('Keyword'), 1 ),
						array( 'rating', T_('Rating'), 1 ),
						array( 'author', T_('Author'), 1 ),
						array( 'author_url', T_('Author URL'), 1 ),
						array( 'author_IP', T_('IP'), 1 ),
						array( 'active', T_('Show active'), 1 ),
						array( 'expired', T_('Show expired'), 1 ),
					),
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
		global $CommentList;

		$params = array_merge( array(
				'CommentList'          => $CommentList,
				'display_button_reset' => true, // Display a button to reset all filters
				'display_empty_filter' => false, // Display a block with text "No filters"
			), $params );

		if( empty( $params['CommentList'] ) )
		{ // Empty CommentList object
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because there is an empty param "CommentList".' );
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

		$filters =  implode( ' '.T_('AND').' ', $params['CommentList']->get_filter_titles( array(), array(
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
				'display_visibility' => ! empty( $this->disp_params['show_filters']['visibility'] ),
				'display_keyword'    => ! empty( $this->disp_params['show_filters']['keyword'] ),
				'display_rating'     => ! empty( $this->disp_params['show_filters']['rating'] ),
				'display_author'     => ! empty( $this->disp_params['show_filters']['author'] ),
				'display_author_url' => ! empty( $this->disp_params['show_filters']['author_url'] ),
				'display_author_IP'  => ! empty( $this->disp_params['show_filters']['author_IP'] ),
				'display_active'     => ! empty( $this->disp_params['show_filters']['active'] ),
				'display_expired'    => ! empty( $this->disp_params['show_filters']['expired'] ),
			) ) );

		if( empty( $filters ) && ! $params['display_empty_filter'] )
		{	// No filters
			$this->display_debug_message( 'Widget "'.$this->get_name().'" is hidden because there are no filters.' );
			return false;
		}

		// START DISPLAY:
		echo $this->disp_params['block_start'];

		// Display title if requested
		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		if( empty( $filters ) )
		{ // No filters
			if( $params['display_empty_filter'] )
			{
				echo sprintf( T_('No filters - Showing all %s'), T_('comments') );
			}
		}
		else
		{ // Display the filters
			echo $filters;

			if( $params['display_button_reset'] )
			{ // Button to reset all filters
				echo '<p>'.action_icon( T_('Remove filters'), 'reset_filters',
					regenerate_url( 'catsel,cat,'
						.$params['CommentList']->param_prefix.'author_IDs,'
						.$params['CommentList']->param_prefix.'authors_login,'
						.$params['CommentList']->param_prefix.'author,'
						.$params['CommentList']->param_prefix.'author_email,'
						.$params['CommentList']->param_prefix.'author_url,'
						.$params['CommentList']->param_prefix.'author_IP,'
						.$params['CommentList']->param_prefix.'url_match,'
						.$params['CommentList']->param_prefix.'include_emptyurl,'
						.$params['CommentList']->param_prefix.'dstart,'
						.$params['CommentList']->param_prefix.'dstop,'
						.$params['CommentList']->param_prefix.'rating_toshow,'
						.$params['CommentList']->param_prefix.'rating_turn,'
						.$params['CommentList']->param_prefix.'rating_limit,'
						.$params['CommentList']->param_prefix.'status,'
						.$params['CommentList']->param_prefix.'show_statuses,'
						.$params['CommentList']->param_prefix.'s,'
						.$params['CommentList']->param_prefix.'sentence,'
						.$params['CommentList']->param_prefix.'exact,'
						.$params['CommentList']->param_prefix.'expiry_statuses,'
						.$params['CommentList']->param_prefix.'type,'
						.$params['CommentList']->param_prefix.'user_perm,' ),
					' '.T_('Remove filters'), 3, 4 ).'<p>';
			}
		}

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>