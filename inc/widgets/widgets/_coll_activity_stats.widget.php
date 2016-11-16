<?php
/**
 * This file implements the coll_activity_stats_Widget class.
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
init_jqplot_js();

/**
 * coll_activity_stats_Widget Class.
 *
 * This displays activity statistics for forums
 *
 * @package evocore
 */
class coll_activity_stats_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'coll_activity_stats' );
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'collection-activity-stats-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Activity Statistics');
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display the activity statistics of the collection.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array local params
	 *  - 'size': Size definition, see {@link $thumbnail_sizes}. E.g. 'fit-160x160'.
	 */
	function get_param_definitions( $params )
	{
		$visibility_statuses = get_visibility_statuses( 'raw', array( 'deprecated', 'redirected', 'trash' ) );
		$visibility_statuses_icons = get_visibility_statuses( 'icons', array( 'deprecated', 'redirected', 'trash' ) );
		$default_visible_statuses = array( 'published', 'community', 'protected' );
		$option_statuses = array();
		foreach( $visibility_statuses as $status => $status_text )
		{
			$option_statuses[] = array(
				'inskin_'.$status,
				$visibility_statuses_icons[$status].' '.$status_text,
				in_array( $status, $default_visible_statuses ) ? 1 : 0
			);
		}

		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display' ),
					'defaultvalue' => 'Activity Stats',
				),
				'height' => array(
					'label' => T_('Chart height'),
					'note' => '',
					'defaultvalue' => '300',
					'allow_empty' => true,
					'size' => 4,
					'valid_pattern' => array(
							'pattern' => '~^(\d+(px)?)?$~i',
							'error'   => sprintf( T_('Invalid chart height, it must be specified in px.') ) ),
				),
				'time_period' => array(
					'type' => 'select',
					'label' => T_('Period'),
					'options' => array(
							'last_week' => T_('last week'),
							'last_month' => T_('last month')
						),
					'note' => T_('Period of activity to display'),
					'defaultvalue' => 'last_month',
				),
				'visibility_statuses' => array(
					'label' => T_('Visibility statuses'),
					'type' => 'checklist',
					'options' => $option_statuses,
					'note' => T_('Only topics and comments with the above visibilities will be counted.')
				),

			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Prepare display params
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function init_display( $params )
	{
		parent::init_display( $params );

		$this->disp_params['block_body_start'] = '<div">';
		$this->disp_params['block_body_end'] = '</div>';
	}


	function get_activity_data()
	{
		global $DB, $Blog, $activity_type_color;

		$chart = array();
		$activity_stats = array();
		switch( $this->disp_params['time_period'] )
		{
			case 'last_week':
				$num_days = 7;
				$start_date = date( 'Y-m-d 00:00:00', strtotime( '-7 days' ) );
				break;

			case 'last_month':
			default:
				$num_days = 30;
				$start_date = date( 'Y-m-d 00:00:00', strtotime( '-30 days' ) );
		}
		$end_date = date( 'Y-m-d 23:59:59' );

		// Get user registrations
		$SQL = new SQL( 'Get count of user registration per day' );
		$SQL->SELECT(	'DATE(user_created_datetime) AS date_registered, COUNT(*) AS registration_count' );
		$SQL->FROM( 'T_users' );
		$SQL->WHERE( 'user_created_datetime > '.$DB->quote( $start_date ) );
		$SQL->GROUP_BY( 'DATE(user_created_datetime)' );
		$users_registered = $DB->get_assoc( $SQL->get(), $SQL->title );

		// Get posts created
		$visibility_statuses = get_visibility_statuses( 'raw', array( 'deprecated', 'redirected', 'trash' ) );
		$filter_inskin_statuses = array();
		foreach( $visibility_statuses as $status => $status_text )
		{
			if( isset( $this->disp_params['visibility_statuses']['inskin_'.$status] ) && $this->disp_params['visibility_statuses']['inskin_'.$status] )
			{
				$filter_inskin_statuses[] = $status;
			}
		}

		$SQL = new SQL( 'Get count of new topics created per day' );
		$SQL->SELECT( 'DATE(post_datestart) AS date_issued, COUNT(*) AS post_count' );
		$SQL->FROM( 'T_items__item' );
		$SQL->FROM_add( 'LEFT JOIN T_categories ON post_main_cat_ID = cat_ID' );
		$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $Blog->ID ) );
		$SQL->WHERE_and( 'post_datestart > '.$DB->quote( $start_date ) );
		$SQL->WHERE_and( 'post_datestart <= '.$DB->quote( $end_date ) );
		$SQL->WHERE_and( 'post_status IN ("'.implode( '","', $filter_inskin_statuses ).'")' );
		$SQL->GROUP_BY( 'DATE(post_datestart)' );
		$posts_created = $DB->get_assoc( $SQL->get(), $SQL->title );

		// Get new comments
		$SQL = new SQL( 'Get count of new comments created per day' );
		$SQL->SELECT( 'DATE(comment_date) AS comment_date, COUNT(*) AS comment_count' );
		$SQL->FROM( 'T_comments' );
		$SQL->FROM_add( 'LEFT JOIN T_items__item ON comment_item_ID = post_ID' );
		$SQL->FROM_add( 'LEFT JOIN T_categories ON post_main_cat_ID = cat_ID' );
		$SQL->WHERE( 'cat_blog_ID = '.$DB->quote( $Blog->ID ) );
		$SQL->WHERE_and( 'comment_date > '.$DB->quote( $start_date ) );
		$SQL->WHERE_and( 'comment_date <= '.$DB->quote( $end_date ) );
		$SQL->WHERE_and( 'comment_status IN ("'.implode( '","', $filter_inskin_statuses ).'")' );
		$SQL->GROUP_BY( 'DATE(comment_date)' );
		$comments = $DB->get_assoc( $SQL->get(), $SQL->title );


		for( $i = 0; $i < $num_days; $i++ )
		{
			$this_date = date( 'Y-m-d', strtotime( '-'.$i.' days' ) );
			$activity_stats[$this_date] = array(
					'users' => !empty( $users_registered[$this_date] ) ? intval( $users_registered[$this_date] ) : 0,
					'posts' => !empty( $posts_created[$this_date] ) ? intval( $posts_created[$this_date] ) : 0,
					'comments' => !empty( $comments[$this_date] ) ? intval( $comments[$this_date] ) : 0,
			);
		}

		$chart['chart_data'][0] = array();
		$chart['chart_data'][1] = array();
		$chart['chart_data'][2] = array();
		$chart['chart_data'][3] = array();

		$chart['dates'] = array();
		$chart['series_color'] = array(
			$activity_type_color['users'],
			$activity_type_color['posts'],
			$activity_type_color['comments'],
		);

		foreach( $activity_stats as $date => $stats )
		{
			array_unshift( $chart['chart_data'][0], date( 'D '.locale_datefmt(), strtotime( $date ) ) );
			array_unshift( $chart['chart_data'][1], $stats['users'] );
			array_unshift( $chart['chart_data'][2], $stats['posts'] );
			array_unshift( $chart['chart_data'][3], $stats['comments'] );

			array_unshift( $chart['dates'], strtotime( $date ) );
		}

		array_unshift( $chart['chart_data'][0], '' );
		array_unshift( $chart['chart_data'][1], T_('User registrations') );
		array_unshift( $chart['chart_data'][2], T_('New topics') );
		array_unshift( $chart['chart_data'][3], T_('New replies') );

		$chart[ 'canvas_bg' ] = array( 'width'  => '100%', 'height' => $this->disp_params['height'] );

		return $chart;
	}

	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		load_funcs('_ext/_canvascharts.php');
		$this->init_display( $params );

		$chart = $this->get_activity_data();

		// START DISPLAY:
		echo $this->disp_params['block_start'];

		// Display title if requested
		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		CanvasBarsChart( $chart );

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Collection, $Blog;

		$owner_User = & $Blog->get_owner_User();

		return array(
				'wi_ID'   => $this->ID,					// Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID,			// Have the settings of the blog changed ? (ex: new owner, new skin)
				'user_ID' => $owner_User->ID, 	// Has the owner User changed? (name, avatar, etc..)
			);
	}

}
?>