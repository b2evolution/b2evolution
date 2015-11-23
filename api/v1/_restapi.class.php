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
		if( ! isset( $this->args[1] ) )
		{	// Wrong request because collection name is not defined:
			$this->halt( 'Collection name is not defined' );
			// Exit here.
		}

		// Collection urlname:
		$coll_urlname = $this->args[1];

		$BlogCache = & get_BlogCache();
		if( ( $Blog = & $BlogCache->get_by_urlname( $coll_urlname, false ) ) === false )
		{	// Collection is not detected in DB by requested url name:
			$this->halt( 'No collection found in DB by requested url name', 'unknown_collection' );
			// Exit here.
		}

		if( ! isset( $this->args[2] ) )
		{	// Wrong request because collection controller is not defined:
			$this->halt( 'Collection controller is not defined' );
			// Exit here.
		}

		// Collection controller:
		$coll_controller = $this->args[2];

		if( ! method_exists( $this, 'controller_coll_'.$coll_controller ) )
		{	// Unknown controller:
			$this->halt( 'Unknown collection controller', 'unknown_controller' );
			// Exit here.
		}

		// Call collection controller to prepare current request:
		$this->{'controller_coll_'.$coll_controller}( $Blog );
	}


	/**
	 * Call collection controller to prepare request for posts
	 *
	 * @param object Collection object
	 */
	private function controller_coll_posts( & $Blog )
	{
		// Get additional params:
		$page = param( 'page', 'integer', 1 );
		$per_page = param( 'per_page', 'integer', 10 );

		// Try to get a post ID:
		$post_ID = empty( $this->args[3] ) ? 0 : $this->args[3];

		$ItemList2 = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), $per_page, 'ItemCache', 'api_' );

		$items_list_filter = array(
				'types' => NULL, // Get all item types
				'page'  => $page, // Page number
			);

		if( $post_ID )
		{	// Get only one requested post:
			$items_list_filter['post_ID'] = $post_ID;
		}

		// Filter items list:
		$ItemList2->set_filters( $items_list_filter );

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
					'content'   => $Item->get( 'content' ),
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
			{	// Wrong post request:
				$this->halt( 'No posts found for requested collection', 'no_posts', 200 );
				// Exit here.
			}
		}
	}
}