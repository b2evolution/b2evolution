<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage manual
 *
 * @version $Id: _skin.class.php 7069 2014-07-04 08:32:23Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class manual_Skin extends Skin
{
	/**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Manual';
	}


  /**
	 * Get default type for the skin.
	 */
	function get_default_type()
	{
		return 'normal';
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
				'head_bg_color' => array(
					'label' => T_('Header Background Color'),
					'note' => T_('E-g: #ff0000 for red'),
					'defaultvalue' => '#03699C',
					'type' => 'color',
				),
				'head_text_color' => array(
					'label' => T_('Header Text Color'),
					'note' => T_('E-g: #00ff00 for green'),
					'defaultvalue' => '#FFFFFF',
					'type' => 'color',
				),
				'menu_bg_color' => array(
					'label' => T_('Menu Background Color'),
					'note' => T_('E-g: #ff0000 for red'),
					'defaultvalue' => '#74b4d4',
					'type' => 'color',
				),
				'menu_text_color' => array(
					'label' => T_('Menu Text Color'),
					'note' => T_('E-g: #00ff00 for green'),
					'defaultvalue' => '#000000',
					'type' => 'color',
				),
				'footer_bg_color' => array(
					'label' => T_('Footer Background Color'),
					'note' => T_('E-g: #0000ff for blue'),
					'defaultvalue' => '#DEE3E7',
					'type' => 'color',
				),
				'colorbox' => array(
					'label' => T_('Colorbox Image Zoom'),
					'note' => T_('Check to enable javascript zooming on images (using the colorbox script)'),
					'defaultvalue' => 1,
					'type'	=>	'checkbox',
				),
				'gender_colored' => array(
					'label' => T_('Display gender'),
					'note' => T_('Use colored usernames to differentiate men & women.'),
					'defaultvalue' => 0,
					'type' => 'checkbox',
				),
				'bubbletip' => array(
					'label' => T_('Username bubble tips'),
					'note' => T_('Check to enable bubble tips on usernames'),
					'defaultvalue' => 0,
					'type' => 'checkbox',
				),
			), parent::get_param_definitions( $params )	);

		return $r;
	}


	/**
	 * Get current skin post navigation setting. Always use this navigation setting where this skin is applied.
	 */
	function get_post_navigation()
	{
		return 'same_category';
	}


	/**
	 * Get ready for displaying the skin.
	 *
	 * This may register some CSS or JS...
	 */
	function display_init()
	{
		// call parent:
		parent::display_init();

		// Add custom CSS:
		$custom_css = '';

		if( $color = $this->get_setting( 'head_bg_color' ) )
		{ // Custom Header background color:
			$custom_css .= '	div.pageHeader { background-color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'head_text_color' ) )
		{ // Custom Header text color:
			$custom_css .= '	div.pageHeader, div.pageHeader a { color: '.$color." }\n";
		}

		if( $color = $this->get_setting( 'menu_bg_color' ) )
		{ // Custom Menu background color:
			$custom_css .= '	div.top_menu_bg { background-color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'menu_text_color' ) )
		{ // Custom Menu text color:
			$custom_css .= '	div.top_menu a { color: '.$color." }\n";
		}

		if( $color = $this->get_setting( 'footer_bg_color' ) )
		{ // Custom Footer background color:
			$custom_css .= '	div#pageFooter { background-color: '.$color." }\n";
		}

		if( !empty( $custom_css ) )
		{
			$custom_css = '<style type="text/css">
	<!--
'.$custom_css.'	-->
	</style>';
			add_headline( $custom_css );
		}

		// Functions to switch between the width sizes
		require_js( '#jquery#', 'blog' );
		require_js( 'widthswitcher.js', 'blog' );
	}


	/**
	 * Display breadcrumbs
	 *
	 * @param integer Chapter ID
	 * @param array Params
	 * @return string Breadcrumbs path if $params['display'] == false
	 */
	function display_breadcrumbs( $chapter_ID, $params = array() )
	{
		if( $chapter_ID <= 0 )
		{ // No selected chapter, or an exlcude chapter filter is set
			return '';
		}

		$params = array_merge( array(
				'before'    => '<div class="breadcrumbs">',
				'after'     => '</div>',
				'separator' => ' &gt; ',
				'display'   => true, // true - to display, false - to return as string
				'format'    => 'link', // 'link' - get breadcrumbs with links | 'text' - only text
				'blogname'  => true, // true - to include blog name in breadcrumbs
			), $params );

		global $Blog;

		$ChapterCache = & get_ChapterCache();

		$breadcrumbs = array();
		do
		{	// Get all parent chapters
			if( $Chapter = & $ChapterCache->get_by_ID( $chapter_ID, false ) )
			{
				if( $params['format'] == 'link' )
				{	// Make a link to the Chapter
					$breadcrumbs[] = '<a href="'.$Chapter->get_permanent_url().'">'.$Chapter->dget( 'name' ).'</a>';
				}
				else
				{	// Get only name of the Chapter
					$breadcrumbs[] = $Chapter->dget( 'name' );
				}
				$chapter_ID = $Chapter->get( 'parent_ID' );
			}
			else
			{
				$chapter_ID = 0;
			}
		}
		while( !empty( $chapter_ID ) );

		if( $params['blogname'] )
		{	// Include Blog name
			if( $params['format'] == 'link' )
			{	// Make a link to the Blog
				$breadcrumbs[] = '<a href="'.$Blog->get( 'blogurl' ).'">'.$Blog->get( 'name' ).'</a>';
			}
			else
			{	// Get only name of the Blog
				$breadcrumbs[] = $Blog->get( 'name' );
			}
		}

		$breadcrumbs = array_reverse( $breadcrumbs );

		$r = $params['before']
			.implode( $params['separator'], $breadcrumbs )
			.$params['after'];

		if( $params['display'] )
		{	// Display
			echo $r;
		}
		else
		{	// Return
			return $r;
		}
	}

	/**
	 * Get chapters
	 *
	 * @param integer Chapter parent ID
	 */
	function get_chapters( $parent_ID = 0 )
	{
		global $Blog, $skin_chapters_cache;

		if( !isset( $skin_chapters_cache ) )
		{	// Get the all chapters for current blog
			$ChapterCache = & get_ChapterCache();
			$ChapterCache->load_subset( $Blog->ID );

			if( isset( $ChapterCache->subset_cache[ $Blog->ID ] ) )
			{
				$chapters = $ChapterCache->subset_cache[ $Blog->ID ];

				$skin_chapters_cache = array();
				foreach( $chapters as $chapter_ID => $Chapter )
				{	// Init children
					//pre_dump( $Chapter->ID.' - '.$Chapter->get_name().' : '.$Chapter->get( 'parent_ID' ) );
					if( $Chapter->get( 'parent_ID' ) == 0 )
					{
						$Chapter->children = $this->get_chapter_children( $Chapter->ID );
						$skin_chapters_cache[ $Chapter->ID ] = $Chapter;
					}
				}
			}
		}

		if( $parent_ID > 0 )
		{	// Get the chapters by parent
			$ChapterCache = & get_ChapterCache();
			if( $Chapter = & $ChapterCache->get_by_ID( $parent_ID, false ) )
			{
				return $Chapter->children;
			}
			else
			{	// Invalid ID of parent category
				return array();
			}
		}

		return $skin_chapters_cache;
	}


	/**
	 * Get the children of current chapter recursively
	 *
	 * @param integer Parent ID
	 * @return array Chapter children
	 */
	function get_chapter_children( $parent_ID = 0 )
	{
		global $blog;

		$ChapterCache = & get_ChapterCache();

		$chapter_children = array();
		if( isset( $ChapterCache->subset_cache[ $blog ] ) )
		{
			$chapters = $ChapterCache->subset_cache[ $blog ];
			foreach( $chapters as $Chapter )
			{
				if( $parent_ID == $Chapter->get( 'parent_ID' ) )
				{
					$Chapter->children = $this->get_chapter_children( $Chapter->ID );
					$chapter_children[ $Chapter->ID ] = $Chapter;
				}
			}
		}

		return $chapter_children;
	}


	/**
	 * Get an array with chapters ID that located in current path
	 *
	 * @param integer Chapter ID
	 * @return array Chapters ID
	 */
	function get_chapter_path( $chapter_ID )
	{
		global $blog;
		$ChapterCache = & get_ChapterCache();
		$ChapterCache->load_subset( $blog );

		$chapter_path = array( $chapter_ID );
		if( isset( $ChapterCache->subset_cache[ $blog ] ) )
		{
			$chapters = $ChapterCache->subset_cache[ $blog ];
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
	 * Display chapters list
	 *
	 * @param array Params
	 */
	function display_chapters( $params = array() )
	{
		$params = array_merge( array(
				'parent_cat_ID'      => 0,
				'block_start'        => '<ul class="chapters_list">',
				'block_end'          => '</ul>',
				'block_subs_start'   => '<ul>',
				'block_subs_end'     => '</ul>',
				'item_start'         => '<li>',
				'item_end'           => '</li>',
				'class_opened'       => 'opened',
				'class_closed'       => 'closed',
				'class_selected'     => 'selected',
				'class_post'         => 'post',
				'display_blog_title' => true,
				'display_children'   => false,
				'display_posts'      => true,
			), $params );

		global $Blog, $blog, $cat, $Item;

		$chapters = $this->get_chapters( (int)$params['parent_cat_ID'] );

		if( empty( $chapters ) )
		{	// No categories, Exit here
			return;
		}

		if( empty( $Blog ) && !empty( $blog ) )
		{	// Set Blog if it still doesn't exist
			$BlogCache = & get_BlogCache();
			$Blog = & $BlogCache->get_by_ID( $blog, false );
		}

		if( empty( $Blog ) )
		{	// No Blog, Exit here
			return;
		}

		$chapter_path = array();
		if( !empty( $cat ) )
		{	// A category is opened
			$chapter_path = $this->get_chapter_path( $cat );
		}
		elseif( !empty( $Item ) )
		{	// A post is opened
			$chapter_path = $this->get_chapter_path( $Item->main_cat_ID );
		}

		echo $params['block_start'];

		if( $params['display_blog_title'] )
		{	// Display blog title
			echo str_replace( '>', ' class="title">', $params['item_start'] );
			echo '<a href="'.$Blog->get( 'url' ).'" class="link">'.$Blog->get( 'name' ).'</a>';
			echo $params['item_end'];
		}

		foreach( $chapters as $Chapter )
		{	// Display all given chapters
			$this->display_chapter_item( array_merge( $params, array(
					'Chapter'      => $Chapter,
					'chapter_path' => $chapter_path,
				) ) );
		}

		echo $params['block_end'];
	}


	/**
	 *
	 *
	 * @param array Params
	 */
	function display_chapter_item( $params = array() )
	{
		$params = array_merge( array(
				'Chapter'      => NULL,
				'chapter_path' => array(),
			), $params );

		global $Blog;

		if( empty( $params['Chapter'] ) )
		{	// No Chapter, Exit here
			return;
		}

		$Chapter = & $params['Chapter'];

		$is_selected = false;

		$classes = array();
		if( !$params['display_children'] && !empty( $params['chapter_path'] ) && in_array( $Chapter->ID, $params['chapter_path'] ) )
		{	// A category is selected
			$is_selected = true;
			$classes[] = $params['class_selected'];
		}
		if( !empty( $Chapter->children ) && ( $params['display_children'] || $is_selected ) )
		{	// A category is opened
			$classes[] = $params['class_opened'];
		}
		else if( $Chapter->has_posts() && ( $params['display_children'] || $is_selected ) )
		{	// A category is selected and it has the posts
			$classes[] = $params['class_opened'];
		}

		// Display a category
		if( empty( $classes ) )
		{
			echo $params['item_start'];
		}
		else
		{	// Add attr "class" for item start tag
			echo str_replace( '>', ' class="'.implode( ' ', $classes ).'">', $params['item_start'] );
		}

		//echo '<a href="'.$Chapter->get_permanent_url().'" class="link">'.$Chapter->get_name().'</a>'.' <span class="red">'.( $Chapter->get( 'order' )> 0? $Chapter->get( 'order' ) : 'NULL').'</span>';
		echo '<a href="'.$Chapter->get_permanent_url().'" class="link">'.$Chapter->get_name().'</a>';

		if( $params['display_children'] || $is_selected )
		{
			global $Settings;

			if( $Settings->get( 'chapter_ordering' ) == 'manual' &&
				  $Blog->get_setting( 'orderby' ) == 'order' &&
				  $Blog->get_setting( 'orderdir' ) == 'ASC' )
			{	// Items & categories are ordered by manual field 'order'
				// In this mode we should show them in one merged list ordered by field 'order'
				$chapters_items_mode = 'order';
			}
			else
			{	// Standard mode for all other cases
				$chapters_items_mode = 'std';
			}

			if( $chapters_items_mode != 'order' )
			{	// Display all subchapters
				$this->display_chapters( array_merge( $params, array(
						'parent_cat_ID'      => $Chapter->ID,
						'block_start'        => $params['block_subs_start'],
						'block_end'          => $params['block_subs_end'],
						'display_blog_title' => false,
					) ) );
			}

			if( $params['display_posts'] && $Chapter->has_posts() )
			{	// Display the posts of this chapter
				echo $params['block_subs_start'];
				$this->display_chapter_posts( array_merge( $params, array(
					'chapter_ID'          => $Chapter->ID,
					'chapters_items_mode' => $chapters_items_mode,
				) ) );
				echo $params['block_subs_end'];
			}
		}

		echo $params['item_end'];
	}


	/**
	 * Display a list of the posts for current chapter
	 *
	 * @param array params
	 * @return string List with posts
	 */
	function display_chapter_posts( $params = array() )
	{
		$params = array_merge( array(
				'chapter_ID'          => 0,
				'item_start'          => '<li>',
				'item_end'            => '</li>',
				'class_selected'      => 'selected',
				'class_post'          => 'post',
				'chapters_items_mode' => 'std',
				'display_children'    => false,
				'display_posts'       => false,
			), $params );

		global $DB, $Item, $Blog, $blog;

		if( empty( $Blog ) && !empty( $blog ) )
		{	// Set Blog if it still doesn't exist
			$BlogCache = & get_BlogCache();
			$Blog = & $BlogCache->get_by_ID( $blog, false );
		}

		if( empty( $params['chapter_ID'] ) || empty( $Blog ) )
		{	// No chapter ID, Exit here
			return;
		}

		if( $params['chapters_items_mode'] == 'order' )
		{	// Get all subchapters in this mode to following insertion into posts list below
			$sub_chapters = $this->get_chapters( $params['chapter_ID'] );
		}

		// Get the posts of current category
		$ItemList = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), $Blog->get_setting('posts_per_page') );
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
			{	// Item is from main category
				$items_main[] = $cur_Item;
			}
			else
			{	// Item is from extra catogry
				$items_extra[] = $cur_Item;
			}
		}


		// ---- Display Items from MAIN category ---- //
		$prev_item_order = 0;
		foreach( $items_main as $cur_Item )
		{
			if( $params['chapters_items_mode'] == 'order' )
			{	// In this mode we display the chapters inside a posts list
				foreach( $sub_chapters as $s => $sub_Chapter )
				{	// Loop through categories to find for current order
					if( ( $sub_Chapter->get( 'order' ) <= $cur_Item->get( 'order' ) && $sub_Chapter->get( 'order' ) > $prev_item_order ) ||
							  /* This condition is needed for NULL order: */
							  ( $cur_Item->get( 'order' ) == 0 && $sub_Chapter->get( 'order' ) >= $cur_Item->get( 'order' ) ) )
					{	// Display chapter
						$this->display_chapter_item( array_merge( $params, array(
								'Chapter'      => $sub_Chapter,
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
			{	// This post is selected
				$classes[] = $params['class_selected'];
			}

			// Display a post
			if( empty( $classes ) )
			{
				echo $params['item_start'];
			}
			else
			{	// Add attr "class" for item start tag
				echo str_replace( '>', ' class="'.implode( ' ', $classes ).'">', $params['item_start'] );
			}

			// Display a permanent link to post
			$cur_Item->title( array(
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
			{	// Loop through rest categories that have order more than last item
				$this->display_chapter_item( array_merge( $params, array(
						'Chapter'      => $sub_Chapter,
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
			{	// This post is selected
				$classes[] = $params['class_selected'];
			}

			// Display a post
			if( empty( $classes ) )
			{
				echo $params['item_start'];
			}
			else
			{	// Add attr "class" for item start tag
				echo str_replace( '>', ' class="'.implode( ' ', $classes ).'">', $params['item_start'] );
			}

			// Display a permanent link to post
			$cur_Item->title( array(
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