<?php
/**
 * This file implements the Comment List Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
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
class coll_comment_list_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_comment_list_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_comment_list' );
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
			'title' => array(
				'label' => T_('Block title'),
				'note' => T_( 'Title to display in your skin.' ),
				'size' => 40,
				'defaultvalue' => T_('Recent Comments'),
			),
			'disp_order' => array(
				'label' => T_('Order'),
				'note' => T_('Order to display items'),
				'type' => 'select',
				'options' => array( 'DESC' => T_( 'Newest to oldest' ), 'ASC' => T_( 'Oldest to newest' ), 'RAND' => T_( 'Random selection' ) ),
				'defaultvalue' => 'DESC',
			),
			'limit' => array(
				'label' => T_( 'Max items' ),
				'note' => T_( 'Maximum number of items to display.' ),
				'size' => 4,
				'defaultvalue' => 20,
			),
			'author_links' => array(
				'label' => T_( 'Link to author'),
				'note' => T_( 'Link the author to their url' ),
				'defaultvalue' => true,
				'type' => 'checkbox',
			),
			'hover_text' => array(
				'label' => T_( 'Hover text'),
				'note' => T_( 'Text to show when hovering over the link' ),
				'size' => 40,
				'defaultvalue' => T_( 'Read the full comment' ),
				'type' => 'text',
			),
			'blog_ID' => array(
				'label' => T_( 'Collection' ),
				'note' => T_( 'ID of the collection to use, leave empty for the current collection.' ),
				'size' => 4,
				'type' => 'integer',
				'allow_empty' => true,
			),
		), parent::get_param_definitions( $params )	);

		return $r;
	}


	/**
	 * Get help URL
	 *
	 * @return string URL
	 */
	function get_help_url()
	{
		return get_manual_url( 'comment-list-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Comment list');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output($this->disp_params['title']);
	}


  /**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('List of comments; click goes to comment.');
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Blog;

		$this->init_display( $params );

		$blog_ID = intval( $this->disp_params['blog_ID'] );

		$blogCache = & get_BlogCache();
		$listBlog = ( $blog_ID ? $blogCache->get_by_ID( $blog_ID ) : $Blog );

		// Create ItemList
		// Note: we pass a widget specific prefix in order to make sure to never interfere with the mainlist
		$limit = intval( $this->disp_params['limit'] );
		$order = $this->disp_params['disp_order'];

		$CommentList = new CommentList2( $listBlog, $limit, 'CommentCache', $this->code.'_' );

		$filters = array(
				'types' => array( 'comment','trackback','pingback' ),
				'statuses' => array( 'published' ),
				'order' => $order,
				'comments' => $limit,
			);

		if( isset( $this->disp_params['page'] ) )
		{
			$filters['page'] = $this->disp_params['page'];
		}

		// Filter list:
		$CommentList->set_filters( $filters );

		// Get ready for display (runs the query):
		$CommentList->display_init();

		echo $this->disp_params[ 'block_start'];

		// Display title if requested
		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		echo $this->disp_params[ 'list_start' ];

		if( empty( $this->disp_params[ 'author_link_text' ] ) )
		{
			$this->disp_params[ 'author_link_text' ] = 'login';
		}

		/**
		 * @var Comment
		 */
		while( $Comment = & $CommentList->get_next() )
		{ // Loop through comments:
			// Load comment's Item object:
			$Comment->get_Item();
			echo $this->disp_params[ 'item_start' ];
			$Comment->author( '', ' ', '', ' ', 'htmlbody', $this->disp_params[ 'author_links' ], $this->disp_params[ 'author_link_text' ] );
			echo T_( 'on ' );
			$Comment->permanent_link( array(
				'text'        => $Comment->Item->title,
				'title'       => $this->disp_params[ 'hover_text' ],
				) );
			echo $this->disp_params[ 'item_end' ];
		}	// End of comment loop.}

		if( isset( $this->disp_params['page'] ) )
		{
			if( empty( $this->disp_params['pagination'] ) )
			{
				$this->disp_params['pagination'] = array();
			}
			$CommentList->page_links( $this->disp_params['pagination'] );
		}

		echo $this->disp_params[ 'list_end' ];

		echo $this->disp_params['block_body_end'];

		echo $this->disp_params[ 'block_end' ];

		return true;
	}
}

?>