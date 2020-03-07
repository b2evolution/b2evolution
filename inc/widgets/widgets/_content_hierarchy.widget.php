<?php
/**
 * Widget class to display a nested list of the full content hierarchy (Categories and Items) of a Collection.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
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
	var $icon = 'sitemap';

	/**
	 * Constructor
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( $db_row, 'core', 'content_hierarchy' );
	}


	/**
	 * Get JavaScript code which helps to edit widget form
	 *
	 * @return string
	 */
	function get_edit_form_javascript()
	{
		if( ( $widget_Blog = & $this->get_Blog() ) &&
		    $widget_Blog->get_setting( 'cache_enabled_widgets' ) )
		{	// Disable "Allow caching" when "Highlight current page" OR "Mark flagged posts" is enabled:
			return 'jQuery( "#'.$this->get_param_prefix().'highlight_current, #'.$this->get_param_prefix().'show_flags" ).click( function()
{
	jQuery( "#'.$this->get_param_prefix().'allow_blockcache" ).prop( "disabled",
		jQuery( "#'.$this->get_param_prefix().'highlight_current" ).prop( "checked" ) ||
		jQuery( "#'.$this->get_param_prefix().'show_flags" ).prop( "checked" ) )
} );';
		}
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
		return T_('Full hierarchical list Collection\'s Categories and Posts');
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Displays a nested list of the full content hierarchy (Categories/Chapters and Post/Items) of a Collection.');
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
				'title' => array(
					'label'        => T_('Block title'),
					'note'         => T_( 'Title to display in your skin.' ),
					'size'         => 40,
					'defaultvalue' => T_('Content Hierarchy'),
				),
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
				'exclude_cats' => array(
					'type' => 'text',
					'label' => T_('Root categories to exclude'),
					'note' => T_('A comma-separated list of category IDs that you want to exclude from the list.'),
					'valid_pattern' => array( 'pattern' => '/^(\d+(,\d+)*|-|\*)?$/',
																		'error'   => T_('Invalid list of Category IDs.') ),
				),
				'highlight_current' => array(
					'label' => T_('Highlight current page'),
					'note' => T_('If checked, the widget will open the current branch and highlight the current page or chapter.'),
					'type' => 'checkbox',
					'defaultvalue' => 1,
				),
				'show_flags' => array(
					'label' => T_('Mark flagged posts'),
					'note' => T_('If checked, the widget will display a flag icon after each flagged post.'),
					'type' => 'checkbox',
					'defaultvalue' => 1,
				),
			), parent::get_param_definitions( $params ) );

		if( isset( $r['allow_blockcache'] ) && (
		    // Check for editing form:
		    ( empty( $params['for_updating'] ) && ( $this->get_param( 'highlight_current', 1 ) || $this->get_param( 'show_flags', 1 ) ) ) ||
		    // Check for updating action:
		    ( ! empty( $params['for_updating'] ) && ( param( $this->get_param_prefix().'highlight_current', 'integer' ) || param( $this->get_param_prefix().'show_flags', 'integer' ) ) )
		  ) )
		{	// Disable "Allow caching" because this widget:
			// - highlights the current page and opens the branch of the current page automatically,
			// - display a falg icon after each flagged post by current User.
			$r['allow_blockcache']['disabled'] = 'disabled';
		}

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

		if( $this->disp_params['highlight_current'] || $this->disp_params['show_flags'] )
		{	// Disable block caching for this widget when it highlights the opened Item or Chapter:
			$this->disp_params['allow_blockcache'] = 0;
		}
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Item, $disp;

		$this->init_display( $params );

		if( !isset( $params['widget_content_hierarchy_params'] ) )
		{
			$params['widget_content_hierarchy_params'] = array();
		}

		if( ( $disp == 'single' || $disp == 'page' ) && ! empty( $Item ) )
		{	// Set selected Item in the params ONLY if we really view item page:
			$params['selected_item_ID'] = $Item->ID;
		}
		
		// Get IDs of categories that must be exluded:
		$this->excluded_cat_IDs = sanitize_id_list( $this->disp_params['exclude_cats'], true );
		
		// START DISPLAY:
		echo $this->disp_params['block_start'];

		// Display title if requested
		$this->disp_title();

		echo $this->disp_params['block_body_start'];

		$this->display_hierarchy( array_merge( array(
				'display_blog_title'   => $this->disp_params['display_blog_title'],
				'open_children_levels' => $this->disp_params['open_children_levels'],
				'highlight_current'    => $this->disp_params['highlight_current'],
				'show_flags'           => $this->disp_params['show_flags'],
				'item_title_fields'    => isset( $this->disp_params['item_title_fields'] ) ? $this->disp_params['item_title_fields'] : 'title',
				'excluded_cat_IDs'   => $this->excluded_cat_IDs,
				'sorted' => true
			), $params, $params['widget_content_hierarchy_params'] ) );

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
				'custom_title'         => '',
				'open_children_levels' => 0,
				'highlight_current'    => true,
				'list_posts'           => true,
				// Don't expand all categories by default for this widget, because it has a separate parameter 'open_children_levels':
				'expand_all'           => false,
			), $params );

		global $blog, $cat, $Item;

		$BlogCache = & get_BlogCache();
		$ChapterCache = & get_ChapterCache();

		//
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
			$this->display_error_message( 'Widget "'.$this->get_name().'" is hidden because the requested Collection #'.$this->disp_params['blog_ID'].' doesn\'t exist any more.' );
			return false;
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

		echo $params['block_start'];

		echo $params['block_body_start'];

		echo $params['list_start'];

		if( $params['display_blog_title'] )
		{	// Display blog title
			if( empty( $params['custom_title'] ) )
			{
				echo str_replace( '>', ' class="title '.$params['class_selected'].'">', $params['item_start'] );
				echo '<a href="'.$this->Blog->get( 'url' ).'" class="link">'.$this->Blog->get( 'name' ).'</a>';
				echo $params['item_end'];
			}
			else
			{
				echo $params['custom_title'];
			}
		}

		$callbacks = array(
			'line' => array( $this, 'display_chapter' ),
			'before_level' => array( $this, 'cat_before_level' ),
			'after_level'  => array( $this, 'cat_after_level' ),
			'posts' => array( $this, 'display_post_row' ),
		);

		if( strpos( $params['item_title_fields'], 'short_title' ) !== false )
		{	// Use function to order items/posts by short title if this field is used to display instead of default title field:
			$params['items_order_alpha_func'] = 'compare_items_by_short_title';
		}

		echo $ChapterCache->recurse( $callbacks, $this->Blog->ID, NULL, 0, $params['open_children_levels'] + 1, $params );

		echo $params['list_end'];

		echo $params['block_body_end'];

		echo $params['block_end'];
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

		if( $params['is_selected'] && $params['highlight_current'] )
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
		if( isset( $params['selected_item_ID'] ) && $params['selected_item_ID'] == $Item->ID  && $params['highlight_current'] )
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
				'target_blog'     => 'auto',
				'link_type'       => 'permalink',
				'link_class'      => 'link',
				'title_field'     => $params['item_title_fields'],
			), $params );

		if( $Item->main_cat_ID != $params['chapter_ID'] )
		{ // Posts from extracats are displayed with italic
			$display_params['before'] = '<i>';
			$display_params['after'] = '</i>';
		}

		// Display a permanent link to post:
		$r .= $Item->get_title( $display_params );

		if( $params['show_flags'] )
		{	// Flag:
			$r .= $Item->get_flag( array(
				'before'       => ' ',
				'only_flagged' => true,
				'allow_toggle' => false,
			) );
		}

		$r .= $params['item_end'];
		return $r;
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Collection, $Blog, $current_User;

		$blog_ID = intval( $this->disp_params['blog_ID'] );

		return array(
				'wi_ID'        => $this->ID, // Have the widget settings changed ?
				'set_coll_ID'  => $Blog->ID, // Have the settings of the blog changed ? (ex: new skin)
				'user_ID'      => ( is_logged_in() ? $current_User->ID : 0 ), // Has the current User changed?
				'cont_coll_ID' => empty( $blog_ID ) ? $Blog->ID : $blog_ID, // Has the content of the displayed blog changed ?
			);
	}
}

?>