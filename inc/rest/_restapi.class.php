<?php
/**
 * This file implements REST API class
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
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

		if( $User->pass != md5( $User->salt.$entered_password, true ) )
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
			$this->halt( T_('You cannot log in at this time because the system is under maintenance. Please try again in a few moments.'), 'system_maintenance', '403' );
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
		global $Blog;

		// Collection controller ('list' by default):
		$coll_controller = isset( $this->args[2] ) ? $this->args[2] : 'list';

		if( $coll_controller != 'list' )
		{	// Initialize data for request of the selected collection:

			if( ! isset( $this->args[1] ) )
			{	// Wrong request because collection name is not defined:
				$this->halt( 'Collection name is not defined' );
				// Exit here.
			}

			// Collection urlname:
			$coll_urlname = $this->args[1];

			$BlogCache = & get_BlogCache();
			if( ( $Blog = $BlogCache->get_by_urlname( $coll_urlname, false ) ) === false )
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

		// Call collection controller to prepare current request:
		call_user_func_array( array( $this, 'controller_coll_'.$coll_controller ), $coll_controller_params );
	}


	/**
	 * Call collection controller to list the public collections
	 */
	private function controller_coll_list()
	{
		// Load only public collections for current user:
		$BlogCache = & get_BlogCache();
		$BlogCache->clear();
		$BlogCache->load_public();

		if( empty( $BlogCache->cache ) )
		{	// No collections found:
			$this->halt( 'No collections found', 'no_collections', 404 );
			// Exit here.
		}

		foreach( $BlogCache->cache as $Blog )
		{ // Add each collection row in the response array:
			$this->add_response( array(
					'id'        => intval( $Blog->ID ),
					'urlname'   => $Blog->get( 'urlname' ),
					'kind'      => $Blog->get( 'type' ),
					'shortname' => $Blog->get( 'shortname' ),
					'name'      => $Blog->get( 'name' ),
					'tagline'   => $Blog->get( 'tagline' ),
					'desc'      => $Blog->get( 'longdesc' ),
				) );
		}
	}


	/**
	 * Call collection controller to prepare request for items with ANY types
	 *
	 * @param array Force filters of request
	 */
	private function controller_coll_items( $force_filters = array() )
	{
		global $Blog;

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
				'itemtype_usage' => NULL, // Keep content post types, Exclude pages, intros, sidebar links and ads
			) );
	}


	/**
	 * Call collection controller to search the chapters, posts, comments and tags
	 */
	private function controller_coll_search()
	{
		global $Blog, $Session;

		// Get additional params:
		$api_page = param( 'page', 'integer', 1 );
		$api_per_page = param( 'per_page', 'integer', 10 );
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
			|| ( $search_result === NULL ) )
		{	// We need to perform a new search:
			$search_params = array(
				'search_keywords' => $search_keywords, 
				'search_blog'     => $Blog->ID,
			);

			// Perform new search:
			$search_result = perform_scored_search( $search_keywords, $api_kind );

			// Save results into session:
			$Session->set( 'search_params', $search_params );
			$Session->set( 'search_result', $search_result );
			$search_result_loaded = true;
		}

		$search_result = $Session->get( 'search_result' );
		if( empty( $search_result ) )
		{	// Nothing found:
			$this->halt( T_('Sorry, we could not find anything matching your request, please try to broaden your search.'), 'no_search_results', 404 );
			// Exit here.
		}

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
					$result_data['desc'] = $Item->get_excerpt2();
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


	/**
	 * Call item controller to flag by current user
	 *
	 * @param integer Item ID
	 */
	private function controller_coll_items_flag( $item_ID )
	{
		global $Blog;

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
		// Set user controller 'list' by default:
		$user_controller = 'list';

		// Get user ID:
		$user_ID = empty( $this->args[1] ) ? 0 : intval( $this->args[1] );

		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET';
		switch( $request_method )
		{
			case 'POST':
				// Set controller to update the requested user OR create one new:
				$user_controller = 'save';
				break;

			case 'DELETE':
				// Set controller to delete the requested user:
				if( $user_ID > 0 )
				{	// Only if user ID is defined:
					$user_controller = 'delete';
				}
				else
				{	// Wrong user request:
					$this->halt( 'Invalid user ID', 'user_invalid_id' );
					// Exit here.
				}
				break;

			case 'GET':
			default:
				// Set controller to view the requested user profile or ALL users:
				$user_controller = ( $user_ID > 0 ) ? 'view' : 'list';
				break;
		}

		if( ! method_exists( $this, 'controller_user_'.$user_controller ) )
		{	// Unknown controller:
			$this->halt( 'Unknown user controller "'.$user_controller.'"', 'unknown_controller' );
			// Exit here.
		}

		// Call collection controller to prepare current request:
		$this->{'controller_user_'.$user_controller}();
	}


	/**
	 * Call user controller to list the users
	 */
	private function controller_user_list()
	{
		global $Settings;

		if( ( $access_error_message = check_access_users_list( 'api' ) ) !== true )
		{	// Current user has no access to public list of the users,
			// Display error message:
			$this->halt( $access_error_message, 'no_access', 403 );
			// Exit here.
		}

		// Get param to limit number users per page:
		$api_per_page = param( 'per_page', 'integer', 10 );

		// Create result set:
		load_class( 'users/model/_userlist.class.php', 'UserList' );
		$UserList = new UserList( 'api_', $api_per_page, '' );

		$UserList->load_from_Request();

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
			$this->halt( 'Invalid user ID', 'user_invalid_id' );
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
			$this->halt( $Messages->messages_text[0], 'update_failed', 403 );
			// Exit here.
		}
		else
		{	// The requested user has been updated successfully
			$this->halt( $Messages->messages_text[0], 'update_success', 200 );
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
		{	// Current user has no permission to delate the requested user:
			$this->halt( T_('You have no permission to edit other users!'), 'no_access', 403 );
			// Exit here.
		}

		// Get an user ID for request "DELETE <baseurl>/api/v1/users/<id>":
		$user_ID = empty( $this->args[1] ) ? 0 : intval( $this->args[1] );

		$UserCache = & get_UserCache();
		$User = & $UserCache->get_by_ID( $user_ID, false, false );

		if( ! $User )
		{	// Wrong user request:
			$this->halt( 'Invalid user ID', 'user_invalid_id' );
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


	/**** MODULE USERS ---- END ****/
}