<?php
/**
 * This file implements the Widget class to print out a button to view new or recent items.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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
class recent_items_button_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'recent_items_button' );
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = parent::get_param_definitions( $params );

		if( isset( $r['allow_blockcache'] ) )
		{	// Disable "allow blockcache" because this widget uses the dynamic data of items count:
			$r['allow_blockcache']['defaultvalue'] = false;
			$r['allow_blockcache']['disabled'] = 'disabled';
			$r['allow_blockcache']['note'] = T_('This widget cannot be cached in the block cache.');
		}

		return $r;
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'recent-items-button-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Recent Items Button');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return $this->get_desc();
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Recent Items Button');
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Collection, $Blog;

		$this->init_display( $params );

		if( ! is_logged_in() || ! $Blog->get_setting( 'track_unread_content' ) )
		{	// For not logged in users AND if the tracking of unread content is turned off for the collection
			$btn_class = 'btn-info';
			$btn_title = T_('Recent Topics');
		}
		else
		{	// For logged in users:
			global $current_User, $DB, $localtimenow;

			// Initialize SQL query to get only the posts which are displayed by global $MainList on disp=posts:
			$ItemList2 = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), NULL, 'ItemCache', 'recent_topics' );
			$ItemList2->set_default_filters( array(
					'unit' => 'all', // set this to don't calculate total rows
				) );
			$ItemList2->query_init();

			// Get a count of the unread topics for current user:
			$unread_posts_SQL = new SQL();
			$unread_posts_SQL->SELECT( 'COUNT( post_ID )' );
			$unread_posts_SQL->FROM( 'T_items__item' );
			$unread_posts_SQL->FROM_add( 'LEFT JOIN T_items__user_data ON post_ID = itud_item_ID AND itud_user_ID = '.$DB->quote( $current_User->ID ) );
			$unread_posts_SQL->FROM_add( 'INNER JOIN T_categories ON post_main_cat_ID = cat_ID' );
			$unread_posts_SQL->FROM_add( 'LEFT JOIN T_items__type ON post_ityp_ID = ityp_ID' );
			$unread_posts_SQL->WHERE( $ItemList2->ItemQuery->get_where( '' ) );
			$unread_posts_SQL->WHERE_and( 'post_last_touched_ts > '.$DB->quote( date2mysql( $localtimenow - 30 * 86400 ) ) );
			// In theory, it would be more safe to use this comparison:
			// $unread_posts_SQL->WHERE_and( 'itud_item_ID IS NULL OR itud_read_item_ts <= post_last_touched_ts' );
			// But until we have milli- or micro-second precision on timestamps, we decided it was a better trade-off to never see our own edits as unread. So we use:
			$unread_posts_SQL->WHERE_and( 'itud_item_ID IS NULL OR itud_read_item_ts < post_last_touched_ts' );

			// Execute a query with to know if current user has new data to view:
			$unread_posts_count = $DB->get_var( $unread_posts_SQL->get(), 0, NULL, 'Get a count of the unread topics for current user' );

			if( $unread_posts_count > 0 )
			{	// If at least one new unread topic exists
				$btn_class = 'btn-warning';
				$btn_title = T_('New Topics').' <span class="badge">'.$unread_posts_count.'</span>';
			}
			else
			{	// Current user already have read all topics
				$btn_class = 'btn-info';
				$btn_title = T_('Recent Topics');
			}
		}

		echo $this->disp_params['block_start'];

		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		// Print out the button:
		echo '<a href="'.$Blog->get( 'recentpostsurl' ).'" class="btn '.$btn_class.' pull-right btn_recent_topics">'.$btn_title.'</a>';

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}
}

?>