<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage bootstrap_forums
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class bootstrap_forums_Skin extends Skin
{
	/**
	 * Skin version
	 * @var string
	 */
	var $version = '7.1.0';

	/**
	 * Do we want to use style.min.css instead of style.css ?
	 */
	var $use_min_css = true;  // true|false|'check' Set this to true for better optimization

	/**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Bootstrap Forums';
	}


	/**
	 * Get default type for the skin.
	 */
	function get_default_type()
	{
		return 'rwd';
	}


	/**
	 * What evoSkins API does has this skin been designed with?
	 *
	 * This determines where we get the fallback templates from (skins_fallback_v*)
	 * (allows to use new markup in new b2evolution versions)
	 */
	function get_api_version()
	{
		return 7;
	}


	/**
	 * Get supported collection kinds.
	 *
	 * This should be overloaded in skins.
	 *
	 * For each kind the answer could be:
	 * - 'yes' : this skin does support that collection kind (the result will be was is expected)
	 * - 'partial' : this skin is not a primary choice for this collection kind (but still produces an output that makes sense)
	 * - 'maybe' : this skin has not been tested with this collection kind
	 * - 'no' : this skin does not support that collection kind (the result would not be what is expected)
	 * There may be more possible answers in the future...
	 */
	public function get_supported_coll_kinds()
	{
		$supported_kinds = array(
				'main' => 'maybe',
				'std' => 'no',		// Blog
				'photo' => 'no',
				'forum' => 'yes',
				'manual' => 'no',
				'group' => 'partial',  // Tracker
				// Any kind that is not listed should be considered as "maybe" supported
			);

		return $supported_kinds;
	}


	/*
	 * What CSS framework does has this skin been designed with?
	 *
	 * This may impact default markup returned by Skin::get_template() for example
	 */
	function get_css_framework()
	{
		return 'bootstrap';
	}


	/**
	 * Get the container codes of the skin main containers
	 *
	 * This should NOT be protected. It should be used INSTEAD of file parsing.
	 * File parsing should only be used if this function is not defined (which will be the case for older v6- skins)
	 *
	 * @return array Array which overrides default containers; Empty array means to use all default containers.
	 */
	function get_declared_containers()
	{
		// Array to override default containers from function get_skin_default_containers():
		// - Key is widget container code;
		// - Value: array( 0 - container name, 1 - container order ),
		//          NULL - means don't use the container, WARNING: it(only empty/without widgets) will be deleted from DB on changing of collection skin or on reload container definitions.
		return array(
				'front_page_main_area'       => NULL,
				'front_page_secondary_area'  => NULL,
				'forum_front_secondary_area' => array( NT_('Forum Front Secondary Area'), 47 ),
				'item_list'                  => NULL,
				'item_in_list'               => NULL,
				'sidebar_single'             => array( NT_('Sidebar Single'), 95 ),
			);
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		// Load for function get_available_thumb_sizes():
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array_merge( array(
				'section_layout_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Layout Settings')
				),
					'layout_general' => array(
						'label' => T_('General Layout'),
						'note' => T_('Select global skin layout.'),
						'defaultvalue' => 'no_sidebar',
						'options' => array(
								'no_sidebar'    => T_('No Sidebar'),
								'left_sidebar'  => T_('Left Sidebar'),
								'right_sidebar' => T_('Right Sidebar'),
							),
						'type' => 'select',
					),
					'layout_single' => array(
						'label' => T_('Single Thread Layout'),
						'note' => T_('Select skin layout for single threads') . ' (disp=single).',
						'defaultvalue' => 'no_sidebar',
						'options' => array(
								'no_sidebar'    => T_('No Sidebar'),
								'left_sidebar'  => T_('Left Sidebar'),
								'right_sidebar' => T_('Right Sidebar'),
							),
						'type' => 'select',
					),
					'main_content_image_size' => array(
						'label' => T_('Image size for main content'),
						'note' => T_('Controls Aspect, Ratio and Standard Size'),
						'defaultvalue' => 'fit-1280x720',
						'options' => get_available_thumb_sizes(),
						'type' => 'select',
					),
					'sidebar_single_affix' => array(
						'label' => T_('Sidebar Single'),
						'note'  => T_('Use affix to keep visible when scrolling down.'),
						'type'  => 'checkbox',
						'defaultvalue' => 1,
					),
					'max_image_height' => array(
						'label' => T_('Max image height'),
						'input_suffix' => ' px ',
						'note' => T_('Constrain height of content images by CSS.'),
						'defaultvalue' => '',
						'type' => 'integer',
						'size' => '7',
						'allow_empty' => true,
					),
					'message_affix_offset' => array(
						'label' => T_('Messages affix offset'),
						'note' => 'px. ' . T_('Set message top offset value.'),
						'defaultvalue' => '',
						'type' => 'integer',
						'allow_empty' => true,
					),
				'section_layout_end' => array(
					'layout' => 'end_fieldset',
				),

				'section_forum_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Forum Display Settings')
				),
					'banner_public' => array(
						'label' => T_('Display "Public" banner'),
						'note' => T_('Display banner for "Public" posts (posts & comments)'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'workflow_display_mode' => array(
						'label' => T_('Workflow column'),
						'type' => 'radio',
						'field_lines' => true,
						'options'  => array(
							array( 'status_and_author', T_('Display Status & Item Author') ),
							array( 'assignee_and_status', T_('Display Assignee (with Priority color coding) & Status') ),
						),
						'defaultvalue' => 'status_and_author',
					),
					'voting_place' => array(
						'label' => T_('Voting'),
						'type' => 'radio',
						'field_lines' => true,
						'options' => array(
							array( 'under_content', T_('Under posts/comments') ),
							array( 'left_score', T_('Show score on the left of each post/comment') ),
						),
						'defaultvalue' => 'under_content',
					),
				'section_forum_end' => array(
					'layout' => 'end_fieldset',
				),

/*
				'section_page_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Page Styles')
				),
					'page_text_size' => array(
						'label' => T_('Page text size'),
						'note' => T_('Default value is 14 pixels.'),
						'defaultvalue' => '14px',
						'size' => '4px',
						'type' => 'text',
					),
					'page_text_color' => array(
						'label' => T_('Page text color'),
						'defaultvalue' => '#333',
						'type' => 'color',
					),
					'page_link_color' => array(
						'label' => T_('Page link color'),
						'defaultvalue' => '#337ab7',
						'type' => 'color',
					),
					'current_tab_text_color' => array(
						'label' => T_('Current tab text color'),
						'defaultvalue' => '#333',
						'type' => 'color',
					),
					'page_bg_color' => array(
						'label' => T_('Page background color'),
						'defaultvalue' => '#fff',
						'type' => 'color',
						'transparency' => true,
					),
				'section_page_end' => array(
					'layout' => 'end_fieldset',
				),
*/

				'section_colorbox_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Colorbox Image Zoom')
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
				'section_colorbox_end' => array(
					'layout' => 'end_fieldset',
				),


				// WARNING: default value for bubbletips is specific!
				'section_username_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Username options')
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
						'defaultvalue' => 1,		// On the forums skin, we want to enable this!
						'type' => 'checkbox',
					),
					'autocomplete_usernames' => array(
						'label' => T_('Autocomplete usernames'),
						'note' => T_('Check to enable auto-completion of usernames entered after a "@" sign in the comment forms'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
				'section_username_end' => array(
					'layout' => 'end_fieldset',
				),


				'section_access_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('When access is denied or requires login...')
				),
					'access_login_containers' => array(
						'label' => T_('Display on login screen'),
						'note' => '',
						'type' => 'checklist',
						'options' => array(
							array( 'header',   sprintf( T_('"%s" container'), NT_('Header') ),    1 ),
							array( 'page_top', sprintf( T_('"%s" container'), NT_('Page Top') ),  1 ),
							array( 'menu',     sprintf( T_('"%s" container'), NT_('Menu') ),      0 ),
							array( 'footer',   sprintf( T_('"%s" container'), NT_('Footer') ),    1 ) ),
					),
				'section_access_end' => array(
					'layout' => 'end_fieldset',
				),

			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Set a skin specific param value for current Blog or Site
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 */
	function set_setting( $parname, $parvalue )
	{
		global $Collection, $Blog;

		// Set skin setting
		parent::set_setting( $parname, $parvalue );

		if( isset( $Blog ) &&
		    $parname == 'voting_place' && 
		    $parvalue == 'left_score' )
		{	// Turn on positive and negative voting for collection when score voting mode is enabled for this Skin:
			$Blog->set_setting( 'voting_positive', 1 );
			$Blog->set_setting( 'voting_negative', 1 );
		}
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
		global $disp, $Messages, $debug;

		// Request some common features that the parent function (Skin::display_init()) knows how to provide:
		parent::display_init( array(
				'jquery',                  // Load jQuery
				'font_awesome',            // Load Font Awesome (and use its icons as a priority over the Bootstrap glyphicons)
				'bootstrap',               // Load Bootstrap (without 'bootstrap_theme_css')
				'bootstrap_evo_css',       // Load the b2evo_base styles for Bootstrap (instead of the old b2evo_base styles)
				'bootstrap_messages',      // Initialize $Messages Class to use Bootstrap styles
				'style_css',               // Load the style.css file of the current skin
				'colorbox',                // Load Colorbox (a lightweight Lightbox alternative + customizations for b2evo)
				'bootstrap_init_tooltips', // Inline JS to init Bootstrap tooltips (E.g. on comment form for allowed file extensions)
				'disp_auto',               // Automatically include additional CSS and/or JS required by certain disps (replace with 'disp_off' to disable this)
			) );

		// Skin specific initializations:

		// **** Layout Settings / START ****
		// Max image height:
		$this->dynamic_style_rule( 'max_image_height', '.evo_image_block img { max-height: $setting_value$px; width: auto; }', array(
			'check' => 'not_empty'
		) );
		// **** Layout Settings / END ****

		// Add dynamic CSS rules headline:
		$this->add_dynamic_css_headline();

		if( in_array( $disp, array( 'single', 'page', 'comments' ) ) )
		{ // Load jquery UI to animate background color on change comment status or on vote
			require_js( '#jqueryUI#', 'blog' );
		}

		if( in_array( $disp, array( 'single', 'page' ) ) )
		{	// Init JS to autcomplete the user logins:
			require_js( '#bootstrap_typeahead#', 'blog' );
			init_autocomplete_login_js( 'blog', 'typeahead' );
			// Initialize date picker for _item_expert.form.php:
			init_datepicker_js( 'blog' );
		}

		if( $this->get_setting( 'voting_place' ) == 'left_score' )
		{	// Initialize JS for voting for score mode on the left of each post/comment:
			if( in_array( $disp, array( 'posts', 'flagged' ) ) )
			{	// Used to vote on an item:
				init_voting_item_js( 'blog' );
			}
			if( $disp == 'comments' )
			{	// Used to vote on the comments:
				init_voting_comment_js( 'blog' );
			}
		}

		// Do not affix sidebar for screen width below and lesser:
		add_css_headline( '@media (max-width: 992px ) { .sidebar_wrapper.affix { position: static } }' );

		// Init JS to affix Messages:
		init_affix_messages_js( $this->get_setting( 'message_affix_offset' ) );
	}


	/**
	 * Display button to create a new post
	 *
	 * @param integer Chapter ID
	 * @param object Item
	 * @param array Additional params
	 */
	function display_post_button( $chapter_ID, $Item = NULL, $params = array() )
	{
		echo $this->get_post_button( $chapter_ID, $Item, $params );
	}


	/**
	 * Get HTML code of button to create a new post
	 *
	 * @param integer Chapter ID
	 * @param object Item
	 * @return string
	 */
	function get_post_button( $chapter_ID, $Item = NULL, $params = array() )
	{
		global $Collection, $Blog;

		$params = array_merge( array(
				'group_class'  => '',
				'button_class' => '',
			), $params );

		$post_button = '';

		$chapter_is_locked = false;
		$default_new_ItemType = $Blog->get_default_new_ItemType();

		if( $default_new_ItemType === false )
		{ // Do not show button on disabled default item type for new items:
			return '';
		}

		$write_new_post_url = $Blog->get_write_item_url( $chapter_ID );
		if( $write_new_post_url != '' )
		{ // Display button to write a new post
			if( empty( $default_new_ItemType ) )
			{	// Use default button text:
				$button_text = T_('New topic');
			}
			else
			{	// Use button text from Item Type:
				$button_text = $default_new_ItemType->get_item_denomination( 'inskin_new_btn' );
			}

			$post_button = '<a href="'.$write_new_post_url.'" class="btn btn-primary '.$params['button_class'].'" title="'.T_('Post a new topic').'"><i class="fa fa-pencil"></i> '.$button_text.'</a>';
		}
		else
		{ // If a creating of new post is unavailable
			$ChapterCache = & get_ChapterCache();
			$current_Chapter = $ChapterCache->get_by_ID( $chapter_ID, false, false );

			if( $current_Chapter && $current_Chapter->lock )
			{ // Display icon to inform that this forum is locked
				$post_button = '<span title="'.T_('This forum is locked: you cannot post, reply to, or edit topics.').'"><i class="icon fa fa-lock"></i> '.T_('Locked').'</span>';
				$chapter_is_locked = true;
			}
		}

		if( !empty( $Item ) )
		{
			if( $Item->comment_status == 'closed' || $Item->comment_status == 'disabled' || $Item->is_locked() )
			{ // Display icon to inform that this topic is locked for comments
				if( !$chapter_is_locked )
				{ // Display this button only when chapter is not locked, to avoid a duplicate button
					$post_button .= ' <span title="'.T_('This topic is locked: you cannot edit posts or make replies.').'"><i class="icon fa fa-lock"></i> '.T_('Locked').'</span>';
				}
			}
			else
			{ // Display button to post a reply
				$post_button .= ' <a href="'.$Item->get_feedback_url().'#form_p'.$Item->ID.'" class="btn btn-default '.$params['button_class'].'" title="'.T_('Reply to topic').'"><i class="fa fa-reply"></i> './* TRANS: verb */ T_('Reply').'</a>';
			}
		}

		if( !empty( $post_button ) )
		{ // Display button
			return '<div class="post_button btn-group '.$params['group_class'].'">'.$post_button.'</div>';
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
	 * Check if we can display a sidebar for the current layout
	 *
	 * @param string Layout: 'general' or 'single'
	 * @param boolean TRUE to check if at least one sidebar container is visible
	 * @return boolean TRUE to display a sidebar
	 */
	function is_visible_sidebar( $layout = 'general', $check_containers = false )
	{
		$layout = $this->get_setting_layout( $layout );

		if( $layout != 'left_sidebar' && $layout != 'right_sidebar' )
		{ // Sidebar is not displayed for selected skin layout
			return false;
		}

		if( $check_containers )
		{ // Check if at least one sidebar container is visible
			return ( $this->show_container_when_access_denied( 'sidebar' ) ||  $this->show_container_when_access_denied( 'sidebar2' ) );
		}
		else
		{ // We should not check the visibility of the sidebar containers for this case
			return true;
		}
	}


	/**
	 * Get value for attbiute "class" of column block
	 * depending on skin setting "Layout"
	 *
	 * @param string Layout: 'general' or 'single'
	 * @return string
	 */
	function get_column_class( $layout = 'general' )
	{
		switch( $this->get_setting_layout( $layout ) )
		{
			case 'left_sidebar':
				// Left Sidebar
				return 'col-md-9 pull-right-md';

			case 'right_sidebar':
				// Right Sidebar
				return 'col-md-9';

			case 'no_sidebar':
				// No Sidebar (Single large column)
			default:
				return 'col-md-12';
		}
	}


	/**
	 * Get a layout setting value depending on $disp
	 *
	 * @param string Layout: 'general' or 'single'
	 * @return string
	 */
	function get_setting_layout( $layout = 'general' )
	{
		global $disp;

		if( $disp == 'single' )
		{	// Single post page has a separate setting for layout:
			if( $layout == 'single' )
			{
				return $this->get_setting( 'layout_single' );
			}
		}
		elseif( $layout == 'general' )
		{	// Use this settings for all other pages:
			return $this->get_setting( 'layout_general' );
		}

		// Hide sidebar by default:
		return 'no_sidebar';
	}


	/**
	 * Display a button to view the Recent/New Topics
	 */
	function display_button_recent_topics()
	{
		global $Collection, $Blog;

		// Get a number of unread posts by current User:
		$unread_posts_count = $Blog->get_unread_posts_count();

		if( $unread_posts_count > 0 )
		{	// If at least one new unread topic exists
			$btn_class = 'btn-warning';
			$btn_title = T_('New Topics').' <span class="badge">'.$unread_posts_count.'</span>';
		}
		else
		{	// Current user already have read all topics
			$btn_class = 'btn-info';
			$btn_title = T_('Recent Topics');
		}

		// Print out the button:
		echo '<a href="'.$Blog->get( 'recentpostsurl' ).'" class="btn '.$btn_class.' pull-right btn_recent_topics">'.$btn_title.'</a>';
	}


	/**
	 * Those templates are used for example by the messaging screens.
	 */
	function get_template( $name )
	{
		switch( $name )
		{
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


	/**
	 * Display a panel with voting buttons for item
	 *
	 * @param object Item
	 * @param string Place where panel is displayed: 'under_content', 'left_score'
	 * @param array Params
	 */
	function display_item_voting_panel( $Item, $place, $params = array() )
	{
		if( $place != $this->get_setting( 'voting_place' ) )
		{	// Skip because different place for panel is requested:
			return;
		}

		switch( $place )
		{
			case 'under_content':
				// Show under posts/comments:
				skin_widget( array_merge( array(
						// CODE for the widget:
						'widget'      => 'item_vote',
						// Optional display params
						'Item'        => $Item,
						'block_start' => '',
						'block_end'   => '',
						'skin_ID'     => $this->ID,
					), $params ) );
				break;

			case 'left_score':
				// Show score on the left of each post/comment:
				global $disp;
				skin_widget( array_merge( array(
						// CODE for the widget:
						'widget'                 => 'item_vote',
						// Optional display params
						'Item'                   => $Item,
						'block_start'            => '',
						'block_end'              => '',
						'skin_ID'                => $this->ID,
						'class'                  => 'evo_voting_panel__left_score',
						'title_text'             => '',
						'title_empty'            => '',
						'display_summary'        => 'no',
						'display_noopinion'      => false,
						'display_score'          => true,
						'score_class'            => ( in_array( $disp, array( 'posts', 'flagged' ) ) ? 'vote_score__status_'.$Item->get_read_status() : '' ),
						'icon_like_active'       => 'thumb_arrow_up',
						'icon_like_noactive'     => 'thumb_arrow_up_disabled',
						'icon_dontlike_active'   => 'thumb_arrow_down',
						'icon_dontlike_noactive' => 'thumb_arrow_down_disabled',
					), $params ) );
				break;
			}
	}


	/**
	 * Display a panel with voting buttons for item
	 *
	 * @param object Comment
	 * @param string Place where panel is displayed: 'under_content', 'left_score'
	 * @param array Params
	 */
	function display_comment_voting_panel( $Comment, $place, $params = array() )
	{
		if( $place != $this->get_setting( 'voting_place' ) )
		{	// Skip because different place for panel is requested:
			return;
		}

		switch( $place )
		{
			case 'under_content':
				// Show under posts/comments:
				$Comment->vote_helpful( '', '', '&amp;', true, true, array_merge( array(
						'before_title' => '',
						'helpful_text' => T_('Is this reply helpful?'),
						'skin_ID'      => $this->ID,
					), $params ) );
				break;

			case 'left_score':
				// Show score on the left of each post/comment:
				$Comment->vote_helpful( '', '', '&amp;', true, true, array_merge( array(
						'before_title'           => '',
						'helpful_text'           => T_('Is this reply helpful?'),
						'class'                  => '',
						'skin_ID'                => $this->ID,
						'class'                  => 'evo_voting_panel__left_score',
						'display_noopinion'      => false,
						'display_score'          => true,
						'title_text'             => '',
						'title_empty'            => '',
						'icon_like_active'       => 'thumb_arrow_up',
						'icon_like_noactive'     => 'thumb_arrow_up_disabled',
						'icon_dontlike_active'   => 'thumb_arrow_down',
						'icon_dontlike_noactive' => 'thumb_arrow_down_disabled',
					), $params ) );
				break;
		}
	}


	/**
	 * Display header for posts list
	 *
	 * @param string Title
	 */
	function display_posts_list_header( $title, $params = array() )
	{
		global $Blog, $current_User;

		$params = array_merge( array(
				'actions' => '',
				// Normal template:
				'before_normal_header'  => '<header class="panel-heading">',
				'after_normal_header'   => '<div class="clearfix"></header>',
				'before_normal_title'   => '<div class="pull-left">',
				'after_normal_title'    => '</div>',
				'before_normal_status'  => '<div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">',
				'after_normal_status'   => '</div>',
				'before_normal_actions' => '',
				'after_normal_actions'  => '',
				// Template with workflow task status selector:
				'before_workflow_header'  => '<header class="panel-heading panel-heading-columns">',
				'after_workflow_header'   => '<div class="clearfix"></header>',
				'before_workflow_title'   => '<div class="col-lg-8 col-md-8 col-sm-6 col-xs-12">',
				'after_workflow_title'    => '</div>',
				'before_workflow_status'  => '<div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">',
				'after_workflow_status'   => '</div>',
				'before_workflow_actions' => '<div class="col-lg-2 col-md-4 col-sm-6 col-xs-12 text-right">',
				'after_workflow_actions'  => '</div>',
			), $params );

		// Check if current User can view workflow properties:
		$can_view_workflow =
			// User must be logged in:
			is_logged_in() &&
			// Workflow must be enabled for current Collection:
			$Blog->get_setting( 'use_workflow' ) &&
			// Current User must has a permission to be assigned for tasks of the current Collection:
			$current_User->check_perm( 'blog_can_be_assignee', 'edit', false, $Blog->ID );

		// Get template depending on permission of current User:
		$template = ( $can_view_workflow ? 'workflow' : 'normal' );

		echo $params['before_'.$template.'_header'];

		// Title:
		echo $params['before_'.$template.'_title'];
		echo $title;
		echo $params['after_'.$template.'_title'];

		if( $can_view_workflow )
		{	// Display status filter only when current User a permission to view workflow properties:
			$ItemStatusCache = & get_ItemStatusCache();
			$ItemStatusCache->clear();
			$item_statuses_SQL = $ItemStatusCache->get_SQL_object();
			$item_statuses_SQL->FROM_add( 'INNER JOIN T_items__status_type ON pst_ID = its_pst_ID' );
			$item_statuses_SQL->FROM_add( 'INNER JOIN T_items__type_coll ON its_ityp_ID = itc_ityp_ID' );
			$item_statuses_SQL->WHERE( 'itc_coll_ID = '.$Blog->ID );
			$ItemStatusCache->load_by_sql( $item_statuses_SQL );
			$status = param( 'status', '/^(-|-[0-9]+|[0-9]+)(,[0-9]+)*$/', '' );

			echo $params['before_workflow_status'];
			echo '<select id="evo_workflow_status_filter" class="form-control input-sm">'
					.'<option value="">'.T_('All statuses').'</option>'
					.'<option value="-"'.( $status == '-' ? ' selected="selected"' : '' ).'>'.T_('No status').'</option>'
					.$ItemStatusCache->get_option_list( $status )
				.'</select>';
				// JavaScript to reload page with new selected task status:
				echo '<script>
				jQuery( "#evo_workflow_status_filter" ).change( function()
				{
					var url = location.href.replace( /([\?&])((status|redir)=[^&]*(&|$))+/, "$1" );
					var status_ID = jQuery( this ).val();
					if( status_ID !== "" )
					{
						url += ( url.indexOf( "?" ) == -1 ? "?" : "&" ) + "status=" + status_ID + "&redir=no";
					}
					location.href = url.replace( "?&", "?" ).replace( /\?$/, "" );
				} );
				</script>';
			echo $params['after_workflow_status'];
		}

		if( ! empty( $params['actions'] ) )
		{	// Actions:
			echo $params['before_'.$template.'_actions'];
			echo $params['actions'];
			echo $params['after_'.$template.'_actions'];
		}

		echo $params['after_'.$template.'_header'];
	}
}

?>