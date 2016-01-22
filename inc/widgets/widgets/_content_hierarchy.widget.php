<?php
/**
 * This file implements the Widget class to build a content hierarchy with categories and posts.
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
class content_hierarchy_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'content_hierarchy' );
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array_merge( array(
				'display_blog_title' => array(
					'label' => T_('Blog Title'),
					'note' => T_('Display blog title.'),
					'type' => 'checkbox',
					'defaultvalue' => true,
				),
				'open_children_levels' => array(
					'label' => T_('Open children levels'),
					'note' => T_('From 0 to 20.'),
					'type' => 'integer',
					'defaultvalue' => '0',
					'valid_pattern' => array( 'pattern'=>'~^(1?\d|20)$~i',
						'error'=>T_('We can display from 0 to 20 children levels.') ),
				),
				'blog_ID' => array(
					'label' => T_( 'Collection' ),
					'note' => T_( 'ID of the collection to use, leave empty for the current collection.' ),
					'size' => 4,
					'type' => 'integer',
					'allow_empty' => true,
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) )
		{ // Disable "allow blockcache" because this widget uses the selected items and the content is dynamic
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
		return get_manual_url( 'content-hierarchy-widget' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Content Hierarchy');
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
		return T_('Content Hierarchy');
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Item;

		$this->init_display( $params );

		echo $this->disp_params['block_start'];

		if( !empty( $Item ) )
		{ // Set selected Item in the params
			$params['selected_item_ID'] = $Item->ID;
		}

		$this->display_hierarchy( array_merge( array(
				'display_blog_title'   => $this->disp_params['display_blog_title'],
				'open_children_levels' => $this->disp_params['open_children_levels'],
				'sorted' => true
			), $params ) );

		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Display a content hierarchy as list with chapters and posts
	 *
	 * @param array Params
	 */
	function display_hierarchy( $params = array() )
	{
		$params = array_merge( array(
				'block_start'          => '',
				'block_end'            => '',
				'block_body_start'     => '',
				'block_body_end'       => '',
				'list_start'           => '<ul class="chapters_list">',
				'list_end'             => '</ul>',
				'list_subs_start'      => '<ul>',
				'list_subs_end'        => '</ul>',
				'item_start'           => '<li>',
				'item_end'             => '</li>',
				'item_before_opened'   => '',
				'item_before_closed'   => '',
				'item_before_post'     => '',
				'class_opened'         => 'opened',
				'class_closed'         => 'closed',
				'class_selected'       => 'selected',
				'class_post'           => 'post',
				'display_blog_title'   => true,
				'open_children_levels' => 0,
				'list_posts'           => true,
			), $params );

		global $blog, $cat, $Item;

		$BlogCache = & get_BlogCache();
		$ChapterCache = & get_ChapterCache();

		if( ! empty( $this->disp_params['blog_ID'] ) )
		{ // Set a Blog from widget setting
			$this->Blog = & $BlogCache->get_by_ID( intval( $this->disp_params['blog_ID'] ), false, false );
		}

		if( empty( $this->Blog ) && ! empty( $blog ) )
		{ // Use current Blog
			$this->Blog = & $BlogCache->get_by_ID( $blog, false, false );
		}

		if( empty( $this->Blog ) )
		{ // No Blog, Exit here
			return;
		}

		$chapter_path = array();
		if( !empty( $cat ) )
		{ // A category is opened
			$params['chapter_path'] = $ChapterCache->get_chapter_path( $this->Blog->ID, $cat );
		}
		elseif( ! empty( $Item ) && ! $Item->is_intro() )
		{ // A post is opened (Ignore intro posts)
			$params['chapter_path'] = $ChapterCache->get_chapter_path( $this->Blog->ID, $Item->main_cat_ID );
		}

		echo $params['block_body_start'];

		echo $params['list_start'];

		if( $params['display_blog_title'] )
		{ // Display blog title
			echo str_replace( '>', ' class="title '.$params['class_selected'].'">', $params['item_start'] );
			echo '<a href="'.$this->Blog->get( 'url' ).'" class="link">'.$this->Blog->get( 'name' ).'</a>';
			echo $params['item_end'];
		}

		$callbacks = array(
			'line' => array( $this, 'display_chapter' ),
			'before_level' => array( $this, 'cat_before_level' ),
			'after_level'  => array( $this, 'cat_after_level' ),
			'posts' => array( $this, 'display_post_row' ),
		);

		echo $ChapterCache->recurse( $callbacks, $this->Blog->ID, NULL, 0, 0, $params );

		echo $params['list_end'];

		echo $params['block_body_end'];
	}


	function cat_before_level( $level )
	{
		return '';
	}

	function cat_after_level( $level )
	{
		return '';
	}


	/**
	 * Display a chapter and the children of the given chapter
	 *
	 * @param Object Chapter
	 * @param integer level
	 * @param array params
	 */
	function display_chapter( $Chapter, $level, $params = array() )
	{
		// What display before link text, Used for icon
		$item_before = $params['item_before_closed'];

		$classes = array();

		if( $params['is_selected'] )
		{ // A category is selected
			$is_selected = true;
			$classes[] = $params['class_selected'];
		}
		if( $params['is_opened'] )
		{ // A category is opened/expanded
			$classes[] = $params['class_opened'];
			$item_before = $params['item_before_opened'];
		}

		// Display a category
		if( empty( $classes ) )
		{
			$r = $params['item_start'];
		}
		else
		{ // Add attr "class" for item start tag
			$r = str_replace( '>', ' class="'.implode( ' ', $classes ).'">', $params['item_start'] );
		}

		$r .= '<a href="'.$Chapter->get_permanent_url().'" class="link">'.$item_before.$Chapter->get_name().'</a>';

		$r .= $params['item_end'];
		return $r;
	}


	/**
	 * Display a post row into string
	 *
	 * @param object Item
	 * @param integer level
	 * @param array params
	 * @return string the Item row display
	 */
	function display_post_row( $Item, $level, $params = array() )
	{
		$classes = array( 'post' );
		if( isset( $params['selected_item_ID'] ) && $params['selected_item_ID'] == $Item->ID )
		{ // This post is selected
			$classes[] = $params['class_selected'];
		}

		// Display a post
		if( empty( $classes ) )
		{
			$r = $params['item_start'];
		}
		else
		{ // Add attr "class" for item start tag
			$r = str_replace( '>', ' class="'.implode( ' ', $classes ).'">', $params['item_start'] );
		}

		$display_params = array_merge( array(
				'before_title'    => $params['item_before_post'],
				'post_navigation' => 'same_category', // we are always navigating through category in this skin
				'nav_target'      => $params['chapter_ID'], // set the category ID as nav target
				'link_type'       => 'permalink',
				'link_class'      => 'link',
			), $params );

		if( $Item->main_cat_ID != $params['chapter_ID'] )
		{ // Posts from extracats are displayed with italic
			$display_params['before'] = '<i>';
			$display_params['after'] = '</i>';
		}

		// Display a permanent link to post
		$r .= $Item->get_title( $display_params );

		$r .= $params['item_end'];
		return $r;
	}
}

?>