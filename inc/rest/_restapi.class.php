<?php
/**
 * This file implements REST API class
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package api
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * REST API
 *
 * @package api
 */
class RestApi
{
	/**
	 * @var string Module name: 'collections', 'users'
	 */
	private $module = NULL;

	/**
	 * @var array Arguments that go from request string,
	 *            for example: array( 0 => "collections", 1 => "a", 2 => "posts" )
	 */
	private $args = array();

	/**
	 * @var array Response, This array collects all data that should be printed
	 *                      out on screen in JSON format at the end of execution:
	 */
	private $response = array();


	/**
	 * Constructor
	 *
	 * @param string Request string after "/api/v1/", for example: "collections/a/posts"
	 */
	public function __construct( $request )
	{
		// Authenticate a user:
		$this->user_authentication();

		// Set arguments from request string:
		$this->args = explode( '/', $request );

		if( isset( $this->args[0] ) )
		{	// Initialize module name:
			$this->module = $this->args[0];
		}
	}


	/**
	 * Log in a user
	 *
	 * @param string Login
	 * @param string Password
	 * @return boolean TRUE on success log in, HALT on error
	 */
	private function user_log_in( $entered_login, $entered_password )
	{
		global $current_User, $failed_logins_lockout, $UserSettings, $Settings, $Session, $localtimenow;

		$UserCache = & get_UserCache();

		// Note: login and password cannot include ' or " or > or <
		$entered_login = utf8_strtolower( utf8_strip_tags( remove_magic_quotes( $entered_login ) ) );
		$entered_password = utf8_strip_tags( remove_magic_quotes( $entered_password ) );

		if( is_email( $entered_login ) )
		{	// We have an email address instead of login name
			// Get user by email and password:
			list( $User, $exists_more ) = $UserCache->get_by_emailAndPwd( $entered_login, $entered_password );
		}
		elseif( is_valid_login( $entered_login ) )
		{	// Make sure that we can load the user:
			$User = & $UserCache->get_by_login( $entered_login );
		}

		if( empty( $User ) )
		{	// Wrong authentication because user is not found by requested login/email and password in DB:
			if( is_logged_in() )
			{	// Logout current user:
				logout();
			}
			$this->halt( T_('Wrong login/password.'), 'wrong_login_pass', '403' );
			// Exit here.
		}

		if( is_logged_in() )
		{	// If current user is logged in
			if( $User->ID == $current_User->ID )
			{	// The requested user is already logged in, Don't relog in it on each request:
				return true;
				// Exit here.
			}
			else
			{	// Log out current user because we should log in new user:
				logout();
			}
		}

		// Check user login attempts:
		$login_attempts = $UserSettings->get( 'login_attempts', $User->ID );
		$login_attempts = empty( $login_attempts ) ? array() : explode( ';', $login_attempts );
		if( $failed_logins_lockout > 0 && count( $login_attempts ) == 9 )
		{	// User already has a maximum value of the attempts:
			$first_attempt = explode( '|', $login_attempts[0] );
			if( $localtimenow - $first_attempt[0] < $failed_logins_lockout )
			{	// User has used 9 attempts during X minutes, Display error and Refuse login
				$this->halt( sprintf( T_('There have been too many failed login attempts. This account is temporarily locked. Try again in %s minutes.'), ceil( $failed_logins_lockout / 60 ) ), 'login_attempt_failed', 403 );
				// Exit here.
			}
		}

		if( ! $User->check_password( $entered_password ) )
		{	// The entered password is not right for requested user
			// Save new login attempt into DB:
			if( count( $login_attempts ) == 9 )
			{ // Unset first attempt to clear a space for new attempt
				unset( $login_attempts[0] );
			}
			$login_attempts[] = $localtimenow.'|'.( array_key_exists( 'REMOTE_ADDR', $_SERVER ) ? $_SERVER['REMOTE_ADDR'] : '' );
			$UserSettings->set( 'login_attempts', implode( ';', $login_attempts ), $User->ID );
			$UserSettings->dbupdate();

			$this->halt( T_('Wrong login/password.'), 'wrong_login_pass', '403' );
			// Exit here.
		}

		if( $User->check_status( 'is_closed' ) )
		{	// User account was closed, don't log in it:
			$this->halt( T_('This account is closed. You cannot log in.'), 'closed_account', '403' );
			// Exit here.
		}
		elseif( $Settings->get( 'system_lock' ) && ! $User->check_perm( 'users', 'edit' ) )
		{ // System is locked for maintenance and current user has no permission to log in this mode
			$this->halt( T_('You cannot log in at this time because the system is under maintenance. Please try again in a few moments.'), 'system_maintenance', '503' );
			// Exit here.
		}

		// All checks are good, so we can log in the requested user:
		$current_User = $User;
		$Session->set_User( $current_User );

		if( ! empty( $login_attempts ) )
		{	// Clear the attempts list:
			$UserSettings->delete( 'login_attempts', $current_User->ID );
			$UserSettings->dbupdate();
		}

		return true;
	}


	/**
	 * Authenticate a user
	 */
	private function user_authentication()
	{
		if( isset( $_SERVER, $_SERVER['PHP_AUTH_USER'] ) )
		{	// Do basic HTTP authentication:
			$this->user_log_in( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
		}
	}


	/**
	 * Execute request
	 *
	 * @param boolean TRUE on success result
	 */
	public function execute()
	{
		if( empty( $this->module ) )
		{	// Wrong request because module is not defined:
			$this->halt( 'Module is not defined' );
			// Exit here.
		}

		if( ! method_exists( $this, 'module_'.$this->module ) )
		{	// Unknown module:
			$this->halt( 'Unknown module "'.$this->module.'"', 'unknown_module' );
			// Exit here.
		}

		// Call module to prepare current request:
		$this->{'module_'.$this->module}();

		// Print out response in JSON format:
		$this->display_response();
	}


	/**
	 * Halt execution
	 *
	 * @param string Error text
	 * @param integer Status code: 'wrong_route', 'unknown_module'
	 * @param integer Status number: 404, 403
	 */
	private function halt( $error_text, $status_code = 'wrong_route', $status_number = 404 )
	{
		// Set status code number:
		header_http_response( $status_number );

		// Add info about error:
		$this->add_response( 'code', $status_code );
		$this->add_response( 'message', $error_text );
		$this->add_response( 'data', array( 'status' => $status_number ) );

		// Print out response in JSON format:
		$this->display_response();

		// Halt execution here:
		exit();
	}


	/**
	 * Halt execution with text from $Messages
	 *
	 * @param integer Status code: 'wrong_route', 'unknown_module'
	 * @param integer Status number: 404, 403
	 * @param string Message types, separated by comma: 'success', 'warning', 'error', 'note'
	 */
	private function halt_with_Messages( $status_code = 'no_access', $status_number = 403, $message_types = 'error,warning' )
	{
		global $Messages;

		$message_types = explode( ',', $message_types );

		$halt_messages = array();
		$Messages->close_group(); // Make sure any open message group are closed
		foreach( $Messages->messages_text as $m => $message_text )
		{
			if( in_array( $Messages->messages_type[ $m ], $message_types ) )
			{	// Get this message for halt because of message type:
				$halt_messages[] = $message_text;
			}
		}

		$halt_messages = empty( $halt_messages ) ? $status_code : implode( ' ', $halt_messages );

		// Halt execution:
		$this->halt( $halt_messages, $status_code, $status_number );
	}


	/**
	 * Add new element in response array
	 *
	 * @param string Key or Value ( if second param is NULL )
	 * @param mixed Value
	 * @param string Type of new added item: 'raw', 'integer', 'array'
	 */
	private function add_response( $key, $value = NULL, $type = 'raw' )
	{
		if( $value === NULL )
		{	// Use auto key:
			$this->response[] = $key;
		}
		else
		{	// Use defined key:
			switch( $type )
			{
				case 'array':
					if( ! isset( $this->response[ $key ] ) || ! is_array( $this->response[ $key ] ) )
					{	// Initialize array:
						$this->response[ $key ] = array();
					}
					// Add new item to array:
					$this->response[ $key ][] = $value;
					break;

				case 'integer':
					$this->response[ $key ] = intval( $value );
					break;

				default:
					// raw (does nothing)
					$this->response[ $key ] = $value;
			}
		}
	}


	/**
	 * Print out the response array in JSON format:
	 */
	private function display_response()
	{
		// Send the predefined cookies:
		evo_sendcookies();

		// Set JSON content type:
		headers_content_mightcache( 'application/json' );

		// Convert array to JSON format and print out:
		echo json_encode( $this->response );
	}


	/**** MODULE COLLECTIONS ---- START ****/


	/**
	 * Call module to prepare request for collections
	 */
	private function module_collections()
	{
		global $Collection, $Blog;

		switch( $_SERVER['REQUEST_METHOD'] )
		{
			case 'GET':
				// List of valid resources
				$valid_resources = array( '', 'view', 'items', 'posts', 'search', 'assignees' );
				break;

			case 'PUT':
				// List of valid resources
				$valid_resources = array( 'favorite', 'items_flag' );
				break;

			default:
				$this->halt( 'Request method not supported for the requested resource', 'wrong_request', 405 );
				// Exit here
		}

		if( ! empty( $this->args[1] ) )
		{
			$coll_controller = ! empty( $this->args[2] ) ? $this->args[2] : 'view';
		}
		else
		{
			$coll_controller = '';
		}

		if( ! empty( $coll_controller ) )
		{	// Initialize data for request of the selected collection:

			// Collection urlname:
			$coll_urlname = $this->args[1];

			$BlogCache = & get_BlogCache();
			if( ( $Collection = $Blog = $BlogCache->get_by_urlname( $coll_urlname, false ) ) === false )
			{	// Collection is not detected in DB by requested url name:
				$this->halt( 'No collection found in DB by requested url name "'.$coll_urlname.'"', 'unknown_collection', 404 );
				// Exit here.
			}

			// Check access to the requested collection:
			$allow_access = $Blog->get_setting( 'allow_access' );
			if( $allow_access != 'public' )
			{	// If the collection is not public:
				if( ! is_logged_in() )
				{	// Only logged in users have an access to the collection:
					$this->halt( T_('You need to log in before you can access this section.'), 'access_requires_login', 403 );
					// Exit here.
				}
				elseif( $allow_access == 'members' )
				{	// Check if current user is member of the collection:
					global $current_User;

					if( ! $current_User->check_perm( 'blog_ismember', 'view', false, $Blog->ID ) )
					{	// Current user cannot access to the collection:
						$this->halt( T_('You are not a member of this section, therefore you are not allowed to access it.'), 'access_denied', 403 );
						// Exit here.
					}
				}
			}
		}

		// Try to get an object ID and action for example request "<baseurl>/api/v1/collections/<collname>/items/<id>/action":
		$object_ID = empty( $this->args[3] ) ? NULL : intval( $this->args[3] );
		$object_action = empty( $this->args[4] ) ? NULL : $this->args[4];
		$coll_controller_params = array();

		if( $object_ID && $object_action )
		{	// Set controller name for single object and action:
			$coll_controller = $coll_controller.'_'.$object_action;
			$coll_controller_params[] = $object_ID;
		}

		if( ! method_exists( $this, 'controller_coll_'.$coll_controller ) )
		{	// Unknown controller:
			$this->halt( 'Unknown collection controller "'.$coll_controller.'"', 'unknown_controller' );
			// Exit here.
		}

		if( ! in_array( $coll_controller, $valid_resources, true ) )
		{ // Invalid request method:
			$this->halt( 'Request method not supported for the requested resource', 'wrong_request', 405 );
			// Exit here.
		}

		// Call collection controller to prepare current request:
		call_user_func_array( array( $this, 'controller_coll_'.$coll_controller ), $coll_controller_params );
	}


	/**
	 * Default controller/handler
	 * erhsatingin > function name is a bit wonky, perhaps there's a better way to go around this.
	 */
	private function controller_coll_()
	{
		global $DB, $Settings, $current_User;

		$api_page = param( 'page', 'integer', 1 );
		$api_per_page = param( 'per_page', 'integer', 10 );
		$api_q = param( 'q', 'string', '' );
		$api_fields = param( 'fields', 'string', 'shortname' ); // 'id', 'shortname'
		$api_restrict = param( 'restrict', 'string', '' ); // 'available_fileroots' - Load only collections with available file roots for current user
		$api_filter = param( 'filter', 'string', 'public' ); // 'public' - Load only collections which can be viewed for current user

		if( $api_filter == 'public' )
		{	// SQL to get ONLY public collections:
			$BlogCache = & get_BlogCache();
			$SQL = $BlogCache->get_public_colls_SQL();
			$count_SQL = $BlogCache->get_public_colls_SQL();
			$count_SQL->SELECT( 'COUNT( blog_ID )' );
		}
		else
		{	// SQL to get ALL collections:
			$sql_order_by = gen_order_clause( $Settings->get( 'blogs_order_by' ), $Settings->get( 'blogs_order_dir' ), 'blog_', 'blog_ID' );
			$SQL = new SQL();
			$SQL->SELECT( '*' );
			$SQL->FROM( 'T_blogs' );
			$SQL->ORDER_BY( $sql_order_by );
			$count_SQL = new SQL( 'Get a count of collections for search request' );
			$count_SQL->SELECT( 'COUNT( blog_ID )' );
			$count_SQL->FROM( 'T_blogs' );
			$count_SQL->ORDER_BY( $sql_order_by );
		}

		if( ! empty( $api_q ) )
		{	// Search collections by keyword:
			$search_sql_where = array();
			$search_fields = empty( $api_fields ) ? array( 'shortname' ) : explode( ',', $api_fields );
			foreach( $search_fields as $search_field )
			{
				switch( strtolower( $search_field ) )
				{
					case 'id':
						$search_sql_where[] = 'blog_ID = '.$DB->quote( $api_q );
						break;

					case 'shortname':
						$search_sql_where[] = 'blog_shortname LIKE '.$DB->quote( '%'.$api_q.'%' );
						break;
				}
			}

			$search_sql_where = implode( ' OR ', $search_sql_where );
			$SQL->WHERE_and( $search_sql_where );
			$count_SQL->WHERE_and( $search_sql_where );
		}

		$collections = array();
		if( $api_restrict == 'available_fileroots' &&
		    (
		      ! is_logged_in() ||
		      ! $current_User->check_perm( 'admin', 'restricted' ) ||
		      ! $current_User->check_perm( 'files', 'view' )
		    ) )
		{	// Anonymous user has no access to file roots AND also if current use has no access to back-office or to file manager:
			$result_count = 0;
		}
		else
		{
			if( $api_restrict == 'available_fileroots' )
			{	// Restrict collections by available file roots for current user:

				// SQL analog for $current_User->check_perm( 'blogs', 'view' ) || $current_User->check_perm( 'files', 'edit' ):
				$current_User->get_Group();
				$check_perm_blogs_view_files_edit_SQL = new SQL();
				$check_perm_blogs_view_files_edit_SQL->SELECT( 'grp_ID' );
				$check_perm_blogs_view_files_edit_SQL->FROM( 'T_groups' );
				$check_perm_blogs_view_files_edit_SQL->FROM_add( 'LEFT JOIN T_groups__groupsettings ON grp_ID = gset_grp_ID AND gset_name = "perm_files"' );
				$check_perm_blogs_view_files_edit_SQL->WHERE( 'grp_ID = '.$current_User->Group->ID );
				$check_perm_blogs_view_files_edit_SQL->WHERE_and( 'grp_perm_blogs IN ( "viewall", "editall" ) OR gset_value IS NULL OR gset_value IN ( "all", "edit" )' );
				$restrict_available_fileroots_sql = '( '.$check_perm_blogs_view_files_edit_SQL->get().' )';

				// SQL analog for $current_User->check_perm( 'blog_media_browse', 'view', false, $Blog ):
				$check_perm_blog_media_browse_user_SQL = new SQL();
				$check_perm_blog_media_browse_user_SQL->SELECT( 'bloguser_blog_ID' );
				$check_perm_blog_media_browse_user_SQL->FROM( 'T_coll_user_perms' );
				$check_perm_blog_media_browse_user_SQL->WHERE( 'bloguser_user_ID = '.$current_User->ID );
				$check_perm_blog_media_browse_user_SQL->WHERE_and( 'bloguser_perm_media_browse <> 0' );
				$check_perm_blog_media_browse_group_SQL = new SQL();
				$check_perm_blog_media_browse_group_SQL->SELECT( 'bloggroup_blog_ID' );
				$check_perm_blog_media_browse_group_SQL->FROM( 'T_coll_group_perms' );
				$check_perm_blog_media_browse_group_SQL->WHERE( '( bloggroup_group_ID = '.$current_User->Group->ID.'
					OR bloggroup_group_ID IN ( SELECT sug_grp_ID FROM T_users__secondary_user_groups WHERE sug_user_ID = '.$current_User->ID.' ) )' );
				$check_perm_blog_media_browse_group_SQL->WHERE_and( 'bloggroup_perm_media_browse <> 0' );
				$restrict_available_fileroots_sql .= ' OR blog_owner_user_ID = '.$current_User->ID
					.' OR ( blog_advanced_perms <> 0 AND ( '
					.'        blog_ID IN ( '.$check_perm_blog_media_browse_user_SQL->get().' ) OR '
					.'        blog_ID IN ( '.$check_perm_blog_media_browse_group_SQL->get().' )'
					.'      )'
					.'    )';

				$SQL->WHERE_and( $restrict_available_fileroots_sql );
				$count_SQL->WHERE_and( $restrict_available_fileroots_sql );
			}

			$result_count = intval( $DB->get_var( $count_SQL ) );
		}

		// Prepare pagination:
		if( $result_count > $api_per_page )
		{	// We will have multiple search result pages:
			if( $api_page < 1 )
			{	// Limit by min page:
				$api_page = 1;
			}
			$total_pages = ceil( $result_count / $api_per_page );
			if( $api_page > $total_pages )
			{	// Limit by max page:
				$api_page = $total_pages;
			}
		}
		else
		{	// Only one page of results:
			$current_page = 1;
			$total_pages = 1;
		}

		$BlogCache = & get_BlogCache();
		$BlogCache->clear();

		if( $result_count > 0 )
		{	// Select collections only from current page:
			$SQL->LIMIT( ( ( $api_page - 1 ) * $api_per_page ).', '.$api_per_page );
			$BlogCache->load_by_sql( $SQL );
		}

		$this->add_response( 'found', $result_count, 'integer' );
		$this->add_response( 'page', $api_page, 'integer' );
		$this->add_response( 'page_size', $api_per_page, 'integer' );
		$this->add_response( 'pages_total', $total_pages, 'integer' );

		foreach( $BlogCache->cache as $Blog )
		{	// Add each collection row in the response array:
			$this->add_response( 'colls', array(
					'id'        => intval( $Blog->ID ),
					'urlname'   => $Blog->get( 'urlname' ),
					'kind'      => $Blog->get( 'type' ),
					'shortname' => $Blog->get( 'shortname' ),
					'name'      => $Blog->get( 'name' ),
					'tagline'   => $Blog->get( 'tagline' ),
					'desc'      => $Blog->get( 'longdesc' ),
				), 'array' );
		}
	}


	/**
	 * Call collection controller to view collection information
	 */
	private function controller_coll_view()
	{
		global $current_User;

		$coll_urlname = empty( $this->args[1] ) ? 0 : $this->args[1];

		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_urlname( $coll_urlname, false );

		$collection_data = array(
			'id'        => intval( $Blog->ID ),
			'urlname'   => $Blog->get( 'urlname' ),
			'kind'      => $Blog->get( 'type' ),
			'shortname' => $Blog->get( 'shortname' ),
			'name'      => $Blog->get( 'name' ),
			'tagline'   => $Blog->get( 'tagline' ),
			'desc'      => $Blog->get( 'longdesc' ) );

		$this->response = $collection_data;
	}


	/**
	 * Call collection controller to prepare request for items with ANY types
	 *
	 * @param array Force filters of request
	 */
	private function controller_coll_items( $force_filters = array() )
	{
		global $Collection, $Blog;

		// Get param to limit number posts per page:
		$api_per_page = param( 'per_page', 'integer', 10 );

		// Try to get a post ID for request "<baseurl>/api/v1/collections/<collname>/items/<id>":
		$post_ID = empty( $this->args[3] ) ? 0 : $this->args[3];

		$ItemList2 = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), $api_per_page, 'ItemCache', '' );

		if( $post_ID )
		{	// Get only one requested post:
			$ItemList2->set_filters( array( 'post_ID' => $post_ID ), true, true );
		}
		else
		{	// Load all available params from request to filter the posts list:
			$ItemList2->load_from_Request( false );
		}

		if( $ItemList2->filters['types'] == $ItemList2->default_filters['types'] )
		{	// Allow all post types by default for this request:
			$ItemList2->set_filters( array( 'itemtype_usage' => NULL ), true, true );
		}

		if( ! empty( $force_filters ) )
		{	// Force filters:
			$ItemList2->set_filters( $force_filters, true, true );
		}

		// Run the items list query:
		$ItemList2->query();

		if( ! $post_ID )
		{	// Add data of posts list to response:
			$this->add_response( 'found', $ItemList2->total_rows, 'integer' );
			$this->add_response( 'page', $ItemList2->page, 'integer' );
			$this->add_response( 'page_size', $ItemList2->limit, 'integer' );
			$this->add_response( 'pages_total', $ItemList2->total_pages, 'integer' );
		}

		// Add each post row in the response array:
		while( $Item = & $ItemList2->get_next() )
		{
			// Get all(1000) item attachemnts:
			$attachments = array();
			$LinkOwner = new LinkItem( $Item );
			if( $LinkList = $LinkOwner->get_attachment_LinkList( 1000 ) )
			{
				while( $Link = & $LinkList->get_next() )
				{
					if( ! ( $File = & $Link->get_File() ) )
					{	// No File object
						global $Debuglog;
						$Debuglog->add( sprintf( 'Link ID#%d of item #%d does not have a file object!', $Link->ID, $Item->ID ), array( 'error', 'files' ) );
						continue;
					}

					if( ! $File->exists() )
					{	// File doesn't exist
						global $Debuglog;
						$Debuglog->add( sprintf( 'File linked to item #%d does not exist (%s)!', $Item->ID, $File->get_full_path() ), array( 'error', 'files' ) );
						continue;
					}

					$attachments[] = array(
							'link_ID'  => intval( $Link->ID ),
							'file_ID'  => intval( $File->ID ),
							'type'     => strval( $File->is_dir() ? 'dir' : $File->type ),
							'position' => $Link->get( 'position' ),
							'name'     => $File->get_name(),
							'url'      => $File->get_url(),
							'title'    => strval( $File->get( 'title' ) ),
							'alt'      => strval( $File->get( 'alt' ) ),
							'desc'     => strval( $File->get( 'desc' ) ),
						);
				}
			}

			// Initialize data for each item:
			$item_data = array(
					'id'          => intval( $Item->ID ),
					'datestart'   => $Item->get( 'datestart' ),
					'urltitle'    => $Item->get( 'urltitle' ),
					'type'        => $Item->get_type_setting( 'name' ),
					'title'       => $Item->get( 'title' ),
					'content'     => $Item->get_prerendered_content( 'htmlbody' ),
					'excerpt'     => $Item->get( 'excerpt' ),
					'teaser'      => $Item->get_content_teaser(),
					'URL'         => $Item->get_permanent_url( '', '', '&' ),
					'attachments' => $attachments,
				);

			if( $post_ID )
			{	// If only one post is requested then response should as one level array with post fields:
				$this->response = $item_data;
			}
			else
			{	// Add data of each post in separate array of response:
				$this->add_response( 'items', $item_data, 'array' );
			}
		}

		if( empty( $this->response ) )
		{	// No posts detected:
			if( $post_ID )
			{	// Wrong post request:
				$this->halt( 'Invalid post ID', 'post_invalid_id', 404 );
				// Exit here.
			}
			else
			{	// No posts found:
				$this->halt( 'No posts found for requested collection', 'no_posts', 404 );
				// Exit here.
			}
		}
	}


	/**
	 * Call collection controller to prepare request for items ONLY with item type "post"
	 */
	private function controller_coll_posts()
	{
		$this->controller_coll_items( array(
				'itemtype_usage' => 'post', // Keep content post types, Exclude pages, intros, sidebar links and ads
			) );
	}


	/**
	 * Call collection controller to search the chapters, posts, comments and tags
	 */
	private function controller_coll_search()
	{
		global $Collection, $Blog, $Session;

		// Get additional params:
		$api_page = param( 'page', 'integer', 1 );
		$api_per_page = param( 'per_page', 'integer', 10 );
		$api_exclude_posts = param( 'exclude_posts', 'string', '' );
		// What types search: 'all', 'item', 'comment', 'category', 'tag'
		// Use separator comma to use several kinds:
		$api_kind = param( 'kind', 'string', 'all' );

		// Load the search functions:
		load_funcs( 'collections/_search.funcs.php' );

		// Set a search string:
		$search_keywords = empty( $this->args[3] ) ? '' : urldecode( $this->args[3] );

		// Try to load existing search results from Session:
		$search_params = $Session->get( 'search_params' );
		$search_result = $Session->get( 'search_result' );
		$search_result_loaded = false;
		if( empty( $search_params )
			|| ( $search_params['search_keywords'] != $search_keywords ) // We had saved search results but for a different search string
			|| ( $search_params['search_blog'] != $Blog->ID ) // We had saved search results but for a different collection
			|| ( isset( $search_params['exclude_posts'] ) && $search_params['exclude_posts'] != $api_exclude_posts ) // We had saved search results but for a different posts excluding
			|| ( $search_result === NULL ) )
		{	// We need to perform a new search:
			$search_params = array(
				'search_keywords' => $search_keywords,
				'search_blog'     => $Blog->ID,
				'exclude_posts'   => $api_exclude_posts,
			);

			// Perform new search:
			$search_result = perform_scored_search( $search_keywords, $api_kind, $api_exclude_posts );

			// Save results into session:
			$Session->set( 'search_params', $search_params );
			$Session->set( 'search_result', $search_result );
			$search_result_loaded = true;
		}

		$search_result = $Session->get( 'search_result' );
		if( empty( $search_result ) )
		{	// Nothing found:
			$this->add_response( 'found', 0, 'integer' );
			$this->add_response( 'page', $api_page, 'integer' );
			$this->add_response( 'page_size', $api_per_page, 'integer' );
			$this->add_response( 'pages_total', 0, 'integer' );
			$this->add_response( 'results', array() );
		}
		else
		{
			// Prepare pagination:
			$result_count = count( $search_result );
			$result_per_page = $api_per_page;
			if( $result_count > $result_per_page )
			{	// We will have multiple search result pages:
				$current_page = $api_page;
				if( $current_page < 1 )
				{
					$current_page = 1;
				}
				$total_pages = ceil( $result_count / $result_per_page );
				if( $api_page > $total_pages )
				{
					$current_page = $total_pages;
				}
			}
			else
			{	// Only one page of results:
				$current_page = 1;
				$total_pages = 1;
			}

			// Set current page indexes:
			$from = ( ( $current_page -1 ) * $result_per_page );
			$to = ( $current_page < $total_pages ) ? ( $from + $result_per_page ) : ( $result_count );

			// Init caches
			$ItemCache = & get_ItemCache();
			$CommentCache = & get_CommentCache();
			$ChapterCache = & get_ChapterCache();

			if( ! $search_result_loaded )
			{	// Search result objects are not loaded into memory yet, load them:
				// Group required object ids by type:
				$required_ids = array();
				for( $index = $from; $index < $to; $index++ )
				{
					$row = $search_result[ $index ];
					if( isset( $required_ids[ $row['type'] ] ) )
					{
						$required_ids[ $row['type'] ][] = $row['ID'];
					}
					else
					{
						$required_ids[ $row['type'] ] = array( $row['ID'] );
					}
				}

				// Load each required object into the corresponding cache:
				foreach( $required_ids as $type => $object_ids )
				{
					switch( $type )
					{
						case 'item':
							$ItemCache->load_list( $object_ids );
							break;

						case 'comment':
							$CommentCache->load_list( $object_ids );
							break;

						case 'category':
							$ChapterCache->load_list( $object_ids );
							break;

						// TODO: we'll probably load "tag" objects once we support tag-synonyms.

						default: // Not handled search result type
							break;
					}
				}
			}

			$this->add_response( 'found', $result_count, 'integer' );
			$this->add_response( 'page', $current_page, 'integer' );
			$this->add_response( 'page_size', $result_per_page, 'integer' );
			$this->add_response( 'pages_total', $total_pages, 'integer' );

			// Get results for current page:
			for( $index = $from; $index < $to; $index++ )
			{
				$row = $search_result[ $index ];

				$result_data = array(
						'kind' => $row['type'],
						'id'   => intval( $row['ID'] ),
					);

				switch( $row['type'] )
				{
					case 'item':
						// Prepare to display an Item:

						$Item = $ItemCache->get_by_ID( $row['ID'], false );

						if( empty( $Item ) )
						{ // This Item was deleted, since the search process was executed
							continue 2; // skip from switch and skip to the next item in loop
						}

						$result_data['title'] = $Item->get_title( array( 'link_type' => 'none' ) );
						$result_data['desc'] = $Item->get_excerpt();
						$result_data['permalink'] = $Item->get_permanent_url( '', '', '&' );
						break;

					case 'comment':
						// Prepare to display a Comment:

						$Comment = $CommentCache->get_by_ID( $row['ID'], false );

						if( empty( $Comment ) || ( $Comment->status == 'trash' ) )
						{ // This Comment was deleted, since the search process was executed
							continue 2; // skip from switch and skip to the next item in loop
						}

						$comment_Item = & $Comment->get_Item();
						$result_data['title'] = $comment_Item->get_title( array( 'link_type' => 'none' ) );
						$result_data['desc'] = excerpt( $Comment->content );
						$result_data['permalink'] = $Comment->get_permanent_url( '&' );
						break;

					case 'category':
						// Prepare to display a Category:

						$Chapter = $ChapterCache->get_by_ID( $row['ID'], false );

						if( empty( $Chapter ) )
						{ // This Chapter was deleted, since the search process was executed
							continue 2; // skip from switch and skip to the next item in loop
						}

						$result_data['title'] = $Chapter->get_name();
						$result_data['desc'] = excerpt( $Chapter->get( 'description' ) );
						$result_data['permalink'] = $Chapter->get_permanent_url( NULL, NULL, 1, NULL, '&' );
						break;

					case 'tag':
						// Prepare to display a Tag:

						list( $tag_name, $post_count ) = explode( ',', $row['name'] );

						$result_data['title'] = $tag_name;
						$result_data['desc'] = sprintf( T_('%d posts are tagged with \'%s\''), $post_count, $tag_name );
						$result_data['permalink'] = url_add_param( $Blog->gen_blogurl(), 'tag='.$tag_name, '&' );
						break;

					default:
						// Other type of result is not implemented

						// TODO: maybe find collections (especially in case of aggregation)? users? files?

						continue 2;
				}

				// Add data of the searched thing to response:
				$this->add_response( 'results', $result_data, 'array' );
			}
		}
	}


	/**
	 * Call collection controller to prepare request for possible assignees
	 *
	 * Request scheme: "<baseurl>/api/v1/collections/<collname>/assignees?q=<search login string>"
	 *
	 * @param integer Item ID
	 */
	private function controller_coll_assignees()
	{
		global $current_User, $Collection, $Blog, $DB;

		if( ! is_logged_in() || ! $current_User->check_perm( 'blog_can_be_assignee', 'edit', false, $Blog->ID ) )
		{	// Check permission: Current user must has a permission to be assignee of the collection:
			$this->halt( 'You are not allowed to view assigness of the collection "'.$Blog->get( 'name' ).'".', 'no_access', 403 );
			// Exit here.
		}

		if( ! $Blog->get_setting( 'use_workflow' ) )
		{	// If workflow is not enabled for the collection:
			$this->halt( 'The collection "'.$Blog->get( 'name' ).'" is not used for workflow.', 'no_access', 403 );
			// Exit here.
		}

		$api_q = trim( urldecode( param( 'q', 'string', '' ) ) );

		/**
		 * sam2kb> The code below decodes percent-encoded unicode string produced by Javascript "escape"
		 * function in format %uxxxx where xxxx is a Unicode value represented as four hexadecimal digits.
		 * Example string "MAMA" (cyrillic letters) encoded with "escape": %u041C%u0410%u041C%u0410
		 * Same word encoded with "encodeURI": %D0%9C%D0%90%D0%9C%D0%90
		 *
		 * jQuery hintbox plugin uses "escape" function to encode URIs
		 *
		 * More info here: http://en.wikipedia.org/wiki/Percent-encoding#Non-standard_implementations
		 */
		if( preg_match( '~%u[0-9a-f]{3,4}~i', $api_q ) && version_compare(PHP_VERSION, '5', '>=') )
		{	// Decode UTF-8 string (PHP 5 and up)
			$api_q = preg_replace( '~%u([0-9a-f]{3,4})~i', '&#x\\1;', $api_q );
			$api_q = html_entity_decode( $api_q, ENT_COMPAT, 'UTF-8' );
		}

		if( empty( $api_q ) )
		{	// Don't allow empty request:
			$this->halt( 'Please enter at least one char to find assignees', 'no_access', 403 );
			// Exit here.
		}

		if( $Blog->get( 'advanced_perms' ) )
		{	// Load group and user permissions ONLY if collection advanced permissions are enabled:

			// Get users which can be assignees of the collection:
			$user_perms_SQL = new SQL();
			$user_perms_SQL->SELECT( 'user_login' );
			$user_perms_SQL->FROM( 'T_users' );
			$user_perms_SQL->FROM_add( 'INNER JOIN T_coll_user_perms ON user_ID = bloguser_user_ID' );
			$user_perms_SQL->WHERE( 'bloguser_blog_ID = '.$DB->quote( $Blog->ID ) );
			$user_perms_SQL->WHERE_and( 'bloguser_can_be_assignee <> 0' );
			$users_sql[] = $user_perms_SQL->get();

			// Get users which primary groups can be assignees of the collection:
			$group_perms_SQL = new SQL();
			$group_perms_SQL->SELECT( 'user_login' );
			$group_perms_SQL->FROM( 'T_users' );
			$group_perms_SQL->FROM_add( 'INNER JOIN T_coll_group_perms ON user_grp_ID = bloggroup_group_ID' );
			$group_perms_SQL->WHERE( 'bloggroup_blog_ID = '.$DB->quote( $Blog->ID ) );
			$group_perms_SQL->WHERE_and( 'bloggroup_can_be_assignee <> 0' );
			$users_sql[] = $group_perms_SQL->get();

			// Get users which secondary groups can be assignees of the collection:
			$secondary_group_perms_SQL = new SQL();
			$secondary_group_perms_SQL->SELECT( 'user_login' );
			$secondary_group_perms_SQL->FROM( 'T_users' );
			$secondary_group_perms_SQL->FROM_add( 'INNER JOIN T_users__secondary_user_groups ON sug_user_ID = user_ID' );
			$secondary_group_perms_SQL->FROM_add( 'INNER JOIN T_coll_group_perms ON sug_grp_ID = bloggroup_group_ID' );
			$secondary_group_perms_SQL->WHERE( 'bloggroup_blog_ID = '.$DB->quote( $Blog->ID ) );
			$secondary_group_perms_SQL->WHERE_and( 'bloggroup_can_be_assignee <> 0' );
			$users_sql[] = $secondary_group_perms_SQL->get();
		}

		// Get collection's owner because it can be assignee by default:
		$owner_SQL = new SQL();
		$owner_SQL->SELECT( 'user_login' );
		$owner_SQL->FROM( 'T_users' );
		$owner_SQL->FROM_add( 'INNER JOIN T_blogs ON blog_owner_user_ID = user_ID' );
		$owner_SQL->WHERE( 'blog_ID = '.$DB->quote( $Blog->ID ) );
		$users_sql[] = $owner_SQL->get();

		// Get assignees which primary groups have a setting to EDIT ALL collections:
		$group_setting_SQL = new SQL();
		$group_setting_SQL->SELECT( 'user_login' );
		$group_setting_SQL->FROM( 'T_users' );
		$group_setting_SQL->FROM_add( 'INNER JOIN T_groups ON user_grp_ID = grp_ID' );
		$group_setting_SQL->WHERE( 'grp_perm_blogs = "editall"' );
		$users_sql[] = $group_setting_SQL->get();

		// Union sql queries to execute one query and save an order as one list:
		$users_sql = 'SELECT user_login'
			.' FROM ( ( '.implode( ' ) UNION ( ', $users_sql ).' ) ) AS uu'
			.' WHERE uu.user_login LIKE "'.$DB->escape( $api_q ).'%"'
			.' ORDER BY user_login'
			.' LIMIT 10';

		$user_logins = $DB->get_col( $users_sql );

		// Send users logins array as response:
		$this->add_response( 'list', $user_logins );
	}


	/**
	 * Call collection controller to toggle favorite status
	 *
	 * Request scheme: "<baseurl>/api/v1/collections/<collname>/favorite
	 *
	 */
	private function controller_coll_favorite()
	{
		global $current_User, $Collection, $Blog;

		if( ! is_logged_in() )
		{	// Check permission: Current user must be logged in
			$this->halt( 'You are not allowed to set the collection "'.$Blog->get( 'name' ).'" as a favorite.', 'no_access', 403 );
			// Exit here.
		}

		parse_str( file_get_contents( 'php://input' ), $data );
		$setting = param_format( $data['setting'], 'integer' );

		$r = $Blog->favorite( $current_User->ID, $setting );

		if( is_null( $r ) )
		{
			$this->add_response( 'status', 'fail', 'string' );
			$this->add_response( 'errorMsg', T_('Unable to set collection favorite status') );
		}
		else
		{
			$this->add_response( 'status', 'ok', 'string' );
			$this->add_response( 'setting', $setting );
		}
	}


	/**
	 * Call item controller to flag by current user
	 *
	 * @param integer Item ID
	 */
	private function controller_coll_items_flag( $item_ID )
	{
		global $Collection, $Blog;

		$ItemCache = & get_ItemCache();
		if( ( $Item = & $ItemCache->get_by_ID( $item_ID, false, false ) ) === false )
		{	// Item is not detected in DB by requested ID:
			$this->halt( 'No item found in DB by requested ID #'.$item_ID, 'unknown_item', 404 );
			// Exit here.
		}

		if( $Item->get_blog_ID() != $Blog->ID )
		{	// Item should be called for current collection:
			$this->halt( 'You request item #'.$Item->ID.' from another collection "'.$Blog->get( 'urlname' ).'"', 'wrong_item_coll', 403 );
			// Exit here.
		}

		if( ! $Item->can_flag() )
		{	// Don't display the flag button if it is not allowed by some reason:
			$this->halt( 'You cannot flag the item #'.$Item->ID, 'cannot_flag_item', 403 );
		}

		// Flag or unflag item for current user:
		$Item->update_flag();

		// Return current state of flag:
		$this->add_response( 'flag', $Item->get_user_data( 'item_flag' ), 'integer' );
	}


	/**** MODULE COLLECTIONS ---- END ****/


	/**** MODULE USERS ---- START ****/


	/**
	 * Call module to prepare request for users
	 */
	private function module_users()
	{
		switch( $_SERVER['REQUEST_METHOD'] )
		{
			case 'GET':
				// Check for valid controllers
				$user_controller = '';
				$user_ID = NULL;

				if( ! empty( $this->args[1] ) )
				{
					if( ! empty( $this->args[2] ) )
					{
						$user_controller = $this->args[2];
					}
					else
					{
						if( preg_match( '/^\d+$/', $this->args[1] ) )
						{ // args[1] should be a positive integer and not just any number:
							$user_ID = intval( $this->args[1] );
							$user_controller = 'view';
						}
						else
						{
							$user_controller = $this->args[1];
						}
					}
				}
				else
				{ // default handler
					$user_controller = '';
				}

				$valid_resources = array( '', 'view', 'recipients', 'autocomplete', 'logins', 'search' );
				if( isset( $user_ID ) )
				{ // Set controller to view the requested user profile:
					$default_controller = 'view';
				}
				else
				{
					$default_controller = $user_controller;
				}
				break;

			case 'POST':
				// check for valid controllers
				$valid_resources = array( 'save' );
				$default_controller = 'save';
				break;

			case 'DELETE':
				if( empty( $this->args[1] ) )
				{
					$this->halt( 'Missing user ID', 'user_missing_id', 400 );
					// Exit here
				}

				$valid_resources = array( 'delete' );
				$default_controller = 'delete';
				break;

			default:
				$this->halt( 'Request method not supported for the requested resource', 'wrong_request', 405 );
				// Exit here
		}

		$user_controller = $default_controller;

		if( ! method_exists( $this, 'controller_user_'.$user_controller ) )
		{	// Unknown controller:
			$this->halt( 'Unknown user controller "'.$user_controller.'"', 'unknown_controller' );
			// Exit here.
		}

		if( ! in_array( $user_controller, $valid_resources, true ) )
		{ // Invalid request method:
			$this->halt( 'Request method not supported for the requested resource', 'wrong_request', 405 );
			// Exit here.
		}

		// Call collection controller to prepare current request:
		$this->{'controller_user_'.$user_controller}();
	}


	/**
	 * Default controller/handler
	 */
	private function controller_user_()
	{
		global $Settings;

		$api_restrict = param( 'restrict', 'string', '' );

		if( $api_restrict == 'available_fileroots' )
		{	// Check if current user has an access to file roots of other users:
			global $current_User;
			if( is_logged_in() )
			{	// Check perms for logged in user:
				if( ! ( $current_User->check_perm( 'users', 'moderate' ) && $current_User->check_perm( 'files', 'all' ) ) )
				{	// Current user has an access only to file root of own account:
					$user_filters = array( 'userids' => array( $current_User->ID ) );
				}
				// otherwise current user has an access to file roots of all users
			}
			else
			{	// Anonymous user has no access to file roots:
				$user_filters = array( 'userids' => array( -1 ) );
			}
		}
		else
		{	// Default restriction:
			if( ( $access_error_message = check_access_users_list( 'api' ) ) !== true )
			{	// Current user has no access to public list of the users,
				// Display error message:
				$this->halt( $access_error_message, 'no_access', 403 );
				// Exit here.
			}
		}

		// Get param to limit number users per page:
		$api_per_page = param( 'per_page', 'integer', 10 );

		// Get user list params:
		$api_list_params = param( 'list_params', 'array:string', array() );

		// Alias for filter param 'keywords':
		$api_q = param( 'q', 'string', NULL );
		if( $api_q !== NULL )
		{
			set_param( 'keywords', $api_q );
		}

		// Create result set:
		load_class( 'users/model/_userlist.class.php', 'UserList' );
		$UserList = new UserList( 'api_', $api_per_page, '', $api_list_params );
		$UserList->load_from_Request();

		if( ! empty( $user_filters ) )
		{	// Filter list:
			$UserList->set_filters( $user_filters, true, true );
		}

		// Execute query:
		$UserList->query();

		// Add data of users list to response:
		$this->add_response( 'found', $UserList->total_rows, 'integer' );
		$this->add_response( 'page', $UserList->page, 'integer' );
		$this->add_response( 'page_size', $UserList->limit, 'integer' );
		$this->add_response( 'pages_total', $UserList->total_pages, 'integer' );

		// Add each user row in the response array:
		while( $User = & $UserList->get_next() )
		{
			// ID:
			$user_data = array( 'id' => intval( $User->ID ) );
			// Picture:
			if( $Settings->get( 'allow_avatars' ) )
			{	// Only if it is allowed by general settings:
				$user_picture = $User->get_avatar_imgtag( 'crop-top-48x48' );
				if( preg_match( '#src="([^"]+)"#', $user_picture, $user_picture ) )
				{
					$user_data['picture'] = $user_picture[1];
				}
			}
			// Login:
			$user_data['login'] = $User->get( 'login' );
			// Full name:
			$user_data['fullname'] = $User->get( 'fullname' );
			// City:
			$user_data['city'] = $User->get_city_name();

			// Add each user row in the response array:
			$this->add_response( 'users', $user_data, 'array' );
		}

		if( empty( $this->response ) )
		{	// No users found:
			$this->halt( 'No users found', 'no_users', 404 );
			// Exit here.
		}
	}


	/**
	 * Call user controller to view a profile of the requested user
	 */
	private function controller_user_view()
	{
		global $current_User;

		// Get an user ID for request "GET <baseurl>/api/v1/users/<id>":
		$user_ID = intval( empty( $this->args[1] ) ? 0 : $this->args[1] );

		if( ( $access_error_message = check_access_user_profile( $user_ID, 'api' ) ) !== true )
		{	// Current user has no access to public list of the users,
			// Display error message:
			$this->halt( $access_error_message, 'no_access', 403 );
			// Exit here.
		}

		$UserCache = & get_UserCache();
		$User = & $UserCache->get_by_ID( $user_ID, false, false );

		if( ! $User )
		{	// Wrong user request:
			$this->halt( 'Invalid user ID', 'user_invalid_id', 404 );
			// Exit here.
		}

		// ID:
		$user_data = array( 'id' => intval( $User->ID ) );
		// Pictures:
		// Main Picture:
		$user_picture = $User->get_avatar_imgtag( is_logged_in() ? 'crop-top-320x320' : 'crop-top-320x320-blur-8' );
		$user_picture = ( preg_match( '#src="([^"]+)"#', $user_picture, $user_picture ) ) ? $user_picture[1] : '';
		$user_data['picture'] = $user_picture;
		// Other pictures:
		$user_data['pictures'] = array();
		if( is_logged_in() && $current_User->check_status( 'can_view_user', $user_ID ) )
		{ // Display other pictures, but only for logged in and activated users:
			$user_pic_links = $User->get_avatar_Links();
			foreach( $user_pic_links as $user_pic_Link )
			{
				$user_pic_url = $user_pic_Link->get_tag( array(
						'before_image'        => '',
						'before_image_legend' => NULL,
						'after_image_legend'  => NULL,
						'after_image'         => '',
						'image_size'          => 'crop-top-80x80',
					) );
				if( ( preg_match( '#src="([^"]+)"#', $user_pic_url, $user_pic_url ) ) )
				{	// Extract image url:
					$user_data['pictures'][] = $user_pic_url[1];
				}
			}
		}

		// Login:
		$user_data['login'] = $User->get( 'login' );
		// First name:
		$user_data['firstname'] = $User->get( 'firstname' );
		// Last name:
		$user_data['lastname'] = $User->get( 'lastname' );
		// Gender:
		$user_data['gender'] = $User->get( 'gender' );

		// Location:
		$user_data['location'] = array(
				'country'   => empty( $User->ctry_ID )  ? NULL : array( 'id' => intval( $User->ctry_ID ),  'name' => $User->get_country_name() ),
				'region'    => empty( $User->rgn_ID )   ? NULL : array( 'id' => intval( $User->rgn_ID ),   'name' => $User->get_region_name() ),
				'subregion' => empty( $User->subrg_ID ) ? NULL : array( 'id' => intval( $User->subrg_ID ), 'name' => $User->get_subregion_name() ),
				'city'      => empty( $User->city_ID )  ? NULL : array( 'id' => intval( $User->city_ID ),  'name' => $User->get_city_name() )
			);

		// Organizations:
		$user_data['organizations'] = array();
		$user_organizations = $User->get_organizations();
		foreach( $user_organizations as $org )
		{
			$user_data['organizations'][] = array(
					'name' => $org->name,
					'url'  => $org->url,
				);
		}

		// User fields:
		$user_data['userfields'] = array();
		// Load the user fields:
		$User->userfields_load();
		foreach( $User->userfields as $userfield )
		{
			if( ! isset( $user_data['userfields'][ $userfield->ufgp_name ] ) )
			{	// Initalize an array for each group of the user fields:
				$user_data['userfields'][ $userfield->ufgp_name ] = array();
			}

			$user_data['userfields'][ $userfield->ufgp_name ][] = array(
					'code'  => $userfield->ufdf_code,
					'name'  => $userfield->ufdf_name,
					'value' => $userfield->uf_varchar
				);
		}

		// Add user data in the response:
		$this->response = $user_data;
	}


	/**
	 * Call user controller to update the requested user OR create new one
	 */
	private function controller_user_save()
	{
		global $current_User;

		if( ! is_logged_in() )
		{	// Must be logged in
			$this->halt( T_( 'You are not logged in.' ), 'no_access', 403 );
			// Exit here.
		}

		// Get an user ID for request "POST <baseurl>/api/v1/users/<id>":
		$user_ID = empty( $this->args[1] ) ? 0 : intval( $this->args[1] );

		if( $user_ID > 0 )
		{	// Initialize User object to update it:
			$UserCache = & get_UserCache();
			$edited_User = & $UserCache->get_by_ID( $user_ID, false, false );

			if( ! $edited_User )
			{	// Wrong user request:
				$this->halt( 'Invalid user ID', 'user_invalid_id' );
				// Exit here.
			}

			if( ! $current_User->can_moderate_user( $edited_User->ID )
			    && $edited_User->ID != $current_User->ID )
			{	// Current user has no permission to delate the requested user:
				$this->halt( T_('You have no permission to edit other users!'), 'no_access', 403 );
				// Exit here.
			}
		}
		else
		{ // Initialize User object to create new one:
			$edited_User = new User();
		}

		// Clear all messages in order to keep only from function User->update_from_request() below:
		global $Messages;
		$Messages->clear();

		// Create new user or Update the requested user:
		$is_new_user = ( $edited_User->ID == 0 ? true : false );
		$result = $edited_User->update_from_request( $is_new_user );
		if( $result !== true )
		{	// There are errors on update the requested user:
			$Messages->close_group(); // Make sure any open message group are closed
			$this->halt( $Messages->messages_text[0], 'update_failed', 403 );
			// Exit here.
		}
		else
		{	// The requested user has been updated successfully
			$Messages->close_group(); // Make sure any open message group are closed
			$this->halt( $Messages->messages_text[0], 'update_success', ( $is_new_user ? 201 : 200 ) );
			// Exit here.
		}
	}


	/**
	 * Call user controller to delete the requested user
	 */
	private function controller_user_delete()
	{
		global $current_User;

		if( ! is_logged_in() || ! $current_User->check_perm( 'users', 'edit' ) )
		{	// Current user has no permission to delete the requested user:
			$this->halt( T_('You have no permission to edit other users!'), 'no_access', 403 );
			// Exit here.
		}

		// Get an user ID for request "DELETE <baseurl>/api/v1/users/<id>":
		$user_ID = intval( $this->args[1] );

		$UserCache = & get_UserCache();
		$User = & $UserCache->get_by_ID( $user_ID, false, false );

		if( ! $User )
		{	// Wrong user request:
			$this->halt( 'Invalid user ID', 'user_invalid_id', 404 );
			// Exit here.
		}

		if( $User->ID == $current_User->ID )
		{
			$this->halt( T_('You can\'t delete yourself!'), 'no_access', 403 );
			// Exit here.
		}
		if( $User->ID == 1 )
		{
			$this->halt( T_('You can\'t delete User #1!'), 'no_access', 403 );
			// Exit here.
		}

		// Clear all messages in order to keep only from function User->check_delete() below:
		global $Messages;
		$Messages->clear();

		if( ! $User->check_delete( sprintf( T_('Cannot delete User &laquo;%s&raquo;'), $User->get( 'login' ) ) ) )
		{	// There are restrictions on delete the requested user:
			$Messages->close_group(); // Make sure any open message group are closed
			$this->halt( strip_tags( $Messages->messages_text[0] ), 'delete_restriction', 403 );
			// Exit here.
		}

		if( $User->dbdelete( $Messages ) !== false )
		{	// The requested user has been deleted successfully
			$this->halt( sprintf( T_('User &laquo;%s&raquo; deleted.'), $User->get( 'login' ) ), 'delete_success', 200 );
			// Exit here.
		}
		else
		{	// Cannot delete the requested user
			$this->halt( sprintf( T_('Cannot delete User &laquo;%s&raquo;'), $User->get( 'login' ) ), 'delete_failed', 403 );
			// Exit here.
		}
	}


	/**
	 * Call user controller to search recipients
	 */
	private function controller_user_recipients()
	{
		global $current_User, $DB;

		if( ! is_logged_in() || ! $current_User->check_perm( 'perm_messaging', 'reply' ) )
		{	// Check permission: User is not allowed to view threads
			$this->halt( 'You are not allowed to view recipients.', 'no_access', 403 );
			// Exit here.
		}

		if( check_create_thread_limit( true ) )
		{	// User has already reached his limit, don't allow to get a users list:
			$this->halt_with_Messages();
		}

		$api_q = param( 'q', 'string', '' );

		// Search users:
		$users = $this->func_user_search( $api_q, array(
				'sql_where' => 'user_ID != '.$DB->quote( $current_User->ID ),
				'sql_mask'  => '%$login$%',
			) );

		foreach( $users as $User )
		{
			if( ! $User->check_status( 'can_receive_pm' ) )
			{	// This user is probably closed so don't show it:
				continue;
			}

			$user_data = array(
					'id'       => $User->ID,
					'login'    => $User->get( 'login' ),
					'fullname' => $User->get( 'fullname' ),
					'avatar'   => $User->get_avatar_imgtag( 'crop-top-32x32' ),
				);

			// Add data of each user in separate array of response:
			$this->add_response( 'users', $user_data, 'array' );
		}
	}


	/**
	 * Call user controller to search user for autocomplete JS plugin
	 */
	private function controller_user_autocomplete()
	{
		$api_q = param( 'q', 'string', '' );

		if( ! is_valid_login( $api_q ) )
		{	// Restrict a wrong request:
			$this->halt( 'Wrong request', 'wrong_request', 403 );
			// Exit here.
		}

		// Add backslash for special char of sql operator LIKE:
		$api_q = str_replace( '_', '\_', $api_q );

		// Search users:
		$users = $this->func_user_search( $api_q );

		foreach( $users as $User )
		{
			$user_data = array(
					'login'    => $User->get( 'login' ),
				);

			// Add data of each user in separate array of response:
			$this->add_response( 'users', $user_data, 'array' );
		}
	}


	/**
	 * Call user controller to search user for hintbox and typeahead JS plugins
	 */
	private function controller_user_logins()
	{
		global $current_User;

		$api_q = trim( urldecode( param( 'q', 'string', '' ) ) );
		$api_status = param( 'status', 'string', '' );

		/**
		 * sam2kb> The code below decodes percent-encoded unicode string produced by Javascript "escape"
		 * function in format %uxxxx where xxxx is a Unicode value represented as four hexadecimal digits.
		 * Example string "MAMA" (cyrillic letters) encoded with "escape": %u041C%u0410%u041C%u0410
		 * Same word encoded with "encodeURI": %D0%9C%D0%90%D0%9C%D0%90
		 *
		 * jQuery hintbox plugin uses "escape" function to encode URIs
		 *
		 * More info here: http://en.wikipedia.org/wiki/Percent-encoding#Non-standard_implementations
		 */
		if( preg_match( '~%u[0-9a-f]{3,4}~i', $api_q ) && version_compare(PHP_VERSION, '5', '>=') )
		{	// Decode UTF-8 string (PHP 5 and up)
			$api_q = preg_replace( '~%u([0-9a-f]{3,4})~i', '&#x\\1;', $api_q );
			$api_q = html_entity_decode( $api_q, ENT_COMPAT, 'UTF-8' );
		}

		if( empty( $api_q ) )
		{	// Don't allow empty request:
			$this->halt( 'Please enter at least one char to find assignees', 'no_access', 403 );
			// Exit here.
		}

		$func_user_search_params = array( 'sql_limit' => 10 );
		if( $api_status == 'all' )
		{	// Get users with all statuses:
			$func_user_search_params['sql_where'] = '';
		}
		elseif( ! empty( $api_status ) )
		{	// Restrict users with requested statuses:
			global $DB;
			$func_user_search_params['sql_where'] = 'user_status IN ( '.$DB->quote( explode( ',', $api_status ) ).' )';
		}

		// Search users:
		$users = $this->func_user_search( $api_q, $func_user_search_params );

		// Check if current user can see other users with ALL statuses:
		$can_view_all_users = ( is_logged_in() && $current_User->check_perm( 'users', 'view' ) );

		$user_logins = array();
		foreach( $users as $User )
		{
			if( $can_view_all_users || in_array( $User->get( 'status' ), array( 'activated', 'autoactivated' ) ) )
			{	// Allow to see this user only if current User has a permission to see users with current status:
				$user_logins[] = $User->get( 'login' );
			}
		}

		// Send users logins array as response:
		$this->add_response( 'list', $user_logins );
	}


	/**
	 * Function to search users by login
	 *
	 * @param string Search string
	 * @return array Users
	 */
	private function func_user_search( $search_string, $params = array() )
	{
		global $DB;

		$params = array_merge( array(
				'sql_where' => 'user_status IN ( "activated", "autoactivated" )',
				'sql_mask'  => '$login$%',
				'sql_limit' => 0,
			), $params );

		// Get request params:
		$api_page = param( 'page', 'integer', 1 );
		$api_per_page = param( 'per_page', 'integer', $params['sql_limit'] );

		// Initialize SQL to get users:
		$users_SQL = new SQL();
		$users_SQL->SELECT( '*' );
		$users_SQL->FROM( 'T_users' );
		if( ! empty( $search_string ) )
		{	// Filter by login:
			$users_SQL->WHERE( 'user_login LIKE '.$DB->quote( str_replace( '$login$', $search_string, $params['sql_mask'] ) ) );
		}
		if( ! empty( $params['sql_where'] ) )
		{	// Additional restrict:
			$users_SQL->WHERE_and( $params['sql_where'] );
		}
		$users_SQL->ORDER_BY( 'user_login' );

		// Get a count of users:
		$count_users = $DB->get_var( preg_replace( '/SELECT(.+)FROM/i', 'SELECT COUNT( user_ID ) FROM', $users_SQL->get() ) );

		// Set page params:
		$count_pages = empty( $api_per_page ) ? 1 : ceil( $count_users / $api_per_page );
		if( empty( $api_page ) )
		{	// Force wrong page number to first:
			$api_page = 1;
		}
		if( $api_page > $count_pages )
		{	// Don't allow page number more than total pages:
			$api_page = $count_pages;
		}
		if( $api_per_page > 0 )
		{	// Limit users by current page:
			$users_SQL->LIMIT( ( ( $api_page - 1 ) * $api_per_page ).', '.$api_per_page );
		}

		// Load users in cache by SQL:
		$UserCache = & get_UserCache();
		$UserCache->clear();
		$UserCache->load_by_sql( $users_SQL );

		if( empty( $UserCache->cache ) )
		{	// No users found:
			$this->halt( 'No users found', 'no_users', 200 );
			// Exit here.
		}

		$this->add_response( 'found', $count_users, 'integer' );
		$this->add_response( 'page', $api_page, 'integer' );
		$this->add_response( 'page_size', $api_per_page, 'integer' );
		$this->add_response( 'pages_total', $count_pages, 'integer' );

		return $UserCache->cache;
	}


	/**** MODULE USERS ---- END ****/


	/**** MODULE TAGS ---- START ****/


	/**
	 * Call module to prepare request for tags
	 */
	private function module_tags()
	{
		global $DB;

		$term = param( 's', 'string' );

		if( substr( $term, 0, 1 ) == '-' )
		{	// Prevent chars '-' in first position:
			$term = preg_replace( '/^-+/', '', $term );
		}

		// Deny to use a comma in tag names:
		$term = str_replace( ',', ' ', $term );

		$term_is_new_tag = true;

		$tags = array();

		$tags_SQL = new SQL();
		$tags_SQL->SELECT( 'tag_name AS id, tag_name AS name' );
		$tags_SQL->FROM( 'T_items__tag' );
		/* Yura: Here I added "COLLATE utf8_general_ci" because:
		 * It allows to match "testA" with "testa", and otherwise "testa" with "testA".
		 * It also allows to find "ee" when we type in "éè" and otherwise.
		 */
		$tags_SQL->WHERE( 'tag_name LIKE '.$DB->quote( '%'.$term.'%' ).' COLLATE utf8_general_ci' );
		$tags_SQL->ORDER_BY( 'tag_name' );
		$tags = $DB->get_results( $tags_SQL->get(), ARRAY_A );

		// Check if current term is not an existing tag:
		foreach( $tags as $tag )
		{
			/* Yura: I have added "utf8_strtolower()" below in condition in order to:
			 * When we enter new tag 'testA' and the tag 'testa' already exists
			 * then we suggest only 'testa' instead of 'testA'.
			 */
			if( utf8_strtolower( $tag['name'] ) == utf8_strtolower( $term ) )
			{ // Current term is an existing tag
				$term_is_new_tag = false;
			}
		}

		if( $term_is_new_tag && ! empty( $term ) )
		{	// Add current term in the beginning of the tags list:
			array_unshift( $tags, array( 'id' => $term, 'name' => $term ) );
		}

		$this->add_response( 'tags', $tags );
	}


	/**** MODULE TAGS ---- END ****/

	/**** MODULE USER TAGS ---- START ****/


	/**
	 * Call module to prepare request for user tags
	 */
	private function module_usertags()
	{
		global $DB;

		$term = param( 's', 'string' );

		if( substr( $term, 0, 1 ) == '-' )
		{	// Prevent chars '-' in first position:
			$term = preg_replace( '/^-+/', '', $term );
		}

		// Deny to use a comma in tag names:
		$term = str_replace( ',', ' ', $term );

		$term_is_new_tag = true;

		$tags = array();

		$tags_SQL = new SQL();
		$tags_SQL->SELECT( 'utag_name AS id, utag_name AS name' );
		$tags_SQL->FROM( 'T_users__tag' );
		/* Yura: Here I added "COLLATE utf8_general_ci" because:
		 * It allows to match "testA" with "testa", and otherwise "testa" with "testA".
		 * It also allows to find "ee" when we type in "éè" and otherwise.
		 */
		$tags_SQL->WHERE( 'utag_name LIKE '.$DB->quote( '%'.$term.'%' ).' COLLATE utf8_general_ci' );
		$tags_SQL->ORDER_BY( 'utag_name' );
		$tags = $DB->get_results( $tags_SQL->get(), ARRAY_A );

		// Check if current term is not an existing tag:
		foreach( $tags as $tag )
		{
			/* Yura: I have added "utf8_strtolower()" below in condition in order to:
			 * When we enter new tag 'testA' and the tag 'testa' already exists
			 * then we suggest only 'testa' instead of 'testA'.
			 */
			if( utf8_strtolower( $tag['name'] ) == utf8_strtolower( $term ) )
			{ // Current term is an existing tag
				$term_is_new_tag = false;
			}
		}

		if( $term_is_new_tag && ! empty( $term ) )
		{	// Add current term in the beginning of the tags list:
			array_unshift( $tags, array( 'id' => $term, 'name' => $term ) );
		}

		$this->add_response( 'tags', $tags );
	}


	/**** MODULE USER TAGS ---- END ****/

	/**** MODULE POLLS ---- START ****/

	private function module_polls()
	{
		global $DB, $current_User;

		if( is_logged_in() && $current_User )
		{
			$polls = array();

			$perm_poll_view = $current_User->check_perm( 'polls', 'view' );

			$polls_SQL = new SQL();
			$polls_SQL->SELECT( 'pqst_ID, pqst_owner_user_ID, pqst_question_text' );
			$polls_SQL->FROM( 'T_polls__question' );
			if( ! $perm_poll_view )
			{
				$polls_SQL->WHERE( 'pqst_owner_user_ID = '.$DB->quote( $current_User->ID ) );
			}

			$poll_count_SQL = new SQL();
			$poll_count_SQL->SELECT( 'COUNT( pqst_ID )' );
			$poll_count_SQL->FROM( 'T_polls__question' );
			if( ! $perm_poll_view )
			{
				$poll_count_SQL->WHERE( 'pqst_owner_user_ID = '.$DB->quote( $current_User->ID ) );
			}

			$polls = $DB->get_results( $polls_SQL->get(), ARRAY_A );

			$this->add_response( 'polls', $polls );
		}
		else
		{
			$this->halt( 'You are not allowed to view polls.', 'no_access', 403 );
		}
	}

	/**** MODULE POLLS ---- END ****/


	/**** MODULE LINKS ---- START ****/


	/**
	 * Call module to prepare request for links
	 */
	private function module_links()
	{
		if( ! empty( $this->args[2] ) )
		{	// Get action from request string:
			$link_action = $this->args[2];
		}
		else
		{	// Get action from param:
			$link_action = param( 'action', 'string', '' );
		}

		// Set link controller '' by default:
		$link_controller = '';

		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET';
		switch( $request_method )
		{
			case 'DELETE':
				// Set controller to unlink/delete the requested link:
				$link_controller = 'delete';
				break;

			case 'GET':
			case 'POST':
				// Actions to update the links:
				switch( $link_action )
				{
					case 'move_up':
					case 'move_down':
						$link_controller = 'change_order';
						break;

					case 'attach':
						$link_controller = 'attach';
						break;

					case 'refresh':
					case 'sort':
						$link_controller = 'refresh';
						break;

					case 'copy':
						$link_controller = 'copy';
						break;
				}
				break;
		}

		if( ! method_exists( $this, 'controller_link_'.$link_controller ) )
		{	// Unknown controller:
			$this->halt( 'Unknown link controller "'.$link_controller.'"', 'unknown_controller' );
			// Exit here.
		}

		// Call collection controller to prepare current request:
		$this->{'controller_link_'.$link_controller}();
	}


	/**
	 * Get Link object
	 *
	 * @return object Link
	 */
	private function & get_Link()
	{
		// Get link ID:
		$link_ID = empty( $this->args[1] ) ? 0 : intval( $this->args[1] );

		$LinkCache = & get_LinkCache();
		$Link = & $LinkCache->get_by_ID( $link_ID, false, false );

		return $Link;
	}


	/**
	 * Check permission if current user can update the requested link
	 */
	private function link_check_perm()
	{
		if( ! ( $Link = & $this->get_Link() ) )
		{	// Wrong link request:
			$this->halt( 'Invalid link ID', 'link_invalid_id' );
			// Exit here.
		}

		$LinkOwner = & $Link->get_LinkOwner();

		if( ! is_logged_in() || ! $LinkOwner->check_perm( 'edit', false ) )
		{	// Current user has no permission to unlink the requested link:
			$this->halt( 'You have no permission to edit the requested link!', 'no_access', 403 );
			// Exit here.
		}
	}


	/**
	 * Call link controller to unlink the requested link or delete file completely
	 */
	private function controller_link_delete()
	{
		// Check permission if current user can update the requested link:
		$this->link_check_perm();

		// Action: 'unlink - just unlink file from the owner, 'delete' - unlink and delete the file from disk and DB completely

		// Note: param() currently does not work with DELETE and PUT requests
		parse_str( file_get_contents('php://input'), $request_params );
		$action = isset( $request_params['action'] ) ? $request_params['action'] : '';

		$deleted_Link = & $this->get_Link();
		$LinkOwner = & $deleted_Link->get_LinkOwner();

		if( $link_File = & $deleted_Link->get_File() )
		{
			syslog_insert( sprintf( 'File %s was unlinked from %s with ID=%s', '[['.$link_File->get_name().']]', $LinkOwner->type, $LinkOwner->get_ID() ), 'info', 'file', $link_File->ID );
		}

		if( $action == 'delete' && $deleted_Link->can_be_file_deleted() )
		{	// Get a linked file to delete it after unlinking if it is allowed for current user:
			$linked_File = & $deleted_Link->get_File();
		}

		// Unlink File from Item/Comment:
		$deleted_link_ID = $deleted_Link->ID;
		$deleted_Link->dbdelete();

		$LinkOwner->after_unlink_action( $deleted_link_ID );

		if( $action == 'delete' && ! empty( $linked_File ) )
		{	// Delete a linked file from disk and DB completely:
			$linked_File->unlink();
		}

		// The requested link has been deleted successfully:
		$this->halt( $LinkOwner->translate( 'Link has been deleted from $xxx$.' ), 'delete_success', 200 );
		// Exit here.
	}


	/**
	 * Call link controller to change order the requested link
	 */
	private function controller_link_change_order()
	{
		// Check permission if current user can update the requested link:
		$this->link_check_perm();

		// Get action from request string:
		$link_action = $this->args[2];

		$edited_Link = & $this->get_Link();
		$LinkOwner = & $edited_Link->get_LinkOwner();

		$ownerLinks = $LinkOwner->get_Links();

		// TODO fp> when moving an "after_more" above a "teaser" img, it should change to "teaser" too.
		// TODO fp> when moving a "teaser" below an "aftermore" img, it should change to "aftermore" too.

		// Switch order with the next/prev one:
		if( $link_action == 'move_up' )
		{
			$switchcond = 'return ($loop_Link->get("order") > $i
				&& $loop_Link->get("order") < '.$edited_Link->get( 'order' ).');';
			$i = -1;
		}
		else
		{
			$switchcond = 'return ($loop_Link->get("order") < $i
				&& $loop_Link->get("order") > '.$edited_Link->get( 'order' ).');';
			$i = PHP_INT_MAX;
		}
		foreach( $ownerLinks as $loop_Link )
		{	// Find nearest order:
			if( $loop_Link == $edited_Link )
				continue;

			if( eval( $switchcond ) )
			{
				$i = $loop_Link->get( 'order' );
				$switch_Link = $loop_Link;
			}
		}
		if( $i > -1 && $i < PHP_INT_MAX )
		{	// Switch the links:
			$switch_Link->set( 'order', $edited_Link->get( 'order' ) );

			// HACK: go through order=0 to avoid duplicate key conflict:
			$edited_Link->set( 'order', 0 );
			$edited_Link->dbupdate();
			$switch_Link->dbupdate();

			$edited_Link->set( 'order', $i );
			$edited_Link->dbupdate();

			// Update last touched date of Owners
			$LinkOwner->update_last_touched_date();

			// The requested link order has been changed successfully:
			$this->halt( ( $link_action == 'move_up' )
				? T_('Link has been moved up.')
				: T_('Link has been moved down.'),
				'change_order_success', 200 );
			// Exit here.
		}
		else
		{	// The requested link order has been changed successfully:
			$this->halt( T_('Link order has not been changed.'), 'change_order_success', 403 );
			// Exit here.
		}
	}


	/**
	 * Call link controller to change order the requested link
	 */
	private function controller_link_attach()
	{
		global $LinkOwner, $current_File;

		$link_type = param( 'type', 'string' );
		$link_object_ID = param( 'object_ID', 'string' );
		$root = param( 'root', 'string' );
		$file_path = param( 'path', 'string' );

		$LinkOwner = get_link_owner( $link_type, $link_object_ID );

		if( ! is_logged_in() || ! $LinkOwner->check_perm( 'edit', false ) )
		{	// Current user has no permission to unlink the requested link:
			$this->halt( 'You have no permission to attach a file!', 'no_access', 403 );
			// Exit here.
		}

		$FileCache = & get_FileCache();
		list( $root_type, $root_in_type_ID ) = explode( '_', $root, 2 );
		if( ! ( $current_File = $FileCache->get_by_root_and_path( $root_type, $root_in_type_ID, $file_path ) ) )
		{	// No file:
			$this->halt( T_('Nothing selected.'), 'wrong_file', 403 );
			// Exit here.
		}

		// Link the file to Item/Comment:
		$link_ID = $current_File->link_to_Object( $LinkOwner );

		$LinkCache = & get_LinkCache();
		if( $Link = & $LinkCache->get_by_ID( $link_ID, false, false ) )
		{	// Add data of new link to response:

			// Use the glyph or font-awesome icons if requested by skin
			param( 'b2evo_icons_type', 'string', 'fontawesome-glyphicons' );

			global $LinkOwner, $current_File, $disable_evo_flush;

			$link_type = param( 'type', 'string' );
			$link_object_ID = param( 'object_ID', 'string' );

			$LinkOwner = get_link_owner( $link_type, $link_object_ID );

			// Initialize admin skin:
			global $current_User, $UserSettings, $is_admin_page, $adminskins_path, $AdminUI;
			$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
			$is_admin_page = true;
			require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
			$AdminUI = new AdminUI();

			// Disable function evo_flush() to correct handle a content below:
			$disable_evo_flush = true;

			// Get the refreshed content:
			ob_start();
			$AdminUI->disp_view( 'links/views/_link_list.view.php' );
			$refreshed_content = ob_get_clean();

			$mask_row = (object) array(
					'link_ID'       => $Link->ID,
					'file_ID'       => $current_File->ID,
					'file_type'     => $current_File->get_file_type(),
					'link_position' => $Link->get( 'position' ),
				);
			$this->add_response( 'link', array(
					'ID'       => $Link->ID,
					'url'      => $current_File->get_view_link(),
					'preview'  => $Link->get_preview_thumb(),
					'actions'  => link_actions( $Link->ID, 'last', $link_type ),
					'position' => display_link_position( $mask_row ),
				) );
			$this->add_response( 'list_content', $refreshed_content );
		}

		// File has been attached successfully:
		$this->halt( $LinkOwner->translate( 'Selected files have been linked to xxx.' ), 'attach_success', 200 );
		// Exit here.
	}


	/**
	 * Call link controller to refresh a list of the links
	 */
	private function controller_link_refresh()
	{
		global $LinkOwner, $current_File, $disable_evo_flush;

		$link_type = param( 'type', 'string' );
		$link_object_ID = param( 'object_ID', 'string' );

		$LinkOwner = get_link_owner( $link_type, $link_object_ID );

		if( ! is_logged_in() || ! $LinkOwner->check_perm( 'edit', false ) )
		{	// Current user has no permission to unlink the requested link:
			$this->halt( 'You have no permission to list of the links!', 'no_access', 403 );
			// Exit here.
		}

		if( get_param( 'action' ) == 'sort' )
		{	// Sort the links:
			$ownerLinks = $LinkOwner->get_Links();
			usort( $ownerLinks, 'sort_links_by_filename' );

			$max_order = 0;
			$link_orders = array();
			$link_count = count( $ownerLinks );
			foreach( $ownerLinks as $link )
			{
				if( $link->order > $max_order )
				{
					$max_order = $link->order;
				}
				$link_orders[] = $link->order;
			}

			for( $i = 1; $i <= $link_count; $i++ )
			{
					$ownerLinks[$i - 1]->set( 'order', $i + $max_order );
					$ownerLinks[$i - 1]->dbupdate();
			}

			for( $i = 1; $i <= $link_count; $i++ )
			{
				if( $ownerLinks[$i -1]->get( 'order' ) != $i )
				{
					$ownerLinks[$i - 1]->set( 'order', $i );
					$ownerLinks[$i - 1]->dbupdate();
				}
			}
		}

		// Initialize admin skin:
		global $current_User, $UserSettings, $is_admin_page, $adminskins_path, $AdminUI;
		$admin_skin = $UserSettings->get( 'admin_skin', $current_User->ID );
		$is_admin_page = true;
		require_once $adminskins_path.$admin_skin.'/_adminUI.class.php';
		$AdminUI = new AdminUI();

		// Disable function evo_flush() to correct handle a content below:
		$disable_evo_flush = true;

		// Get the refreshed content:
		ob_start();
		$AdminUI->disp_view( 'links/views/_link_list.view.php' );
		$refreshed_content = ob_get_clean();

		$this->add_response( 'html', $refreshed_content );

		if( get_param( 'action' ) == 'sort' )
		{	// The sort has been done successfully:
			$this->halt( T_('The attachments have been sorted by file name.'), 'sort_success', 200 );
			// Exit here.
		}
		else
		{	// The refresh has been done successfully:
			$this->halt( 'A list of the links has been refreshed.', 'refresh_success', 200 );
			// Exit here.
		}
	}


	/**
	 * Call link controller to copy Link from one object to another
	 */
	private function controller_link_copy()
	{
		$dest_type = param( 'dest_type', 'string' );
		$dest_object_ID = param( 'dest_object_ID', 'string' );

		$dest_LinkOwner = get_link_owner( $dest_type, $dest_object_ID );

		if( ! is_logged_in() || ! $dest_LinkOwner->check_perm( 'edit', false ) )
		{	// Current user has no permission to copy the requested link:
			$this->halt( 'You have no permission to list of the links!', 'no_access', 403 );
			// Exit here.
		}

		$source_type = param( 'source_type', 'string' );
		$source_object_ID = param( 'source_object_ID', 'string' );
		$source_position = trim( param( 'source_position', 'string' ), ',' );
		$source_file_type = param( 'source_file_type', 'string', NULL );

		$source_LinkOwner = get_link_owner( $source_type, $source_object_ID );

		$link_list_params = array(
				// Sort the attachments to get firstly "Cover", then "Teaser", and "After more" as last order
				'sql_select_add' => ', CASE '
						.'WHEN link_position = "cover"     THEN "1" '
						.'WHEN link_position = "teaser"    THEN "2" '
						.'WHEN link_position = "aftermore" THEN "3" '
						.'WHEN link_position = "inline"    THEN "4" '
						.'ELSE "99999999"' // Use this line only if you want to put the other position types at the end
					.'END AS position_order',
				'sql_order_by' => 'position_order, link_order',
			);

		if( ! $source_LinkOwner || ! ( $source_LinkList = $source_LinkOwner->get_attachment_LinkList( 1000, $source_position, $source_file_type, $link_list_params ) ) )
		{	// No requested links, Exit here:
			$this->response = array();
			return;
		}

		$dest_position = param( 'dest_position', 'string' );

		$dest_last_order = $dest_LinkOwner->get_last_order() + 1;

		// Limit files per each position, 0 - for unlimit:
		$limit_position = param( 'limit_position', 'integer', 0 );
		if( $limit_position )
		{
			$position_counts = array();
			$all_position_counts = 0;
		}

		// Find all attached files of the destination LinkOwner in order to don't link twice same files:
		$dest_files_links = array();
		$dest_links = $dest_LinkOwner->get_Links();
		if( ! empty( $dest_links ) && is_array( $dest_links ) )
		{
			foreach( $dest_links as $dest_Link )
			{
				if( $dest_File = & $dest_Link->get_File() )
				{
					$dest_files_links[ $dest_File->ID ] = $dest_Link->ID;
				}
			}
		}

		while( $source_Link = & $source_LinkList->get_next() )
		{	// Copy a Link to new object:
			if( $limit_position )
			{
				if( $source_position == '' && $all_position_counts >= $limit_position )
				{	// Stop copy other positions because we are finding any position:
					break;
				}
				if( ! isset( $position_counts[ $source_Link->position ] ) )
				{
					$position_counts[ $source_Link->position ] = 0;
				}
				if( $position_counts[ $source_Link->position ] >= $limit_position )
				{	// Skip this because of limit per position:
					if( $source_position == $source_Link->position )
					{	// Stop copy other positions because we are finding only current:
						break;
					}
					else
					{	// Continue to find other positions:
						continue;
					}
				}
				$position_counts[ $source_Link->position ]++;
				$all_position_counts++;
			}

			if( $source_File = & $source_Link->get_File() )
			{
				if( isset( $dest_files_links[ $source_File->ID ] ) )
				{	// Don't attach the File twice if it is already linked to the destination LinkOwner:
					$new_link_ID = $dest_files_links[ $source_File->ID ];
				}
				else
				{	// Attach new file because it is not linked to the destination LinkOwner yet:
					$new_link_ID = $dest_LinkOwner->add_link( $source_Link->file_ID, ( empty( $dest_position ) ? $source_Link->position : $dest_position ), $dest_last_order++ );
				}
				$this->add_response( 'links', array(
						'ID'            => $new_link_ID,
						'file_type'     => $source_File->get_file_type(),
						'orig_position' => $source_Link->position,
					), 'array' );
			}
		}
	}


	/**** MODULE LINKS ---- END ****/

	/**** MODULE TOOLS ---- START ****/

	/**
	 * Call module to prepare request for tools
	 */
	private function module_tools()
	{
		if( empty( $this->args[1] ) )
		{
			$this->halt( 'Missing tool controller', 'wrong_request', 405 );
		}
		else
		{
			$tool_controller = $this->args[1];

			if( ! method_exists( $this, 'controller_tool_'.$tool_controller ) )
			{	// Unknown controller:
				$this->halt( 'Unknown link controller "'.$tool_controller.'"', 'unknown_controller' );
				// Exit here.
			}

			// Call collection controller to prepare current request:
			$this->{'controller_tool_'.$tool_controller}();
		}
	}


	/**
	 * Call tool controller to get available urlname
	 */
	private function controller_tool_available_urlname()
	{
		$urlname = param( 'urlname', 'string' );

		$this->add_response( 'base', $urlname );
		$this->add_response( 'urlname', urltitle_validate( $urlname, '', 0, false, 'blog_urlname', 'blog_ID', 'T_blogs' ) );
	}

	/**** MODULE TOOLS ---- END ****/
}