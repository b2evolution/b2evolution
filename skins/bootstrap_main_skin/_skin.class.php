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
	var $version = '6.8.3';

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
		return 'normal';
	}


	/**
	 * What evoSkins API does has this skin been designed with?
	 *
	 * This determines where we get the fallback templates from (skins_fallback_v*)
	 * (allows to use new markup in new b2evolution versions)
	 */
	function get_api_version()
	{
		return 6;
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
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
				'section_layout_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Layout Settings')
				),
					'max_image_height' => array(
						'label' => T_('Max image height'),
						'note' => 'px. ' . T_('Set maximum height for post images.'),
						'defaultvalue' => '',
						'type' => 'integer',
						'size' => '7',
						'allow_empty' => true,
					),
				'section_layout_end' => array(
					'layout' => 'end_fieldset',
				),

				'1_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Image section')
				),
					'front_bg_image' => array(
						'label' => T_('Background image'),
						'note' => T_('Set background image in Main Area section.'),
						'defaultvalue' => 'shared/global/sunset/sunset.jpg',
						'type' => 'text',
						'size' => '50'
					),
				'1_end' => array(
					'layout' => 'end_fieldset',
				),
				'2_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Front Page Main Area Overlay')
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
					'front_bg_color' => array(
						'label' => T_('Background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#000000',
						'type' => 'color',
					),
					'front_bg_opacity' => array(
						'label' => T_('Background opacity'),
						'note' => '%. ' . T_('Adjust the background transparency level.'),
						'size' => '7',
						'maxlength' => '3',
						'defaultvalue' => '10',
						'type' => 'integer',
						'valid_range' => array(
							'min' => 0, // from 0%
							'max' => 100, // to 100%
						),
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
					'label'  => T_('Front Page Secondary Area Overlay')
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

		// Limit images by max height:
		$max_image_height = intval( $this->get_setting( 'max_image_height' ) );
		if( $max_image_height > 0 )
		{
			add_css_headline( '.evo_image_block img { max-height: '.$max_image_height.'px; width: auto; }' );
		}

		if( in_array( $disp, array( 'front', 'login', 'register', 'lostpassword', 'activateinfo', 'access_denied', 'access_requires_login' ) ) )
		{
			global $media_url, $media_path;

			// Add custom CSS:
			$custom_css = '';

			$bg_image = $this->get_setting( 'front_bg_image' );
			if( ! empty( $bg_image ) && file_exists( $media_path.$bg_image ) )
			{ // Custom body background image:
				$custom_css .= '#bg_picture { background-image: url('.$media_url.$bg_image.") }\n";
			}

			if( $color = $this->get_setting( 'pict_title_color' ) )
			{ // Custom title color:
				$custom_css .= 'body.pictured .main_page_wrapper .widget_core_coll_title h1 a { color: '.$color." }\n";
			}

			if( $color = $this->get_setting( 'pict_muted_color' ) )
			{ // Custom muted text color:
				$custom_css .= 'body.pictured .main_page_wrapper .text-muted { color: '.$color." }\n";
			}

			if( $color = $this->get_setting( 'front_bg_color' ) )
			{ // Custom body background color:
				$color_transparency = floatval( $this->get_setting( 'front_bg_opacity' ) / 100 );
				$color = substr( $color, 1 );
				if( strlen( $color ) == '6' )
				{ // Color value in format #FFFFFF
					$color = str_split( $color, 2 );
				}
				else
				{ // Color value in format #FFF
					$color = str_split( $color, 1 );
					foreach( $color as $c => $v )
					{
						$color[ $c ] = $v.$v;
					}
				}
				$custom_css .= '.front_main_content { background-color: rgba('.implode( ',', array_map( 'hexdec', $color ) ).','.$color_transparency.')'." }\n";
			}

			if( $color = $this->get_setting( 'front_text_color' ) )
			{ // Custom text color:
				$custom_css .= 'body.pictured .front_main_content, body.pictured .front_main_content h1 small, .evo_container__header, .evo_container__page_top { color: '.$color." }\n";
			}

			$link_color = $this->get_setting( 'front_link_color' );
			$icon_color = $this->get_setting( 'front_icon_color' );
			if( $link_color )
			{ // Custom link color:
				$custom_css .= 'body.pictured .main_page_wrapper .front_main_area a,
				body.pictured .main_page_wrapper .front_main_area div.evo_withteaser div.item_content > a { color: '.$link_color.' }
				body.pictured .main_page_wrapper .front_main_area div.widget_core_coll_item_list.evo_noexcerpt.evo_withteaser ul li div.item_content > a,
				body.pictured .main_page_wrapper .front_main_area div.widget_core_coll_post_list.evo_noexcerpt.evo_withteaser ul li div.item_content > a { color: '.$link_color." }\n";
			}
			if( $link_color && $icon_color )
			{ // Custom icon color:
				$custom_css .= 'body.pictured .front_main_content .ufld_icon_links a:not([class*="ufld__textcolor"]):not(:hover) { color: '.$icon_color." }\n";
				$custom_css .= 'body.pictured .front_main_content .ufld_icon_links a:not([class*="ufld__bgcolor"]):not(:hover) { background-color: '.$link_color." }\n";
				$custom_css .= 'body.pictured .front_main_content .ufld_icon_links a:hover:not([class*="ufld__hovertextcolor"]) { color: '.$link_color." }\n";
				$custom_css .= 'body.pictured .front_main_content .ufld_icon_links a:hover:not([class*="ufld__hoverbgcolor"]) { background-color: '.$icon_color." }\n";
			}

			if( $width = $this->get_setting( 'front_width' ) )
			{ // Custom width for front main area:
				$custom_css .= 'div.front_main_area { width: '.$width." }\n";
			}

			if( $position = $this->get_setting( 'front_position' ) )
			{ // Custom width for front main area:
				if( $position == 'middle' )
				{
					$custom_css .= 'div.front_main_area { float: none; margin-left: auto; margin-right: auto;'." }\n";
				}
				elseif( $position == 'right' )
				{
					$custom_css .= 'div.front_main_area { float: right;'." }\n";
				}
			}

			if( $color = $this->get_setting( 'secondary_text_color' ) )
			{ // Custom text color on secondary area:
				$custom_css .= 'section.secondary_area, .widget_core_org_members { color: '.$color." !important }\n";
			}

			if( ! empty( $custom_css ) )
			{
				if( $disp == 'front' )
				{ // Use standard bootstrap style on width <= 640px only for disp=front
					$custom_css = '@media only screen and (min-width: 641px)
						{
							'.$custom_css.'
						}';
				}
				$custom_css = '<style type="text/css">
	<!--
		'.$custom_css.'
	-->
	</style>';
				add_headline( $custom_css );
			}
		}

		if( $disp == 'front' )
		{ // Initialize script to scroll down to widget container with users team:
			add_js_headline( '
jQuery( document ).ready( function()
{
	jQuery( "#slide_button" ).click( function()
	{
		jQuery( "html, body, #skin_wrapper" ).animate(
		{
			scrollTop: jQuery( ".evo_container__front_page_secondary" ).offset().top
		}, 1500 );
	} );
} );' );
		}

	}


	/**
	 * Check if we can display a widget container
	 *
	 * @param string Widget container key: 'header', 'page_top', 'menu', 'sidebar', 'sidebar2', 'footer'
	 * @return boolean TRUE to display
	 */
	function is_visible_container( $container_key )
	{
		global $Collection, $Blog;

		if( $Blog->has_access() )
		{	// If current user has an access to this collection then don't restrict containers:
			return true;
		}

		// Get what containers are available for this skin when access is denied or requires login:
		$access = $this->get_setting( 'access_login_containers' );

		return ( ! empty( $access ) && ! empty( $access[ $container_key ] ) );
	}

}

?>