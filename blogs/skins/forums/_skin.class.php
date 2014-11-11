<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage forums
 *
 * @version $Id: _skin.class.php 13 2011-10-24 23:42:53Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class forums_Skin extends Skin
{
  /**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Forums';
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
				'display_post_date' => array(
					'label' => T_('Post date'),
					'note' => T_('Display the date of each post'),
					'defaultvalue' => 1,
					'type' => 'checkbox',
				),
				'colorbox' => array(
					'label' => T_('Colorbox Image Zoom'),
					'note' => T_('Check to enable javascript zooming on images (using the colorbox script)'),
					'defaultvalue' => 1,
					'type' => 'checkbox',
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
				'banner_public' => array(
					'label' => T_('"Public" banner'),
					'note' => T_('Display banner for "Public" posts (posts & comments)'),
					'defaultvalue' => 1,
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
		global $disp;

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

		if( in_array( $disp, array( 'single', 'page', 'comments' ) ) )
		{ // Load jquery UI to animate background color on change comment status or on vote
			require_js( '#jqueryUI#', 'blog' );
		}
	}


	/**
	 * Display breadcrumbs
	 *
	 * @param integer Chapter ID
	 * @param array Params
	 */
	function display_breadcrumbs( $chapter_ID, $params = array() )
	{
		if( $chapter_ID <= 0 )
		{ // No selected chapter, or an exlcude chapter filter is set
			return;
		}

		$params = array_merge( array(
				'before'    => '<div class="breadcrumbs">',
				'after'     => '</div>',
				'separator' => ' -> ',
			), $params );

		global $Blog;

		$ChapterCache = & get_ChapterCache();

		$breadcrumbs = array();
		do
		{	// Get all parent chapters
			$Chapter = & $ChapterCache->get_by_ID( $chapter_ID );

			$breadcrumbs[] = '<a href="'.$Chapter->get_permanent_url().'">'.$Chapter->dget( 'name' ).'</a>';

			$chapter_ID = $Chapter->get( 'parent_ID' );
		}
		while( !empty( $chapter_ID ) );

		$breadcrumbs[] = '<a href="'.$Blog->get( 'blogurl' ).'">'.$Blog->get( 'name' ).'</a>';
		$breadcrumbs = array_reverse( $breadcrumbs );

		// Display
		echo $params['before'];
		echo implode( $params['separator'], $breadcrumbs );
		echo $params['after'];
	}


	/**
	 * Display button to create a new post
	 *
	 * @param integer Chapter ID
	 */
	function display_post_button( $chapter_ID, $Item = NULL )
	{
		global $Blog;

		$post_button = '';

		$chapter_is_locked = false;

		$write_new_post_url = $Blog->get_write_item_url( $chapter_ID );
		if( $write_new_post_url != '' )
		{ // Display button to write a new post
			$post_button = '<a href="'.$write_new_post_url.'"><span class="ficon newTopic" title="'.T_('Post new topic').'"></span></a>';
		}
		else
		{ // If a creating of new post is unavailable
			$ChapterCache = & get_ChapterCache();
			$current_Chapter = $ChapterCache->get_by_ID( $chapter_ID, false, false );

			if( $current_Chapter && $current_Chapter->lock )
			{ // Display icon to inform that this forum is locked
				$post_button = '<span class="ficon locked" title="'.T_('This forum is locked: you cannot post, reply to, or edit topics.').'"></span>';
				$chapter_is_locked = true;
			}
		}

		if( !empty( $Item ) )
		{
			if( $Item->comment_status == 'closed' || $Item->comment_status == 'disabled' || $Item->is_locked() )
			{ // Display icon to inform that this topic is locked for comments
				if( !$chapter_is_locked )
				{ // Display this button only when chapter is not locked, to avoid a duplicate button
					$post_button .= ' <span class="ficon locked" title="'.T_('This topic is locked: you cannot edit posts or make replies.').'"></span>';
				}
			}
			else
			{ // Display button to post a reply
				$post_button .= ' <a href="'.$Item->get_feedback_url().'#form_p'.$Item->ID.'"><span class="ficon postReply" title="'.T_('Reply to topic').'"></span></a>';
			}
		}

		if( !empty( $post_button ) )
		{ // Display button
			echo '<div class="post_button">';
			echo $post_button;
			echo '</div>';
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

		if( isset( $skin_chapters_cache ) )
		{	// Get chapters from cache
			return $skin_chapters_cache;
		}

		$skin_chapters_cache = array();
		if( $parent_ID > 0 )
		{	// Get children of selected chapter
			global $DB, $Settings;

			$skin_chapters_cache = array();

			$SQL = new SQL();
			$SQL->SELECT( 'cat_ID' );
			$SQL->FROM( 'T_categories' );
			$SQL->WHERE( 'cat_parent_ID = '.$DB->quote( $parent_ID ) );
			if( $Settings->get( 'chapter_ordering' ) == 'manual' )
			{	// Manual order
				$SQL->ORDER_BY( 'cat_meta, cat_order' );
			}
			else
			{	// Alphabetic order
				$SQL->ORDER_BY( 'cat_meta, cat_name' );
			}

			$ChapterCache = & get_ChapterCache();

			$categories = $DB->get_results( $SQL->get() );
			foreach( $categories as $c => $category )
			{
				$skin_chapters_cache[$c] = $ChapterCache->get_by_ID( $category->cat_ID );
				// Get children
				$SQL->WHERE( 'cat_parent_ID = '.$DB->quote( $category->cat_ID ) );
				$children = $DB->get_results( $SQL->get() );
				foreach( $children as $child_Chapter )
				{
					$skin_chapters_cache[$c]->children[$child_Chapter->cat_ID] = $ChapterCache->get_by_ID( $child_Chapter->cat_ID );
				}
			}
		}
		else
		{	// Get the all chapters for current blog
			$ChapterCache = & get_ChapterCache();
			$ChapterCache->load_subset( $Blog->ID );

			if( isset( $ChapterCache->subset_cache[ $Blog->ID ] ) )
			{
				$skin_chapters_cache = $ChapterCache->subset_cache[ $Blog->ID ];

				foreach( $skin_chapters_cache as $c => $Chapter )
				{ // Init children
					foreach( $skin_chapters_cache as $child_Chapter )
					{ // Again go through all chapters to find a children for current chapter
						if( $Chapter->ID == $child_Chapter->get( 'parent_ID' ) )
						{ // Add to array of children
							$skin_chapters_cache[$c]->children[$child_Chapter->ID] = $child_Chapter;
						}
					}
				}

				foreach( $skin_chapters_cache as $c => $Chapter )
				{ // Unset the child chapters
					if( $Chapter->get( 'parent_ID' ) )
					{
						unset( $skin_chapters_cache[$c] );
					}
				}
			}
		}

		return $skin_chapters_cache;
	}


	/**
	 * Determine to display status banner or to don't display
	 *
	 * @param string Status of Item or Comment
	 * @return boolean TRUE if we can display status banner for given status
	 */
	function enabled_status_banner( $status )
	{
		if( $status != 'published' )
		{	// Display status banner everytime when status is not 'published'
			return true;
		}

		if( is_logged_in() && $this->get_setting( 'banner_public' ) )
		{	// Also display status banner if status is 'published'
			//   AND current user is logged in
			//   AND this feature is enabled in skin settings
			return true;
		}

		// Don't display status banner
		return false;
	}
}

?>