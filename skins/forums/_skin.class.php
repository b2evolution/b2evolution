<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage forums
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
				'colorbox_vote_post' => array(
					'label' => T_('Voting on Post Images'),
					'note' => T_('Check this to enable AJAX voting buttons in the colorbox zoom view'),
					'defaultvalue' => 1,
					'type' => 'checkbox',
				),
				'colorbox_vote_post_numbers' => array(
					'label' => T_('Display Votes'),
					'note' => T_('Check to display number of likes and dislikes'),
					'defaultvalue' => 1,
					'type' => 'checkbox',
				),
				'colorbox_vote_comment' => array(
					'label' => T_('Voting on Comment Images'),
					'note' => T_('Check this to enable AJAX voting buttons in the colorbox zoom view'),
					'defaultvalue' => 1,
					'type' => 'checkbox',
				),
				'colorbox_vote_comment_numbers' => array(
					'label' => T_('Display Votes'),
					'note' => T_('Check to display number of likes and dislikes'),
					'defaultvalue' => 1,
					'type' => 'checkbox',
				),
				'colorbox_vote_user' => array(
					'label' => T_('Voting on User Images'),
					'note' => T_('Check this to enable AJAX voting buttons in the colorbox zoom view'),
					'defaultvalue' => 1,
					'type' => 'checkbox',
				),
				'colorbox_vote_user_numbers' => array(
					'label' => T_('Display Votes'),
					'note' => T_('Check to display number of likes and dislikes'),
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
				'autocomplete_usernames' => array(
					'label' => T_('Autocomplete usernames'),
					'note' => T_('Check to enable auto-completion of usernames entered after a "@" sign in the comment forms'),
					'defaultvalue' => 1,
					'type' => 'checkbox',
				),
				'banner_public' => array(
					'label' => T_('"Public" banner'),
					'note' => T_('Display banner for "Public" posts (posts & comments)'),
					'defaultvalue' => 1,
					'type' => 'checkbox',
				),
			), parent::get_param_definitions( $params ) );

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
		if( $chapter_ID == 0 )
		{	// No selected chapter
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

		$ChapterCache = & get_ChapterCache();
		$ChapterCache->reveal_children( $Blog->ID, true );

		$skin_chapters_cache = array();
		if( $parent_ID > 0 )
		{ // Get children of selected chapter
			$ChapterCache = & get_ChapterCache();
			$parent_Chapter = $ChapterCache->get_by_ID( $parent_ID );
			$parent_Chapter->sort_children();
			foreach( $parent_Chapter->children as $Chapter )
			{ // Iterate through childrens or the given parent Chapter
				$skin_chapters_cache[$Chapter->ID] = $ChapterCache->get_by_ID( $Chapter->ID );
				$Chapter->sort_children();
			}
		}
		else
		{ // Get the current blog root chapters
			foreach( $ChapterCache->subset_root_cats[ $Blog->ID] as $Chapter )
			{
				$skin_chapters_cache[$Chapter->ID] = $Chapter;
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


	/**
	 * Those templates are used for example by the messaging screens.
	 */
	function get_template( $name )
	{
		switch( $name )
		{
			case 'Form':
				// Default Form settings:
				return array(
					'layout'            => 'fieldset',
					'title_fmt'         => '<span style="float:right">$global_icons$</span><h2>$title$</h2>'."\n",
					'no_title_fmt'      => '<span style="float:right">$global_icons$</span>'."\n",
					'formstart'         => '<table class="bForums" width="100%" cellspacing="1" cellpadding="2" border="0">',
					'formend'           => '</table>',
					'fieldset_begin'    => '<tr><th colspan="3" $fieldset_attribs$>$fieldset_title$</th></tr>',
					'fieldset_end'      => '',
					'fieldstart'        => '<tr $ID$>',
					'fieldend'          => '</tr>',
					'labelclass'        => '',
					'labelstart'        => '<td class="row1 left">',
					'labelend'          => '</td>',
					'labelempty'        => '<td class="row1 left"></td>',
					'inputstart'        => '<td class="row2 left">',
					'inputend'          => '</td>',
					'infostart'         => '<td class="row2 left" colspan="2">',
					'infoend'           => '</td>',
					'buttonsstart'      => '<tr><td colspan="2" class="buttons">',
					'buttonsend'        => '</td></tr>',
					'inline_labelstart' => '<td class="left" colspan="2">',
					'inline_labelend'   => '</td>',
					'inline_inputstart' => '',
					'inline_inputend'   => '',
					'customstart'       => '<tr><td colspan="2" class="custom_content">',
					'customend'         => '</td></tr>',
					'note_format'       => ' <span class="notes">%s</span>',
				);

			default:
				// Delegate to parent class:
				return parent::get_template( $name );
		}
	}
}

?>