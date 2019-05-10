<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage bootstrap_main
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class bootstrap_main_Skin extends Skin
{
	/**
	 * Skin version
	 * @var string
	 */
	var $version = '7.0.1';

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
		return 'Bootstrap Main';
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
				'minisite' => 'maybe',  // Not necessarily a good choice because there is no menu container on the homepage, minisites typically need a menu to switch pages
				'main' => 'yes',
				'std' => 'no',		// Blog
				'photo' => 'no',
				'forum' => 'no',
				'manual' => 'no',
				'group' => 'maybe',  // Tracker
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
		return array();
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
						'defaultvalue' => '',
						'type' => 'integer',
						'allow_empty' => true,
					),
				'section_layout_end' => array(
					'layout' => 'end_fieldset',
				),

				'1_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Image section')
				),
					'front_bg_image_file_ID' => array(
						'label' => T_('Background image'),
						'type' => 'fileselect',
						'initialize_with' => 'shared/global/sunset/sunset.jpg',
						'thumbnail_size' => 'fit-320x320'
					),
					'front_bg_color' => array(
						'label' => T_('Background color'),
						'note' => T_('This color will be used if Background image is not set or does not exist.'),
						'defaultvalue' => '#333333',
						'type' => 'color',
						'transparency' => true,
					),
				'1_end' => array(
					'layout' => 'end_fieldset',
				),
				'2_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Front Page Main Area Settings')
				),
					'front_width' => array(
						'label' => T_('Width'),
						'note' => T_('Adjust width of the Main Area container.'),
						'size' => '7',
						'defaultvalue' => '450px',
					),
					'front_position' => array(
						'label' => T_('Position'),
						'note' => T_('Select the position of Main Area container.'),
						'defaultvalue' => 'left',
						'options' => array(
								'left'   => T_('Left'),
								'middle' => T_('Middle'),
								'right'  => T_('Right'),
							),
						'type' => 'select',
					),
					'front_bg_cont_color' => array(
						'label' => T_('Background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => 'rgba(0,0,0,0.1)',
						'type' => 'color',
						'transparency' => true,
					),
					'pict_title_color' => array(
						'label' => T_('Title color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#F0F0F0',
						'type' => 'color',
					),
					'front_text_color' => array(
						'label' => T_('Text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'front_link_color' => array(
						'label' => T_('Link color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'pict_muted_color' => array(
						'label' => T_('Muted text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#F0F0F0',
						'type' => 'color',
					),
					'front_icon_color' => array(
						'label' => T_('Inverse icon color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#CCCCCC',
						'type' => 'color',
					),
				'2_end' => array(
					'layout' => 'end_fieldset',
				),
				'3_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Front Page Secondary Area Settings')
				),
					'secondary_bg_color' => array(
						'label' => T_('Background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#fff',
						'type' => 'color',
						'transparency' => true,
					),
					'secondary_text_color' => array(
						'label' => T_('Text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#333',
						'type' => 'color',
					),
				'3_end' => array(
					'layout' => 'end_fieldset',
				),
				'4_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Featured posts Settings')
				),
					'bgimg_text_color' => array(
						'label' => T_('Text color on background image'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#fff',
						'type' => 'color',
					),
					'bgimg_link_color' => array(
						'label' => T_('Link color on background image'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#6cb2ef',
						'type' => 'color',
					),
					'bgimg_hover_link_color' => array(
						'label' => T_('Hover link color on background image'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#6cb2ef',
						'type' => 'color',
					),
				'4_end' => array(
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
							array( 'header',   sprintf( T_('"%s" container'), NT_('Header') ),   1 ),
							array( 'page_top', sprintf( T_('"%s" container'), NT_('Page Top') ), 1 ),
							array( 'menu',     sprintf( T_('"%s" container'), NT_('Menu') ),     0 ),
							array( 'footer',   sprintf( T_('"%s" container'), NT_('Footer') ),   1 )
							),
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
		global $Messages, $disp, $debug, $Session, $blog;

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
		$this->dynamic_style_rule( 'max_image_height', '.evo_image_block img { max-height: $setting_value$; width: auto; }', array(
			'suffix' => 'px'
		) );
		// **** Layout Settings / END ****

		if( in_array( $disp, array( 'front', 'login', 'register', 'lostpassword', 'activateinfo', 'access_denied', 'access_requires_login', 'content_requires_login' ) ) )
		{
			// **** Image section / START ****
			// Background image:
			$this->dynamic_style_rule( 'front_bg_image_file_ID', '.evo_pictured_layout { background-image: $setting_value$ }', array(
				'type' => 'image_file',
			) );
			// Background color:
			$this->dynamic_style_rule( 'front_bg_color', '.evo_pictured_layout { background-color: $setting_value$ }' );
			// **** Image section / END ****

			// **** Front Page Main Area Settings / START ****
			// Width:
			$this->dynamic_style_rule( 'front_width', 'div.front_main_area { width: $setting_value$ }' );

			// Title color:
			$this->dynamic_style_rule( 'pict_title_color', 'body.pictured .main_page_wrapper .front_main_area .widget_core_coll_title h2 a { color: $setting_value$ }' );

			// Muted text color:
			$this->dynamic_style_rule( 'pict_muted_color', 'body.pictured .main_page_wrapper .text-muted { color: $setting_value$ }' );

			// Background color:
			$this->dynamic_style_rule( 'front_bg_cont_color', '.front_main_content { background-color: $setting_value$ }' );

			// Text color:
			$this->dynamic_style_rule( 'front_text_color',
				'body.pictured .front_main_content, '.
				'body.pictured .front_main_content h1 small, '.
				'.evo_container__header, '.
				'.evo_container__page_top, '.
				'body.pictured.disp_access_requires_login .evo_widget.widget_core_content_block, '.
				'body.pictured.disp_access_denied .evo_widget.widget_core_content_block '.
				'{ color: $setting_value$ }'
			);

			// Link color:
			$this->dynamic_style_rule( 'front_link_color',
				'body.pictured .main_page_wrapper .front_main_area a:not(.btn),'.
				'body.pictured .main_page_wrapper .front_main_area div.evo_withteaser div.item_content > a { color: $setting_value$ }'.
				'body.pictured .main_page_wrapper .front_main_area div.widget_core_coll_item_list.evo_noexcerpt.evo_withteaser ul li div.item_content > a,'.
				'body.pictured .main_page_wrapper .front_main_area div.widget_core_coll_post_list.evo_noexcerpt.evo_withteaser ul li div.item_content > a { color: $setting_value$'." }\n".
				'body.pictured .front_main_content .ufld_icon_links a:not([class*="ufld__bgcolor"]):not(:hover) { background-color: $setting_value$'." }\n".
				'body.pictured .front_main_content .ufld_icon_links a:hover:not([class*="ufld__hovertextcolor"]) { color: $setting_value$ }'
			);

			// Inverse icon color:
			$this->dynamic_style_rule( 'front_icon_color',
				'body.pictured .front_main_content .ufld_icon_links a:not([class*="ufld__textcolor"]):not(:hover) { color: $setting_value$'." }\n".
				'body.pictured .front_main_content .ufld_icon_links a:hover:not([class*="ufld__hoverbgcolor"]) { background-color: $setting_value$ }'
			);

			// Position:
			$this->dynamic_style_rule( 'front_position', 'div.front_main_area { $setting_value$ }', array(
				'options' => array(
					'left'   => '',// default value
					'middle' => 'float: none; margin-left: auto; margin-right: auto;',
					'right'  => 'float: right;',
				)
			) );
			// **** Front Page Main Area Settings / END ****

			// **** Front Page Secondary Area Settings / START ****
			// Background color:
			$this->dynamic_style_rule( 'secondary_bg_color', 'section.secondary_area { background-color: $setting_value$ }' );
			// Text color:
			$this->dynamic_style_rule( 'secondary_text_color', 'section.secondary_area, .widget_core_org_members { color: $setting_value$ !important }' );
			// **** Front Page Secondary Area Settings / END ****
		}

		// **** Featured posts Settings / START ****
		// Text color on background image:
		$this->dynamic_style_rule( 'bgimg_text_color', '.evo_hasbgimg { color: $setting_value$ }' );
		// Link color on background image:
		$this->dynamic_style_rule( 'bgimg_link_color', '.evo_hasbgimg a { color: $setting_value$ }' );
		// Hover link color on background image:
		$this->dynamic_style_rule( 'bgimg_hover_link_color', '.evo_hasbgimg a:hover { color: $setting_value$ }' );
		// **** Featured posts Settings / END ****

		// Add dynamic CSS rules headline:
		// Use standard bootstrap style on width <= 640px only for disp=front:
		$media_exception = ( $disp == 'front' ? '@media only screen and (min-width: 641px)' : NULL );
		$this->add_dynamic_css_headline( $media_exception );

		// Init JS to affix Messages:
		init_affix_messages_js( $this->get_setting( 'message_affix_offset' ) );

		if( $Session->get( 'designer_mode_'.$blog ) )
		{	// On enabled designer mode we should set full window height for pictured layout in pixel instead of 100% percents to avoid issues on scroll page:
			add_js_headline( 'jQuery( document ).ready( function()
			{	// On enabled designer mode we should set full window height for pictured layout in pixel instead of 100% percents to avoid issues on scroll page:
				jQuery( ".evo_pictured_layout" ).height( jQuery( window ).height() );
				jQuery( window ).resize( function()
				{	// Update height on window resizing:
					jQuery( ".evo_pictured_layout" ).height( jQuery( window ).height() );
				} );
			} );' );
		}
	}
}
?>