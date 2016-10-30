<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage pureforums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class pureforums_Skin extends Skin
{
	/**
	 * Skin version
	 * @var string
	 */
	var $version = '6.7.8';

  /**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Pure Forums';
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
				'avatar_style' => array(
					'label' => T_('Style of profile pictures'),
					'note' => '',
					'defaultvalue' => 'round',
					'type' => 'radio',
					'options' => array(
						array( 'round', T_('Round the corners of profile pictures') ),
						array( 'square', T_('Original pictures with square corners') ) ),
					'field_lines' => true,
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
					'label' => T_('Display Post Votes'),
					'note' => T_('Check to display number of post likes and dislikes'),
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
					'label' => T_('Display Comment Votes'),
					'note' => T_('Check to display number of comment likes and dislikes'),
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
				'width_switcher' => array(
					'label' => T_('Width switcher'),
					'note' => T_('Check to enable the width switcher between fixed value and 100%.'),
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

		// Request some common features that the parent function (Skin::display_init()) knows how to provide:
		parent::display_init( array(
				'font_awesome',   // Load Font Awesome (and use its icons as a priority over the Bootstrap glyphicons)
				'b2evo_base_css', // Include the b2evo_base CSS (OLD / v5 style) - Use this when you DON'T use Bootstrap:
				'colorbox',       // Load Colorbox (a lightweight Lightbox alternative + customizations for b2evo)
				'disp_auto',      // Automatically include additional CSS and/or JS required by certain disps (replace with 'disp_off' to disable this)
			) );

		// $this->require_css( 'pureforums_header.css' );
		// $this->require_css( 'pureforums_main.css' );
		// $this->require_css( 'pureforums_footer.css' );
		// $this->require_css( 'pureforums.bundle.css' ); // Concatenation of the above
		$this->require_css( 'pureforums.bmin.css' ); // Concatenation + Minifaction of the above

		if( $this->get_setting( 'width_switcher' ) )
		{ // Functions to switch between the width sizes
			require_js( '#jquery#', 'blog' );
			require_js( 'widthswitcher.js', 'blog' );
		}

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
				'separator' => '',
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
	 * @param object Item
	 */
	function display_post_button( $chapter_ID, $Item = NULL )
	{
		echo $this->get_post_button( $chapter_ID, $Item );
	}


	/**
	 * Get HTML code of button to create a new post
	 *
	 * @param integer Chapter ID
	 * @param object Item
	 * @return string
	 */
	function get_post_button( $chapter_ID, $Item = NULL )
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
			return '<div class="post_button">'.$post_button.'</div>';
		}
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
			case 'Results':
				// Results list:
				return array(
					'page_url' => '', // All generated links will refer to the current page
					'before' => '<div class="results">',
					'content_start' => '<div id="$prefix$ajax_content">',
					'header_start' => '',
						'header_text' => '<div class="center"><strong>'.T_('Pages').'</strong>: <ul class="pagination">'
								.'$prev$$first$$list_prev$$list$$list_next$$last$$next$'
							.'</ul></div>',
						'header_text_single' => '',
					'header_end' => '',
					'head_title' => '<div class="title">$title$<span class="floatright">$global_icons$</span></div>'."\n",
					'filters_start' => '<div class="filters">',
					'filters_end' => '</div>',
					'messages_start' => '<div class="messages">',
					'messages_end' => '</div>',
					'messages_separator' => '<br />',
					'list_start' => '<div class="table_scroll">'."\n"
					               .'<table class="forums_table highlight" cellspacing="0" cellpadding="0">'."\n",
						'head_start' => "<thead>\n",
							'line_start_head' => '<tr>',
							'colhead_start' => '<th $class_attrib$>',
							'colhead_start_first' => '<th class="$class$">',
							'colhead_start_last' => '<th class="$class$">',
							'colhead_end' => "</th>\n",
							'sort_asc_off' => get_icon( 'sort_asc_off' ),
							'sort_asc_on' => get_icon( 'sort_asc_on' ),
							'sort_desc_off' => get_icon( 'sort_desc_off' ),
							'sort_desc_on' => get_icon( 'sort_desc_on' ),
							'basic_sort_off' => '',
							'basic_sort_asc' => get_icon( 'ascending' ),
							'basic_sort_desc' => get_icon( 'descending' ),
						'head_end' => "</thead>\n\n",
						'tfoot_start' => "<tfoot>\n",
						'tfoot_end' => "</tfoot>\n\n",
						'body_start' => "<tbody>\n",
							'line_start' => '<tr class="even">'."\n",
							'line_start_odd' => '<tr class="odd">'."\n",
							'line_start_last' => '<tr class="even lastline">'."\n",
							'line_start_odd_last' => '<tr class="odd lastline">'."\n",
								'col_start' => '<td $class_attrib$>',
								'col_start_first' => '<td class="firstcol $class$">',
								'col_start_last' => '<td class="lastcol $class$">',
								'col_end' => "</td>\n",
							'line_end' => "</tr>\n\n",
							'grp_line_start' => '<tr class="group">'."\n",
							'grp_line_start_odd' => '<tr class="odd">'."\n",
							'grp_line_start_last' => '<tr class="lastline">'."\n",
							'grp_line_start_odd_last' => '<tr class="odd lastline">'."\n",
										'grp_col_start' => '<td $class_attrib$ $colspan_attrib$>',
										'grp_col_start_first' => '<td class="firstcol $class$" $colspan_attrib$>',
										'grp_col_start_last' => '<td class="lastcol $class$" $colspan_attrib$>',
								'grp_col_end' => "</td>\n",
							'grp_line_end' => "</tr>\n\n",
						'body_end' => "</tbody>\n\n",
						'total_line_start' => '<tr class="total">'."\n",
							'total_col_start' => '<td $class_attrib$>',
							'total_col_start_first' => '<td class="firstcol $class$">',
							'total_col_start_last' => '<td class="lastcol $class$">',
							'total_col_end' => "</td>\n",
						'total_line_end' => "</tr>\n\n",
					'list_end' => "</table></div>\n\n",
					'footer_start' => '',
					'footer_text' => '<div class="center"><strong>'.T_('Pages').'</strong>: <ul class="pagination">'
							.'$prev$$first$$list_prev$$list$$list_next$$last$$next$'
						.'</ul></div><div class="center">$page_size$</div>'
					                  /* T_('Page $scroll_list$ out of $total_pages$   $prev$ | $next$<br />'. */
					                  /* '<strong>$total_pages$ Pages</strong> : $prev$ $list$ $next$' */
					                  /* .' <br />$first$  $list_prev$  $list$  $list_next$  $last$ :: $prev$ | $next$') */,
					'footer_text_single' => '<div class="center">$page_size$</div>',
					'footer_text_no_limit' => '', // Text if theres no LIMIT and therefor only one page anyway
						'page_current_template' => '<span><b>$page_num$</b></span>',
						'page_item_before' => '<li>',
						'page_item_after' => '</li>',
						'prev_text' => T_('Previous'),
						'next_text' => T_('Next'),
						'no_prev_text' => '',
						'no_next_text' => '',
						'list_prev_text' => T_('...'),
						'list_next_text' => T_('...'),
						'list_span' => 11,
						'scroll_list_range' => 5,
					'footer_end' => "\n\n",
					'no_results_start' => '<div class="table_scroll"><table class="forums_table highlight" cellspacing="0" cellpadding="0"><tr><td>'."\n",
					'no_results_end'   => '$no_results$</td></tr></table></div>'."\n\n",
					'content_end' => '</div>',
					'after' => '</div>',
					'sort_type' => 'basic'
				);
				break;

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

			case 'cat_array_mode':
				// What category level use to display the items on disp=posts:
				//   - 'children' - Get items from current category and from all its sub-categories recirsively
				//   - 'parent' - Get items ONLY from current category WITHOUT sub-categories
				return 'parent';

			default:
				// Delegate to parent class:
				return parent::get_template( $name );
		}
	}
}

?>