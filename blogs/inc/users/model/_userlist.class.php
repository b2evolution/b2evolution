<?php
/**
 * This file implements the UserList class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * @version $Id: _userlist.class.php 236 2011-11-08 16:08:22Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobjectlist2.class.php', 'DataObjectList2' );
load_class( 'users/model/_userquery.class.php', 'UserQuery' );

/**
 * UserList Class
 *
 * @package evocore
 */
class UserList extends DataObjectList2
{
	/**
	 * SQL object for the Query
	 */
	var $UserQuery;

	/**
	 * Boolean var, Set TRUE when we should to get new user IDs from DB (when user changes the filter params)
	 */
	var $refresh_query = false;

	/**
	 * Boolean var, TRUE - to memorize params (regenerate_url)
	 */
	var $memorize = true;
	
	/**
	 * @var array Params to build query
	 */
	var $query_params = array();

	/**
	 * Constructor
	 *
	 * @param integer|NULL Limit
	 * @param string prefix to differentiate page/order params when multiple Results appear one same page
	 * @param string Name to be used when saving the filterset (leave empty to use default)
	 * @param array Query params:
	 *                    'join_group'   => true,
	 *                    'join_session' => false,
	 *                    'join_country' => true,
	 *                    'join_city'    => true,
	 *                    'keywords_fields'     - Fields of users table to search by keywords
	 *                    'where_status_closed' - FALSE - to don't display closed users
	 */
	function UserList(
		$filterset_name = '', // Name to be used when saving the filterset (leave empty to use default)
		$limit = 20, // Page size
		$param_prefix = 'users_',
		$query_params = array()
		)
	{
		// Call parent constructor:
		parent::DataObjectList2( get_Cache( 'UserCache' ), $limit, $param_prefix, NULL );

		// Init query params, @see $this->query_init()
		$this->query_params = $query_params;

		if( !empty( $filterset_name ) )
		{	// Set the filterset_name with the filterset_name param
			$this->filterset_name = 'UserList_filters_'.$filterset_name;
		}
		else
		{	// Set a generic filterset_name
			$this->filterset_name = 'UserList_filters';
		}

		$this->order_param = $param_prefix.'order';
		$this->page_param = $param_prefix.'paged';

		// Initialize the default filter set:
		$this->set_default_filters( array(
				'filter_preset'       => NULL,
				'country'             => NULL,    // integer, Country ID
				'region'              => NULL,    // integer, Region ID
				'subregion'           => NULL,    // integer, Subregion ID
				'city'                => NULL,    // integer, City ID
				'keywords'            => NULL,    // string, Search words
				'gender'              => NULL,    // string: 'M', 'F' or 'MF'
				'status_activated'    => NULL,    // string: 'activated'
				'account_status'      => NULL,    // string: 'new', 'activated', 'autoactivated', 'emailchanged', 'deactivated', 'failedactivation', 'closed'
				'reported'            => NULL,    // integer: 1 to show only reported users
				'custom_sender_email' => NULL,    // integer: 1 to show only users with custom notifcation sender email address
				'custom_sender_name'  => NULL,    // integer: 1 to show only users with custom notifaction sender name
				'group'               => -1,      // string: User group ID, -1 = all groups but list is ungrouped, 0 - all groups with grouped list
				'age_min'             => NULL,    // integer, Age min
				'age_max'             => NULL,    // integer, Age max
				'userfields'          => array(), // Format of item: array( 'type' => type_ID, 'value' => search_words )
				'order'               => '-D',    // Order
				'users'               => array(), // User IDs
		) );
	}


	/**
	 * Reset the query -- EXPERIMENTAL
	 *
	 * Useful to requery with a slighlty moidified filterset
	 */
	function reset()
	{
		// The SQL Query object:
		$this->UserQuery = new UserQuery( $this->Cache->dbtablename, $this->Cache->dbprefix, $this->Cache->dbIDname );

		parent::reset();
	}


	/**
	 * Set/Activate filterset
	 *
	 * This will also set back the GLOBALS !!! needed for regenerate_url().
	 *
	 * @param array Filters
	 */
	function set_filters( $filters )
	{
		if( !empty( $filters ) )
		{ // Activate the filterset (fallback to default filter when a value is not set):
			$this->filters = array_merge( $this->default_filters, $filters );
		}

		// Activate preset filters if necessary:
		$this->activate_preset_filters();

		// Page
		$this->page = param( $this->page_param, 'integer', 1 );

		// Country
		if( has_cross_country_restriction() )
		{ // In case of cross country restrionction we always have to set the ctry filter
			// In this case we always have a logged in user
			global $current_User;
			if( ( ! empty( $current_User->ctry_ID ) ) && ( $current_User->ctry_ID != $this->filters['country'] ) )
			{ // current country filter is not the same
				$this->filters['country'] = $current_User->ctry_ID;
				$this->refresh_query = true;
			}
		}

		// asimo> memorize is always false for now, because is not fully implemented
		if( $this->memorize )
		{	// set back the GLOBALS !!! needed for regenerate_url() :

			/*
			 * Selected filter preset:
			 */
			memorize_param( 'filter_preset', 'string', $this->default_filters['filter_preset'], $this->filters['filter_preset'] );  // List of authors to restrict to

			/*
			 * Restrict by keywords
			 */
			memorize_param( 'keywords', 'string', $this->default_filters['keywords'], $this->filters['keywords'] );			 // Search string

			/*
			 * Restrict by gender
			 */
			memorize_param( 'gender_men', 'integer', strpos( $this->default_filters['gender'], 'M' ) !== false, strpos( $this->filters['gender'], 'M' ) !== false );
			memorize_param( 'gender_women', 'integer', strpos( $this->default_filters['gender'], 'F' ) !== false, strpos( $this->filters['gender'], 'F' ) !== false );

			/*
			 * Restrict by status
			 */
			memorize_param( 'status_activated', 'string', $this->default_filters['status_activated'], $this->filters['status_activated'] );
			memorize_param( 'account_status', 'string', $this->default_filters['account_status'], $this->filters['account_status'] );

			/*
			 * Restrict by reported state ( was reported or not )
			 */
			memorize_param( 'reported', 'integer', $this->default_filters['reported'], $this->filters['reported'] );

			/*
			 * Restrict by custom sender email settings
			 */
			memorize_param( 'custom_sender_email', 'integer', $this->default_filters['custom_sender_email'], $this->filters['custom_sender_email'] );
			memorize_param( 'custom_sender_name', 'integer', $this->default_filters['custom_sender_name'], $this->filters['custom_sender_name'] );

			/*
			 * Restrict by user group
			 */
			memorize_param( 'group', 'string', $this->default_filters['group'], $this->filters['group'] );

			/*
			 * Restrict by locations
			 */
			memorize_param( 'country', 'integer', $this->default_filters['country'], $this->filters['country'] );       // Search country
			memorize_param( 'region', 'integer', $this->default_filters['region'], $this->filters['region'] );          // Search region
			memorize_param( 'subregion', 'integer', $this->default_filters['subregion'], $this->filters['subregion'] ); // Search subregion
			memorize_param( 'city', 'integer', $this->default_filters['city'], $this->filters['city'] );                // Search city

			/*
			 * Restrict by age group
			 */
			memorize_param( 'age_min', 'integer', $this->default_filters['age_min'], $this->filters['age_min'] );
			memorize_param( 'age_max', 'integer', $this->default_filters['age_max'], $this->filters['age_max'] );

			/*
			 * Restrict by user fields
			 */
			$filters_uf_types = array();
			$filters_uf_values = array();
			$userfields = !empty( $this->filters['userfields'] ) ? $this->filters['userfields'] : $this->default_filters['userfields'];
			foreach( $userfields as $field )
			{
				$filters_uf_types[] = $field['type'];
				$filters_uf_values[] = $field['value'];
			}
			memorize_param( 'criteria_type', 'array', $filters_uf_types, $filters_uf_types );
			memorize_param( 'criteria_value', 'array', $filters_uf_values, $filters_uf_values );

			/*
			 * order:
			 */
			$order = param( $this->order_param, 'string', '' );
			$this->order = $order != '' ? $order : $this->filters['order'];
			if( $this->order != $this->filters['order'] )
			{	// Save order from request
				$this->filters['order'] = $this->order;
				$this->save_filterset();
				$this->refresh_query = true;
			}
			memorize_param( $this->order_param, 'string', $this->default_filters['order'], $this->order ); // Order

			// 'paged'
			memorize_param( $this->page_param, 'integer', 1, $this->page ); // List page number in paged display
		}
	}


	/**
	 * Init filter params from request params
	 *
	 * @param boolean do we want to use saved filters ?
	 * @return boolean true if we could apply a filterset based on Request params (either explicit or reloaded)
	 */
	function load_from_Request( $use_filters = true )
	{
		$this->filters = $this->default_filters;

		if( $use_filters )
		{
			// Do we want to restore filters or do we want to create a new filterset
			$filter = param( 'filter', 'string', '' );
			switch( $filter )
			{
				case 'new':
					$this->refresh_query = true;
					break;

				case 'reset':
					// We want to reset the memorized filterset:
					global $Session;
					$Session->delete( $this->filterset_name );

					// Memorize global variables:
					$this->set_filters( array() );
					$this->refresh_query = true;
					// We have applied no filterset:
					return false;
					/* BREAK */

				case 'refresh':
					$this->refresh_query = true;
					return $this->restore_filterset();

				default:
					return $this->restore_filterset();
			}

			/**
			 * Filter preset
			 */
			$this->filters['filter_preset'] = param( 'filter_preset', 'string', $this->default_filters['filter_preset'], true );

			// Activate preset default filters if necessary:
			$this->activate_preset_filters();
		}

		/*
		 * Restrict by keywords
		 */
		$this->filters['keywords'] = param( 'keywords', 'string', $this->default_filters['keywords'], true );         // Search string

		/*
		 * Restrict by gender
		 */
		$gender_men = param( 'gender_men', 'boolean', strpos( $this->default_filters['gender'], 'M' ), true );
		$gender_women = param( 'gender_women', 'boolean', strpos( $this->default_filters['gender'], 'F' ), true );
		if( ( $gender_men && ! $gender_women ) || ( ! $gender_men && $gender_women ) )
		{	// Find men OR women
			$this->filters['gender'] = $gender_men ? 'M' : 'F';
		}
		else if( $gender_men && $gender_women )
		{	// Find men AND women
			$this->filters['gender'] = 'MF';
		}

		/*
		 * Restrict by status
		 */
		$this->filters['account_status'] = param( 'account_status', 'string', $this->default_filters['account_status'], true );
		if( $this->filters['account_status'] === $this->default_filters['account_status'] &&
		    param( 'status_activated', 'boolean', $this->default_filters['status_activated'], true ) )
		{
			$this->filters['status_activated'] = 'activated';
		}
		else
		{
			$this->filters['status_activated'] = $this->default_filters['status_activated'];
		}

		/*
		 * Restrict by reported state ( was reported or not )
		 */
		$this->filters['reported'] = param( 'reported', 'integer', $this->default_filters['reported'], true );

		/*
		 * Restrict by custom sender email settings
		 */
		$this->filters['custom_sender_email'] = param( 'custom_sender_email', 'integer', $this->default_filters['custom_sender_email'], true );
		$this->filters['custom_sender_name'] = param( 'custom_sender_name', 'integer', $this->default_filters['custom_sender_name'], true );

		/*
		 * Restrict by user group
		 */
		$this->filters['group'] = param( 'group', 'string', $this->default_filters['group'], true );

		/*
		 * Restrict by locations
		 */
		$this->filters['country'] = param( 'country', 'integer', $this->default_filters['country'], true );
		$this->filters['region'] = param( 'region', 'integer', $this->default_filters['region'], true );
		$this->filters['subregion'] = param( 'subregion', 'integer', $this->default_filters['subregion'], true );
		$this->filters['city'] = param( 'city', 'integer', $this->default_filters['city'], true );

		/*
		 * Restrict by age group
		 */
		$this->filters['age_min'] = param( 'age_min', 'integer', $this->default_filters['age_min'], true );
		$this->filters['age_max'] = param( 'age_max', 'integer', $this->default_filters['age_max'], true );

		/*
		 * Restrict by user fields
		 */
		$criteria_types = param( 'criteria_type', 'array/integer', array(), true );
		$criteria_values = param( 'criteria_value', 'array/string', array(), true );
		$userfields = array();
		foreach( $criteria_types as $c => $type )
		{
			$userfields[] = array(
					'type' => $type,
					'value' => $criteria_values[$c]
				);
		}
		$this->filters['userfields'] = $userfields;

		// 'paged'
		$this->page = param( $this->page_param, 'integer', 1, true );      // List page number in paged display

		// 'order'
		global $Session;
		$prev_filters = $Session->get( $this->filterset_name );
		if( !empty( $prev_filters['order'] ) )
		{	// Restore an order from saved session
			$this->order = $this->filters['order'] = $prev_filters['order'];
		}

		if( $use_filters && $filter == 'new' )
		{
			$this->save_filterset();
		}

		return ! param_errors_detected();
	}


	/**
	 *
	 *
	 * @todo count?
	 */
	function query_init()
	{
		global $current_User;

		if( empty( $this->filters ) )
		{	// Filters have not been set before, we'll use the default filterset:
			// If there is a preset filter, we need to activate its specific defaults:
			$this->filters['filter_preset'] = param( 'filter_preset', 'string', $this->default_filters['filter_preset'], true );
			$this->activate_preset_filters();

			// Use the default filters:
			$this->set_filters( $this->default_filters );
		}

		// GENERATE THE QUERY:

		// The SQL Query object:
		// If group == -1 we shouldn't group list by user group
		$this->query_params['grouped'] = ( $this->filters['group'] != -1 );
		$this->UserQuery = new UserQuery( $this->Cache->dbtablename, $this->Cache->dbprefix, $this->Cache->dbIDname, $this->query_params );
		if( isset( $this->query_params['keywords_fields'] ) )
		{ // Change keywords_fields from query params
			$this->UserQuery->keywords_fields = $this->query_params['keywords_fields'];
		}
		if( isset( $this->query_params['where_status_closed'] ) )
		{ // Limit by closed users
			$this->UserQuery->where_status( 'closed', $this->query_params['where_status_closed'] );
		}

		// If browse users from different countries is restricted, then always filter to the current User country
		if( has_cross_country_restriction() )
		{ // Browse users from different countries is restricted, filter to current user country
			$ctry_filter = $current_User->ctry_ID;
			// if country filtering was changed the qurey must be refreshed
			$this->refresh_query = ( $this->refresh_query || ( $ctry_filter != $this->filters['country'] ) );
		}
		else
		{ // Browse users from different countries is allowed
			$ctry_filter = $this->filters['country'];
		}

		/*
		 * filtering stuff:
		 */
		$this->UserQuery->where_keywords( $this->filters['keywords'] );
		$this->UserQuery->where_gender( $this->filters['gender'] );
		$this->UserQuery->where_status( $this->filters['status_activated'] );
		$this->UserQuery->where_status( $this->filters['account_status'], true, true );
		$this->UserQuery->where_reported( $this->filters['reported'] );
		$this->UserQuery->where_custom_sender( $this->filters['custom_sender_email'], $this->filters['custom_sender_name'] );
		$this->UserQuery->where_group( $this->filters['group'] );
		$this->UserQuery->where_location( 'ctry', $ctry_filter );
		$this->UserQuery->where_location( 'rgn', $this->filters['region'] );
		$this->UserQuery->where_location( 'subrg', $this->filters['subregion'] );
		$this->UserQuery->where_location( 'city', $this->filters['city'] );
		$this->UserQuery->where_age_group( $this->filters['age_min'], $this->filters['age_max'] );
		$this->UserQuery->where_userfields( $this->filters['userfields'] );

		if( $this->get_order_field_list() != '' )
		{
			$this->UserQuery->order_by( str_replace( '*', $this->get_order_field_list(), $this->UserQuery->order_by ) );
		}
		else
		{
			$this->UserQuery->order_by( $this->get_order_field_list() );
		}
	}


	/**
	 * Run Query: GET DATA ROWS *** HEAVY ***
	 */
	function query()
	{
		global $DB, $Session, $localtimenow;

		if( !is_null( $this->rows ) )
		{ // Query has already executed:
			return;
		}

		// INIT THE QUERY:
		$this->query_init();


		// We are going to proceed in two steps (we simulate a subquery)
		// 1) we get the IDs we need
		// 2) we get all the other fields matching these IDs
		// This is more efficient than manipulating all fields at once.

		// *** STEP 1 ***
		$user_IDs = $this->filters['users'];
		if( $this->refresh_query || // Some filters are changed
		    $localtimenow - $Session->get( $this->filterset_name.'_refresh_time' ) > 7200 ) // Time has passed ( 2 hours )
		{	// We should create new list of user IDs
			global $Timer;
			$Timer->start( 'Users_IDs', false );

			$step1_SQL = new SQL();
			$step1_SQL->SELECT( 'T_users.user_ID, IF( user_avatar_file_ID IS NOT NULL, 1, 0 ) as has_picture, COUNT( DISTINCT blog_ID ) AS nb_blogs' );
			if( !empty( $this->filters['reported'] ) && $this->filters['reported'] )
			{	// Filter is set to 'Reported users'
				$step1_SQL->SELECT_add( ', COUNT( DISTINCT urep_reporter_ID ) AS user_rep' );
			}
			$step1_SQL->FROM( $this->UserQuery->get_from( '' ) );
			$step1_SQL->WHERE( $this->UserQuery->get_where( '' ) );
			$step1_SQL->GROUP_BY( $this->UserQuery->get_group_by( '' ) );
			$step1_SQL->ORDER_BY( $this->UserQuery->get_order_by( '' ) );
			$step1_SQL->LIMIT( 0 );

			// Get list of the IDs we need:
			$user_IDs = $DB->get_col( $step1_SQL->get(), 0, 'UserList::Query() Step 1: Get ID list' );
			// Update filter with user IDs
			$this->filters['users'] = $user_IDs;
			$this->save_filterset();

			$Timer->stop( 'Users_IDs' );
		}

		// GET TOTAL ROW COUNT:
		parent::count_total_rows( count( $user_IDs ) );

		// Pagination, Get user IDs from array for current page
		$user_IDs_paged = array_slice( $user_IDs, ( $this->page - 1 ) * $this->limit, $this->limit );

		// *** STEP 2 ***
		$step2_SQL = $this->UserQuery;

		if( ! empty( $user_IDs_paged ) )
		{	// Init sql query to get users by IDs
			$step2_SQL->WHERE( $this->Cache->dbIDname.' IN ('.implode( ',', $user_IDs_paged ).') ' );
			$step2_SQL->ORDER_BY( 'FIND_IN_SET( user_ID, "'.implode( ',', $user_IDs_paged ).'" )' );
		}
		else
		{	// No users
			$step2_SQL->WHERE( 'user_ID IS NULL' );
		}

		$this->sql = $step2_SQL->get();

		// ATTENTION: we skip the parent on purpose here!! fp> refactor
		DataObjectList2::query( false, false, false, 'UserList::Query() Step 2' );
	}


	/**
	 * Check if the Result set is filtered or not
	 */
	function is_filtered()
	{
		if( empty( $this->filters ) )
		{
			return false;
		}

		// Exclude user IDs
		unset( $this->filters['users'] );
		unset( $this->default_filters['users'] );

		return ( $this->filters != $this->default_filters );
	}


	/**
	 * Link to previous and next link in collection
	 */
	function prevnext_user_links( $params )
	{
		$params = array_merge( array(
									'template'     => '$prev$$back$$next$',
									'prev_start'   => '',
									'prev_text'    => '&laquo; $login$',
									'prev_end'     => '',
									'prev_no_user' => '',
									'back_start'   => '',
									'back_end'     => '',
									'next_start'   => '',
									'next_text'    => '$login$ &raquo;',
									'next_end'     => '',
									'next_no_user' => '',
									'user_tab'     => 'profile',
								), $params );

		// ID of selected user
		$user_ID = get_param( 'user_ID' );

		$users_list = $this->filters['users'];
		if( array_search( $user_ID, $users_list ) === false )
		{	// Selected user is NOT located in this list
			return;
		}

		$prev = $this->prevnext_user_link( 'prev', $params['prev_start'], $params['prev_end'], $params['prev_text'], $params['prev_no_user'], $params['user_tab'], false );
		$next = $this->prevnext_user_link( 'next', $params['next_start'], $params['next_end'], $params['next_text'], $params['next_no_user'], $params['user_tab'], false );
		$back = $this->back_user_link( $params['back_start'], $params['back_end'], false );

		$output = str_replace( '$prev$', $prev, $params['template'] );
		$output = str_replace( '$next$', $next, $output );
		$output = str_replace( '$back$', $back, $output );

		if( !empty( $output ) )
		{	// we have some output, lets wrap it
			echo( $params['block_start'] );
			echo $output;
			echo( $params['block_end'] );
		}
	}


	/**
	 * Get link to previous/next user
	 *
	 * @return string Link to previous/next user
	 */
	function prevnext_user_link( $direction, $before = '', $after = '', $text = '&laquo; $login$', $no_user = '', $user_tab = 'profile', $display = true )
	{
		/**
		 * @var User
		 */
		$prev_User = & $this->get_prevnext_User( $direction );
		if( has_cross_country_restriction() )
		{ // If current user has cross country restriction, make sure we display only users from the same country
			// Note: This may happen only if the user list filter was saved and the cross country restriction was changed after that
			global $current_User;
			while( !empty( $prev_User ) && ( $current_User->ctry_ID !== $prev_User->ctry_ID ) )
			{
				$prev_User = & $this->get_prevnext_User( $direction, $prev_User->ID );
			}
		}

		if( !empty( $prev_User ) )
		{	// User exists in DB
			$output = $before;
			$identity_url = get_user_identity_url( $prev_User->ID, $user_tab );
			$login = str_replace( '$login$', $prev_User->get_colored_login(), $text );
			if( !empty( $identity_url ) )
			{	// User link is available
				// Note: we don't want a bubble tip on navigation links
				$output .= '<a href="'.$identity_url.'">'.$login.'</a>';
			}
			else
			{	// No identity link
				$output .= $login;
			}
			$output .= $after;
		}
		else
		{	// No user
			$output = $no_user;
		}
		if( $display ) echo $output;
		return $output;
	}


	/**
	 * Get link to back users list
	 *
	 * @return string Link to back users list
	 */
	function back_user_link( $before = '', $after = '', $display = true )
	{
		// ID of selected user
		$user_ID = get_param( 'user_ID' );

		$users_list = $this->filters['users'];

		$user_key = array_search( $user_ID, $users_list );
		if( is_int( $user_key ) )
		{	// Selected user is located in this list
			global $Blog;
			++$user_key;
			$page = ceil( $user_key / $this->limit );
			$page_param = '';
			if( $page > 1 )
			{
				$page_param = $this->page_param.'='.$page;
			}

			$output = $before;
			$output .= '<a href="'.get_dispctrl_url( 'users', $page_param ).'">'.$user_key.'/'.count( $users_list ).'</a>';
			$output .= $after;
		}
		else
		{
			$output = '';
		}
		if( $display ) echo $output;
		return $output;
	}


	/**
	 * Skip to previous/next User
	 *
	 * @param integer the currently selected user ID ( Note: it must be set only if we would like to skip some users from the list )
	 * @param string prev | next  (relative to the current sort order)
	 */
	function & get_prevnext_User( $direction = 'next', $selected_user_ID = NULL )
	{
		$users_list = $this->filters['users'];

		if( count( $users_list ) < 2 )
		{	// Short users list
			$r = NULL;
			return $r;
		}

		// ID of selected user
		if( $selected_user_ID === NULL )
		{ // get currently selected user ID from param
			$selected_user_ID = get_param( 'user_ID' );
		}

		$user_key = array_search( $selected_user_ID, $users_list );
		if( is_int( $user_key ) )
		{	// Selected user is located in the list
			$prevnext_key = $direction == 'next' ? $user_key + 1 : $user_key - 1;
			if( isset( $users_list[$prevnext_key] ) )
			{	// Prev/next user is located in the list
				$prevnext_ID = $users_list[$prevnext_key];
			}
		}

		if( empty( $prevnext_ID ) )
		{	// No prev/next user
			$r = NULL;
			return $r;
		}

		$UserCache = & get_UserCache();
		$User = & $UserCache->get_by_ID( $prevnext_ID, false, false );

		return $User;
	}


	/**
	 * Set an order of a list (Use this function after when all $this->cols are already defined)
	 *
	 * @param string Field name
	 * @param string Order direction (A|D)
	 * @param boolean Save the filters from Session
	 */
	function set_order( $order_field, $direction = 'D', $save_filters = false )
	{
		global $Session;

		if( empty( $this->cols ) )
		{ // The columns are not defined yet, Exit here
			return;
		}

		// Force filter param to reset the previous filters
		set_param( 'filter', 'new' );
		$this->refresh_query = true;

		foreach( $this->cols as $col_num => $col )
		{	// Find a column number
			if( $col['order'] == $order_field )
			{
				break;
			}
		}

		if( $save_filters )
		{ // Get the filters from Session
			$this->filters = $Session->get( $this->filterset_name );
			if( ! is_array( $this->filters ) )
			{
				$this->filters = array();
			}
			$this->filters = array_merge( $this->default_filters, $this->filters );
		}
		else
		{ // Reset the filters
			$this->filters = array();
		}

		// Rewrite a previous order to new value
		$this->filters['order'] = str_repeat( '-', $col_num ).$direction;
		$this->order = $this->filters['order'];

		// Save a new order
		$Session->set( $this->filterset_name, $this->filters );
		$this->save_filterset();
	}

}

?>