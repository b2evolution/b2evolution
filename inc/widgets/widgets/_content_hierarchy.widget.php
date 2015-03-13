<?php
/**
 * This file implements the Widget class to build a content hierarchy with categories and posts.
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
class content_hierarchy_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function content_hierarchy_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'content_hierarchy' );
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
		$this->init_display( $params );

		echo $this->disp_params['block_start'];

		$this->display_hierarchy( array_merge( array(
				'display_blog_title'   => $this->disp_params['display_blog_title'],
				'open_children_levels' => $this->disp_params['open_children_levels']
			), $params ) );

		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Get an array with chapters ID that located in current path
	 *
	 * @param integer Chapter ID
	 * @return array Chapters ID
	 */
	function get_chapter_path( $chapter_ID )
	{
		$ChapterCache = & get_ChapterCache();
		$ChapterCache->load_subset( $this->Blog->ID );

		$chapter_path = array( $chapter_ID );
		if( isset( $ChapterCache->subset_cache[ $this->Blog->ID ] ) )
		{
			$chapters = $ChapterCache->subset_cache[ $this->Blog->ID ];
			if( isset( $chapters[ $chapter_ID ] ) )
			{
				$Chapter = $chapters[ $chapter_ID ];
				while( $Chapter->get( 'parent_ID' ) > 0 )
				{
					$chapter_path[] = $Chapter->get( 'parent_ID' );
					// Select a parent chapter
					$Chapter = $chapters[ $Chapter->get( 'parent_ID' ) ];
				}
			}
		}

		return $chapter_path;
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

		$chapters = get_chapters( $this->Blog->ID, 0 );

		if( empty( $chapters ) )
		{ // No categories, Exit here
			return;
		}

		$chapter_path = array();
		if( !empty( $cat ) )
		{ // A category is opened
			$chapter_path = $this->get_chapter_path( $cat );
		}
		elseif( ! empty( $Item ) && ! $Item->is_intro() )
		{ // A post is opened (Ignore intro posts)
			$chapter_path = $this->get_chapter_path( $Item->main_cat_ID );
		}

		echo $params['block_body_start'];

		echo $params['list_start'];

		if( $params['display_blog_title'] )
		{ // Display blog title
			echo str_replace( '>', ' class="title '.$params['class_selected'].'">', $params['item_start'] );
			echo '<a href="'.$this->Blog->get( 'url' ).'" class="link">'.$this->Blog->get( 'name' ).'</a>';
			echo $params['item_end'];
		}

		foreach( $chapters as $Chapter )
		{ // Display all given chapters
			$this->display_chapter( array_merge( $params, array(
					'Chapter'      => $Chapter,
					'chapter_path' => $chapter_path,
				) ) );
		}

		echo $params['list_end'];

		echo $params['block_body_end'];
	}


	/**
	 * Display a chapter and the children of the given chapter
	 *
	 * @param array Params
	 */
	function display_chapter( $params = array() )
	{
		$params = array_merge( array(
				'Chapter'      => NULL,
				'chapter_path' => array(),
			), $params );

		if( isset( $this->chapter_level ) )
		{ // Increase a level
			$this->chapter_level++;
		}
		else
		{ // Init this only first time
			$this->chapter_level = 0;
		}

		if( empty( $params['Chapter'] ) )
		{ // No Chapter, Exit here
			return;
		}

		$Chapter = & $params['Chapter'];

		$is_selected = false;

		// What display before link text, Used for icon
		$item_before = $params['item_before_closed'];

		$classes = array();
		if( ! empty( $params['chapter_path'] ) && in_array( $Chapter->ID, $params['chapter_path'] ) )
		{ // A category is selected
			$is_selected = true;
			$classes[] = $params['class_selected'];
		}
		if( ! empty( $Chapter->children ) && ( $params['open_children_levels'] > $this->chapter_level || $is_selected ) )
		{ // A category is opened
			$classes[] = $params['class_opened'];
			$item_before = $params['item_before_opened'];
		}
		else if( $Chapter->has_posts() && ( $params['open_children_levels'] > $this->chapter_level || $is_selected ) )
		{ // A category is selected and it has the posts
			$classes[] = $params['class_opened'];
			$item_before = $params['item_before_opened'];
		}

		// Display a category
		if( empty( $classes ) )
		{
			echo $params['item_start'];
		}
		else
		{ // Add attr "class" for item start tag
			echo str_replace( '>', ' class="'.implode( ' ', $classes ).'">', $params['item_start'] );
		}

		echo '<a href="'.$Chapter->get_permanent_url().'" class="link">'.$item_before.$Chapter->get_name().'</a>';

		if( ( $params['open_children_levels'] > $this->chapter_level ) || $is_selected )
		{
			global $Settings;

			if( $Settings->get( 'chapter_ordering' ) == 'manual' &&
				  $this->Blog->get_setting( 'orderby' ) == 'order' &&
				  $this->Blog->get_setting( 'orderdir' ) == 'ASC' )
			{ // Items & categories are ordered by manual field 'order'
				// In this mode we should show them in one merged list ordered by field 'order'
				$chapters_items_mode = 'order';
			}
			else
			{ // Standard mode for all other cases
				$chapters_items_mode = 'std';
			}

			if( $chapters_items_mode != 'order' )
			{ // Display all sub chapters
				$sub_chapters = get_chapters( $this->Blog->ID, $Chapter->ID );
				if( count( $sub_chapters ) > 0 )
				{
					echo $params['list_subs_start'];
					foreach( $sub_chapters as $sub_Chapter )
					{ // Display all sub chapters
						$this->display_chapter( array_merge( $params, array(
								'Chapter' => $sub_Chapter,
								//'chapter_path' => $chapter_path,
								'list_start' => $params['list_subs_start'],
								'list_end'   => $params['list_subs_end'],
							) ) );
					}
					echo $params['list_subs_end'];
				}
			}

			if( $params['list_posts'] && $Chapter->has_posts() )
			{ // Display the posts of this chapter
				echo $params['list_subs_start'];
				$this->display_posts( array_merge( $params, array(
					'chapter_ID'          => $Chapter->ID,
					'chapters_items_mode' => $chapters_items_mode,
				) ) );
				echo $params['list_subs_end'];
			}
		}

		echo $params['item_end'];

		// Decrease a level
		$this->chapter_level--;
	}


	/**
	 * Display a list of the posts for current chapter
	 *
	 * @param array params
	 * @return string List with posts
	 */
	function display_posts( $params = array() )
	{
		$params = array_merge( array(
				'chapter_ID'           => 0,
				'item_start'           => '<li>',
				'item_end'             => '</li>',
				'class_selected'       => 'selected',
				'class_post'           => 'post',
				'chapters_items_mode'  => 'std',
				'open_children_levels' => 0,
				'list_posts'           => false,
			), $params );

		global $DB, $Item;

		if( empty( $params['chapter_ID'] ) || empty( $this->Blog ) )
		{ // No chapter ID, Exit here
			return;
		}

		if( $params['chapters_items_mode'] == 'order' )
		{ // Get all subchapters in this mode to following insertion into posts list below
			$sub_chapters = get_chapters( $this->Blog->ID, $params['chapter_ID'] );
		}

		// Get the posts of current category
		$ItemList = new ItemList2( $this->Blog, $this->Blog->get_timestamp_min(), $this->Blog->get_timestamp_max() );
		$ItemList->set_filters( array(
				'cat_array'    => array( $params['chapter_ID'] ), // Limit only by selected cat (exclude posts from child categories)
				'cat_modifier' => NULL,
				'unit'         => 'all', // Display all items of this category, Don't limit by page
			) );
		$ItemList->query();

		$selected_item_ID = ( !empty( $Item ) && !empty( $Item->ID ) ) ? $Item->ID : 0;

		// Split items in two arrays to know what items are from main category and what items are from extra category
		$items_main = array();
		$items_extra = array();
		while( $cur_Item = $ItemList->get_item() )
		{
			if( $cur_Item->main_cat_ID == $params['chapter_ID'] )
			{ // Item is from main category
				$items_main[] = $cur_Item;
			}
			else
			{ // Item is from extra catogry
				$items_extra[] = $cur_Item;
			}
		}


		// ---- Display Items from MAIN category ---- //
		$prev_item_order = 0;
		foreach( $items_main as $cur_Item )
		{
			if( $params['chapters_items_mode'] == 'order' )
			{ // In this mode we display the chapters inside a posts list
				foreach( $sub_chapters as $s => $sub_Chapter )
				{ // Loop through categories to find for current order
					if( ( $sub_Chapter->get( 'order' ) <= $cur_Item->get( 'order' ) && $sub_Chapter->get( 'order' ) > $prev_item_order ) ||
							  /* This condition is needed for NULL order: */
							  ( $cur_Item->get( 'order' ) == 0 && $sub_Chapter->get( 'order' ) >= $cur_Item->get( 'order' ) ) )
					{ // Display chapter
						$this->display_chapter( array_merge( $params, array(
								'Chapter' => $sub_Chapter,
							) ) );
						// Remove this chapter from array to avoid the duplicates
						unset( $sub_chapters[ $s ] );
					}
				}

				// Save current post order for next iteration
				$prev_item_order = $cur_Item->get( 'order' );
			}

			$classes = array( 'post' );
			if( $selected_item_ID == $cur_Item->ID )
			{ // This post is selected
				$classes[] = $params['class_selected'];
			}

			// Display a post
			if( empty( $classes ) )
			{
				echo $params['item_start'];
			}
			else
			{ // Add attr "class" for item start tag
				echo str_replace( '>', ' class="'.implode( ' ', $classes ).'">', $params['item_start'] );
			}

			// Display a permanent link to post
			$cur_Item->title( array(
					'before_title'    => $params['item_before_post'],
					'post_navigation' => 'same_category', // we are always navigating through category in this skin
					'nav_target'      => $params['chapter_ID'], // set the category ID as nav target
					'link_type'       => 'permalink',
					'link_class'      => 'link',
				) );

			//echo ' <span class="red">'.( $cur_Item->get('order') > 0 ? $cur_Item->get('order') : 'NULL').'</span>'.$params['item_end'];
			echo $params['item_end'];
		}

		if( $params['chapters_items_mode'] == 'order' )
		{
			foreach( $sub_chapters as $s => $sub_Chapter )
			{ // Loop through rest categories that have order more than last item
				$this->display_chapter( array_merge( $params, array(
						'Chapter' => $sub_Chapter,
					) ) );
				// Remove this chapter from array to avoid the duplicates
				unset( $sub_chapters[ $s ] );
			}
		}


		// ---- Display Items from EXTRA category ---- //
		foreach( $items_extra as $cur_Item )
		{
			$classes = array( 'post' );
			if( $selected_item_ID == $cur_Item->ID )
			{ // This post is selected
				$classes[] = $params['class_selected'];
			}

			// Display a post
			if( empty( $classes ) )
			{
				echo $params['item_start'];
			}
			else
			{ // Add attr "class" for item start tag
				echo str_replace( '>', ' class="'.implode( ' ', $classes ).'">', $params['item_start'] );
			}

			// Display a permanent link to post
			$cur_Item->title( array(
					'before_title'    => $params['item_before_post'],
					'post_navigation' => 'same_category', // we are always navigating through category in this skin
					'nav_target'      => $params['chapter_ID'], // set the category ID as nav target
					'link_type'       => 'permalink',
					'link_class'      => 'link',
					'before'          => '<i>',
					'after'           => '</i>',
				) );

			//echo ' <span class="red">'.( $cur_Item->get('order') > 0 ? $cur_Item->get('order') : 'NULL').'</span>'.$params['item_end'];
			echo $params['item_end'];
		}
	}
}

?>