<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage bootstrap_photoblog_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class bootstrap_photoblog_Skin extends Skin
{
	/**
	 * Skin version
	 * @var string
	 */
	var $version = '7.1.2';

	/**
	 * Do we want to use style.min.css instead of style.css ?
	 */
	var $use_min_css = 'check';  // true|false|'check' Set this to true for better optimization
	// Note: we leave this on "check" in the bootstrap_blog_skin so it's easier for beginners to just delete the .min.css file
	// But for best performance, you should set it to true.

	/**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Bootstrap Photoblog Skin';
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
				'std' => 'yes',		// Blog
				'photo' => 'maybe',
				'forum' => 'no',
				'manual' => 'no',
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
				'item_list'                 => NULL,
				'item_in_list'              => NULL,
				'item_single_header'        => NULL,
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
				// Layout settings
				'section_layout_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Layout Settings')
				),
					'layout' => array(
						'label' => T_('Layout'),
						'note' => T_('Select skin layout.'),
						'defaultvalue' => 'single_column',
						'options' => array(
								'single_column'              => T_('Single Column Large'),
								'single_column_normal'       => T_('Single Column'),
								'single_column_narrow'       => T_('Single Column Narrow'),
								'single_column_extra_narrow' => T_('Single Column Extra Narrow'),
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
					'max_image_height' => array(
						'label' => T_('Max image height'),
						'input_suffix' => ' px ',
						'note' => T_('Constrain height of content images by CSS.'),
						'defaultvalue' => '',
						'type' => 'integer',
						'allow_empty' => true,
					),
					'message_affix_offset' => array(
						'label' => T_('Messages affix offset'),
						'note' => 'px. ' . T_('Set message top offset value.'),
						'defaultvalue' => '',
						'type' => 'integer',
						'allow_empty' => true,
					),
					'font_size' => array(
						'label' => T_('Font size'),
						'note' => T_('Select content font size.'),
						'defaultvalue' => 'default',
						'options' => array(
								'default'        => T_('Default (14px)'),
								'standard'       => T_('Standard (16px)'),
								'medium'      	 => T_('Medium (18px)'),
								'large' 		 => T_('Large (20px)'),
								'very_large'     => T_('Very large (22px)'),
							),
						'type' => 'select',
					),
				'section_layout_end' => array(
					'layout' => 'end_fieldset',
				),


				// Page color settings
				'page_color_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Page Color Settings')
				),
					'background_color' => array(
						'label' => T_('Background color'),
						'defaultvalue' => '#fff',
						'type' => 'color',
					),
					'page_text_color' => array(
						'label' => T_('Text color'),
						'defaultvalue' => '#333',
						'type' => 'color',
					),
					'page_link_color' => array(
						'label' => T_('Link color'),
						'defaultvalue' => '#337ab7',
						'type' => 'color',
					),
					'page_link_h_color' => array(
						'label' => T_('Link hover color'),
						'defaultvalue' => '#23527c',
						'type' => 'color',
					),
					'well_color' => array(
						'label' => T_('Background color'),
						'defaultvalue' => '#f5f5f5',
						'type' => 'color',
					),
				'page_color_end' => array(
					'layout' => 'end_fieldset',
				),


				// Navigation settings
				'navigation_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Navigation Settings')
				),
					'active_color' => array(
						'label' => T_('Active navigation link color'),
						'defaultvalue' => '#555',
						'type' => 'color',
					),
					'default_color' => array(
						'label' => T_('Default navigation links color'),
						'defaultvalue' => '#337ab7',
						'type' => 'color',
					),
					'default_h_color' => array(
						'label' => T_('Default navigation links hover color'),
						'defaultvalue' => '#23527c',
						'type' => 'color',
					),
					'default_bgh_color' => array(
						'label' => T_('Default navigation links hover background-color'),
						'defaultvalue' => '#eee',
						'type' => 'color',
					),
				'navigation_end' => array(
					'layout' => 'end_fieldset',
				),


				// Colorbox settings
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
							array( 'footer',   sprintf( T_('"%s" container'), NT_('Footer') ),    1 ) ),
						),
				'section_access_end' => array(
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
		global $Messages, $debug;

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
		// Default font - Size:
		$this->dynamic_style_rule( 'font_size', '$setting_value$', array(
			'options' => array(
				'default' => '',
				'standard' =>
					'.container p, .container ul li, .container div, .container label, .container textarea, .container input { font-size: 16px !important }'.
					'.container input.search_field { height: 100% }'.
					'.container h1 { font-size: 38px }'.
					'.container h2 { font-size: 32px }'.
					'.container h3 { font-size: 26px }'.
					'.container h4 { font-size: 18px }'.
					'.container h5 { font-size: 16px }'.
					'.container h6 { font-size: 14px }'.
					'.container .small { font-size: 85% !important }',
				'medium' =>
					'.container p, .container ul li, .container div, .container label, .container textarea, .container input { font-size: 18px !important }'.
					'.container input.search_field { height: 100% }'.
					'.container h1 { font-size: 40px }'.
					'.container h2 { font-size: 34px }'.
					'.container h3 { font-size: 28px }'.
					'.container h4 { font-size: 20px }'.
					'.container h5 { font-size: 18px }'.
					'.container h6 { font-size: 16px }'.
					'.container .small { font-size: 85% !important }',
				'large' =>
					'.container p, .container ul li, .container div, .container label, .container textarea, .container input { font-size: 20px !important }'.
					'.container input.search_field { height: 100% }'.
					'.container h1 { font-size: 42px }'.
					'.container h2 { font-size: 36px }'.
					'.container h3 { font-size: 30px }'.
					'.container h4 { font-size: 22px }'.
					'.container h5 { font-size: 20px }'.
					'.container h6 { font-size: 18px }'.
					'.container .small { font-size: 85% !important }',
				'very_large' =>
					'.container p, .container ul li, .container div, .container label, .container textarea, .container input { font-size: 22px !important }'.
					'.container input.search_field { height: 100% }'.
					'.container h1 { font-size: 44px }'.
					'.container h2 { font-size: 38px }'.
					'.container h3 { font-size: 32px }'.
					'.container h4 { font-size: 24px }'.
					'.container h5 { font-size: 22px }'.
					'.container h6 { font-size: 20px }'.
					'.container .small { font-size: 85% !important }',
			)
		) );
		// **** Layout Settings / END ****

		// **** Page Color Settings / START ****
		// Background color:
		$this->dynamic_style_rule( 'background_color', '#skin_wrapper, .nav li.active a { background-color: $setting_value$ }' );
		// Text color:
		$this->dynamic_style_rule( 'page_text_color', '#skin_wrapper { color: $setting_value$ }' );
		// Link color:
		$this->dynamic_style_rule( 'page_link_color',
			'a, .evo_comment_title a, .panel-title .evo_comment_type { color: $setting_value$ }'.
			// Pagination links:
			'.pagination > li > a, .pagination > li > span { color: $setting_value$ }'.
			'.pagination > .active > a, .pagination > .active > span, .pagination > .active > a:hover, .pagination > .active > span:hover, .pagination > .active > a:focus, .pagination > .active > span:focus { background-color: $setting_value$; border-color: $setting_value$ }'
		);
		// Link hover color:
		$this->dynamic_style_rule( 'page_link_h_color',
			'a:hover, .panel-title .evo_comment_type:hover { color: $setting_value$ }'.
			// Pagination links:
			'.pagination > li > a:hover, .pagination > li > span:hover, .pagination > li > a:focus, .pagination > li > span:focus { color:  $setting_value$ }'
		);
		// Posts background color:
		$this->dynamic_style_rule( 'well_color', '.well { background-color: $setting_value$ }' );
		// **** Page Color Settings / END ****

		// **** Navigation Settings / START ****
		// Active navigation link color:
		$this->dynamic_style_rule( 'active_color', 'ul.nav li.active a.selected { color: $setting_value$ }' );
		// Default navigation links color:
		$this->dynamic_style_rule( 'default_color', 'ul.nav li a.default { color: $setting_value$ }' );
		// Default navigation links hover color:
		$this->dynamic_style_rule( 'default_h_color', 'ul.nav li a.default:hover { color: $setting_value$ }' );
		// Default navigation links hover background-color:
		$this->dynamic_style_rule( 'default_bgh_color', 'ul.nav li a.default:hover { background-color: $setting_value$ }' );
		// **** Navigation Settings / END ****

		// Add dynamic CSS rules headline:
		$this->add_dynamic_css_headline();

		// Init JS to affix Messages:
		init_affix_messages_js( $this->get_setting( 'message_affix_offset' ) );
	}


	/**
	 * Get value for attbiute "class" of column block
	 * depending on skin setting "Layout"
	 *
	 * @return string
	 */
	function get_column_class()
	{
		switch( $this->get_setting( 'layout' ) )
		{
			case 'single_column':
				// Single Column Large
				return 'col-md-12';

			case 'single_column_normal':
				// Single Column
				return 'col-xs-12 col-sm-12 col-md-12 col-lg-10 col-lg-offset-1';

			case 'single_column_narrow':
				// Single Column Narrow
				return 'col-xs-12 col-sm-12 col-md-10 col-md-offset-1 col-lg-8 col-lg-offset-2';

			case 'single_column_extra_narrow':
				// Single Column Extra Narrow
				return 'col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2 col-lg-6 col-lg-offset-3';
		}
	}
}

?>