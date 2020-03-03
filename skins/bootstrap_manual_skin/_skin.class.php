<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage bootstrap_manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class bootstrap_manual_Skin extends Skin
{
	/**
	 * Skin version
	 * @var string
	 */
	var $version = '7.1.2';

	/**
	 * Do we want to use style.min.css instead of style.css ?
	 */
	var $use_min_css = 'true';  // true|false|'check' Set this to true for better optimization

	/**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Bootstrap Manual';
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
				'main' => 'no',
				'std' => 'no',		// Blog
				'photo' => 'no',
				'forum' => 'no',
				'manual' => 'yes',
				'group' => 'no',  // Tracker
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
	 * File parsing should only be used if this function is not defined
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
				'front_page_secondary_area' => NULL,
				'item_single_header'        => NULL,
				'chapter_main_area'         => array( NT_('Chapter Main Area'), 46 ),
				'sidebar_single'            => array( NT_('Sidebar Single'), 95 ),
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
					'page_navigation' => array(
						'label' => T_('Page navigation'),
						'note' => T_('(EXPERIMENTAL)').' '.T_('Check this to show previous/next page links to navigate inside the <b>current</b> chapter.'),
						'defaultvalue' => 0,
						'type' => 'checkbox',
					),
					'use_3_cols' => array(
						'label' => T_('Use 3 cols'),
						'type' => 'checklist',
						'options' => array(
							array( 'single',       sprintf( /* TRANS: position On disp=single or other disps */T_('On %s'), '<code>disp=single</code>' ), 1 ),
							array( 'posts-topcat', sprintf( /* TRANS: position On disp=single or other disps */T_('On %s'), '<code>disp=posts-topcat-intro</code>, <code>disp=posts-topcat-nointro</code>' ), 1 ),
							array( 'posts-subcat', sprintf( /* TRANS: position On disp=single or other disps */T_('On %s'), '<code>disp=posts-subcat-intro</code>, <code>disp=posts-subcat-nointro</code>' ), 1 ),
							array( 'front',        sprintf( /* TRANS: position On disp=single or other disps */T_('On %s'), '<code>disp=front</code>' ), 1 ),
							array( 'other',        T_('On other disps'), 0 ),
						),
					),
				'section_layout_end' => array(
					'layout' => 'end_fieldset',
				),

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
						'defaultvalue' => 0,
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
							array( 'sidebar',  sprintf( T_('"%s" container'), NT_('Sidebar') ),   0 ),
							array( 'sidebar2', sprintf( T_('"%s" container'), NT_('Sidebar 2') ), 0 ),
							array( 'footer',   sprintf( T_('"%s" container'), NT_('Footer') ),    1 ),
						) ),
				'section_access_end' => array(
					'layout' => 'end_fieldset',
				),

				'section_advanced_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Advanced')
				),
					'main_content_image_size' => array(
						'label' => T_('Image size for main content'),
						'note' => T_('Controls Aspect, Ratio and Standard Size'),
						'defaultvalue' => 'fit-1280x720',
						'options' => get_available_thumb_sizes(),
						'type' => 'select',
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
						'defaultvalue' => '100',
						'type' => 'integer',
						'allow_empty' => true,
					),
				'section_advanced_end' => array(
					'layout' => 'end_fieldset',
				),

			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Get ready for displaying the skin.
	 *
	 * This may register some CSS or JS...
	 */
	function display_init()
	{
		global $Messages, $disp, $debug;

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

		// Initialize a template depending on current page
		switch( $disp )
		{
			case 'front':
				// Init star rating for intro posts:
				init_ratings_js( 'blog', true );

				// Used to quick upload several files:
				init_fileuploader_js( 'blog' );
				break;

			case 'posts':
				global $cat, $tag, $bootstrap_manual_posts_text;

				// Init star rating for intro posts:
				init_ratings_js( 'blog', true );

				$bootstrap_manual_posts_text = T_('Posts');
				if( ! empty( $cat ) )
				{ // Init the <title> for categories page:
					$ChapterCache = & get_ChapterCache();
					if( $Chapter = & $ChapterCache->get_by_ID( $cat, false ) )
					{
						$bootstrap_manual_posts_text = $Chapter->get( 'name' );
					}
				}

				// Used to quick upload several files for comment of intro post:
				init_fileuploader_js( 'blog' );
				break;
		}

		if( $this->is_side_navigation_visible() )
		{ // Include JS code for left navigation panel only when it is displayed:
			$this->require_js_defer( 'affix_sidebars.js' );
		}

		// Init JS to affix Messages:
		init_affix_messages_js( $this->get_setting( 'message_affix_offset' ) );
	}


	/**
	 * Those templates are used for example by the messaging screens.
	 */
	function get_template( $name )
	{
		switch( $name )
		{
			case 'disp_params':
				// Params for skin_include( '$disp$', array( ) )
				return array(
					'author_link_text' => 'auto',
					// Profile tabs to switch between user edit forms
					'profile_tabs' => array(
						'block_start'         => '<nav><ul class="nav nav-tabs profile_tabs">',
						'item_start'          => '<li>',
						'item_end'            => '</li>',
						'item_selected_start' => '<li class="active">',
						'item_selected_end'   => '</li>',
						'block_end'           => '</ul></nav>',
					),
					// Pagination
					'pagination' => array(
						'block_start'           => '<div class="center"><ul class="pagination">',
						'block_end'             => '</ul></div>',
						'page_current_template' => '<span>$page_num$</span>',
						'page_item_before'      => '<li>',
						'page_item_after'       => '</li>',
						'page_item_current_before' => '<li class="active">',
						'page_item_current_after'  => '</li>',
						'prev_text'             => '<i class="fa fa-angle-double-left"></i>',
						'next_text'             => '<i class="fa fa-angle-double-right"></i>',
					),
					// Form params for the forms below: login, register, lostpassword, activateinfo and msgform
					'skin_form_before'      => '<div class="panel panel-default skin-form">'
																				.'<div class="panel-heading">'
																					.'<h3 class="panel-title">$form_title$</h3>'
																				.'</div>'
																				.'<div class="panel-body">',
					'skin_form_after'       => '</div></div>',
					// Login
					'display_form_messages' => true,
					'form_title_login'      => T_('Log in to your account').'$form_links$',
					'form_title_lostpass'   => get_request_title().'$form_links$',
					'lostpass_page_class'   => 'evo_panel__lostpass',
					'login_form_inskin'     => false,
					'login_page_class'      => 'evo_panel__login',
					'login_page_before'     => '<div class="$form_class$">',
					'login_page_after'      => '</div>',
					'display_reg_link'      => true,
					'abort_link_position'   => 'form_title',
					'abort_link_text'       => '<button type="button" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></button>',
					// Activate form
					'activate_form_title'  => T_('Account activation'),
					'activate_page_before' => '<div class="evo_panel__activation">',
					'activate_page_after'  => '</div>',
					// Search
					'search_input_before'      => '<div class="input-group">',
					'search_input_after'       => '',
					'search_submit_before'     => '<span class="input-group-btn">',
					'search_submit_after'      => '</span></div>',
					'search_use_editor'        => true,
					'search_author_format'     => 'login',
					'search_cell_author_start' => '<p class="small text-muted">',
					'search_cell_author_end'   => '</p>',
					'search_date_format'       => 'F jS, Y',
					// Front page
					'featured_intro_before' => '',
					'featured_intro_after'  => '',
					'intro_class'           => 'jumbotron',
					'featured_class'        => 'featurepost',
					// Form "Sending a message"
					'msgform_form_title' => T_('Contact'),
				);

			default:
				// Delegate to parent class:
				return parent::get_template( $name );
		}
	}


	/**
	 * Check if side(left and/or right) navigations are visible for current page
	 *
	 * @return boolean TRUE on visible
	 */
	function is_side_navigation_visible()
	{
		global $disp;

		if( in_array( $disp, array( 'access_requires_login', 'content_requires_login', 'access_denied' ) ) )
		{ // Display left navigation column on this page when at least one sidebar container is visible:
			return $this->show_container_when_access_denied( 'sidebar' ) || $this->show_container_when_access_denied( 'sidebar2' );
		}

		// Display left navigation column only on these pages:
		return in_array( $disp, array( 'front', 'posts', 'comments', 'flagged', 'mustread', 'single', 'search', 'edit', 'edit_comment', 'catdir', '404' ) );
	}


	/**
	 * Check if 3rd/right column layout can be used for current page
	 *
	 * @return boolean
	 */
	function is_3rd_right_column_layout()
	{
		global $disp, $disp_detail;

		if( ! $this->is_side_navigation_visible() )
		{	// Side navigation is hidden for current page:
			return false;
		}

		// Check when we should use layout with 3 columns:
		if( $disp == 'front' )
		{	// Front page
			return (boolean)$this->get_checklist_setting( 'use_3_cols', 'front' );
		}

		if( $disp == 'single' )
		{	// Single post/item page:
			return ( $this->get_checklist_setting( 'use_3_cols', 'single' )
				// old setting should be supported:
				|| $this->get_setting( 'single_3_cols' ) );
		}

		if( $disp_detail == 'posts-topcat-nointro' || $disp_detail == 'posts-topcat-intro' )
		{	// Category page with or without intro:
			return (boolean)$this->get_checklist_setting( 'use_3_cols', 'posts-topcat' );
		}

		if( $disp_detail == 'posts-subcat-nointro' || $disp_detail == 'posts-subcat-intro' )
		{	// Sub-category page with or without intro:
			return (boolean)$this->get_checklist_setting( 'use_3_cols', 'posts-subcat' );
		}

		// All other disps:
		return (boolean)$this->get_checklist_setting( 'use_3_cols', 'other' );
	}


	/**
	 * Get layout style class depending on skin settings and current disp
	 *
	 * @param string Place where class is used
	 */
	function get_layout_class( $place )
	{
		$r = '';

		switch( $place )
		{
			case 'container':
				$r .= 'container';
				if( $this->is_3rd_right_column_layout() )
				{	// Layout with 3 columns on current page:
					$r .= ' container-xxl';
				}
				break;

			case 'main_column':
				if( $this->is_side_navigation_visible() )
				{	// Layout with visible left sidebar:
					if( $this->is_3rd_right_column_layout() )
					{	// Layout with 3 columns on current page:
						$r .= 'col-xxl-8 col-xxl-pull-2 ';
					}
					$r .= 'col-md-9 pull-right-md';
				}
				else
				{
					$r .= 'col-md-12';
				}
				break;

			case 'left_column':
				if( $this->is_3rd_right_column_layout() )
				{	// Layout with 3 columns on current page:
					$r .= 'col-xxl-2 ';
				}
				$r .= 'col-md-3 col-xs-12 pull-left-md';
				break;

			case 'right_column':
				if( $this->is_3rd_right_column_layout() )
				{	// Layout with 3 columns on current page:
					$r .= 'col-xxl-2 col-xxl-push-8 ';
				}
				$r .= 'col-md-3 col-xs-12 pull-right-md';
				break;
		}

		return $r;
	}
}
?>