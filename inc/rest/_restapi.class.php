<?php
/**
 * This file implements REST API class
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
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
		$this->args = explode( '/', $request );

		if( isset( $this->args[0] ) )
		{	// Initialize module name:
			$this->module = $this->args[0];
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
			$this->halt( 'Unknown module', 'unknown_module' );
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
	 */
	private function add_response( $key, $value = NULL )
	{
		if( $value === NULL )
		{	// Use auto key:
			$this->response[] = $key;
		}
		else
		{	// Use defined key:
			$this->response[ $key ] = $value;
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
				$this->halt( 'No collection found in DB by requested url name', 'unknown_collection' );
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

		if( ! method_exists( $this, 'controller_coll_'.$coll_controller ) )
		{	// Unknown controller:
			$this->halt( 'Unknown collection controller', 'unknown_controller' );
			// Exit here.
		}

		// Call collection controller to prepare current request:
		$this->{'controller_coll_'.$coll_controller}();
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
			$this->halt( 'No collections found', 'no_collections', 200 );
			// Exit here.
		}

		foreach( $BlogCache->cache as $Blog )
		{ // Add each collection row in the response array:
			$this->add_response( array(
					'id'        => $Blog->ID,
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
	 * Call collection controller to prepare request for posts
	 */
	private function controller_coll_posts()
	{
		global $Blog;

		// Get param to limit number posts per page:
		$api_per_page = param( 'per_page', 'integer', 10 );

		// Try to get a post ID for request "<baseurl>/api/v1/collections/<collname>/posts/<id>":
		$post_ID = empty( $this->args[3] ) ? 0 : $this->args[3];

		$ItemList2 = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), $api_per_page, 'ItemCache', 'api_' );

		if( $post_ID )
		{	// Get only one requested post:
			$ItemList2->set_filters( array( 'post_ID' => $post_ID ) );
		}
		else
		{	// Load all available params from request to filter the posts list:
			$ItemList2->load_from_Request( false );
		}

		// Run the items list query:
		$ItemList2->query();

		// Add each post row in the response array:
		while( $Item = & $ItemList2->get_next() )
		{
			$item_data = array(
					'id'        => $Item->ID,
					'datestart' => $Item->get( 'datestart' ),
					'urltitle'  => $Item->get( 'urltitle' ),
					'type'      => $Item->get_type_setting( 'name' ),
					'title'     => $Item->get( 'title' ),
					'content'   => $Item->get_prerendered_content( 'htmlbody' ),
					'excerpt'   => $Item->get( 'excerpt' ),
				);
			if( $post_ID )
			{	// If only one post is requested then response should as one level array with post fields:
				$this->response = $item_data;
			}
			else
			{	// Add data of each post in separate array in response:
				$this->add_response( $item_data );
			}
		}

		if( empty( $this->response ) )
		{	// No posts detected:
			if( $post_ID )
			{	// Wrong post request:
				$this->halt( 'Invalid post ID', 'post_invalid_id' );
				// Exit here.
			}
			else
			{	// No posts found:
				$this->halt( 'No posts found for requested collection', 'no_posts', 200 );
				// Exit here.
			}
		}
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
			$search_result = perform_scored_search( $search_keywords );

			// Save results into session:
			$Session->set( 'search_params', $search_params );
			$Session->set( 'search_result', $search_result );
			$search_result_loaded = true;
		}

		$search_result = $Session->get( 'search_result' );
		if( empty( $search_result ) )
		{	// Nothing found:
			$this->halt( T_('Sorry, we could not find anything matching your request, please try to broaden your search.'), 'no_search_results', 200 );
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

		// Get results for current page:
		for( $index = $from; $index < $to; $index++ )
		{
			$row = $search_result[ $index ];

			$result_data = array(
					'kind' => $row['type'],
					'id'   => $row['ID'],
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

					list( $tag_name, $post_count ) = explode( ':', $row['ID'] );

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
			$this->add_response( $result_data );
		}
	}
}