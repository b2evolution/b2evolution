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
class jared_Skin extends Skin
{
	/**
	 * Skin version
	 * @var string
	 */
	var $version = '7.0.5';

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
		return 'Jared Skin';
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
				'minisite' => 'yes',
				'main' => 'yes',
				'std' => 'yes',		// Blog
				'photo' => 'no',
				'forum' => 'no',
				'manual' => 'no',
				'group' => 'no',  // Tracker
				// Any kind that is not listed should be considered as "maybe" supported
			);

		return $supported_kinds;
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
				'page_top'                  => array( NT_('Page Top'), 2 ),
				'header'                    => array( NT_('Header'), 10 ),
				'menu'                      => array( NT_('Menu'), 15 ),
				'front_page_main_area'      => array( NT_('Front Page Area 1'), 40 ),
				'front_page_secondary_area' => array( NT_('Front Page Area 2'), 41 ),
				'front_page_area_3'         => array( NT_('Front Page Area 3'), 42 ),
				'front_page_area_4'         => array( NT_('Front Page Area 4'), 43 ),
				'front_page_area_5'         => array( NT_('Front Page Area 5'), 44 ),
				'item_list'                 => array( NT_('Item List'), 48 ),
				'item_in_list'              => array( NT_('Item in List'), 49 ),
				'item_single_header'        => array( NT_('Item Single Header'), 50 ),
				'item_single'               => array( NT_('Item Single'), 51 ),
				'item_page'                 => array( NT_('Item Page'), 55 ),
				'contact_page_main_area'    => array( NT_('Contact Page Main Area'), 60 ),
				'sidebar'                   => NULL,
				'sidebar_2'                 => NULL,
				'footer'                    => array( NT_('Footer'), 100 ),
				'user_profile_left'         => array( NT_('User Profile - Left'), 110 ),
				'user_profile_right'        => array( NT_('User Profile - Right'), 120 ),
				'404_page'                  => array( NT_('404 Page'), 130 ),
				'login_required'            => array( NT_('Login Required'), 140 ),
				'access_denied'             => array( NT_('Access Denied'), 150 ),
				'help'                      => array( NT_('Help'), 160 ),
				'register'                  => array( NT_('Register'), 170 ),
				'compare_main_area'         => array( NT_('Compare Main Area'), 180 ),
			);
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
				'layout_section_start' => array(
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
					'message_affix_offset' => array(
						'label' => T_('Messages affix offset'),
						'note' => 'px. ' . T_('Set message top offset value.'),
						'defaultvalue' => '100',
						'type' => 'integer',
						'allow_empty' => true,
					),
				'layout_section_end' => array(
					'layout' => 'end_fieldset',
				),


				// ============ Navigation Section ============
				'navigation_section_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Top Navigation Bar Settings')
				),
					'nav_bg_transparent' => array(
						'label' => T_('Transparent background'),
						'note' => T_('Check this to enable transparent background until navigation breaks into hamburger layout.'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'nav_bg_color' => array(
						'label' => T_('Background color'),
						'note' => T_('This color will be used if Background image is not set or does not exist.'),
						'defaultvalue' => '#333333',
						'type' => 'color',
					),
					'nav_colltitle_size' => array(
						'label' => T_('Collection title font size'),
						'note' => 'px. ' . T_('Set font size for collection title in navigation.'),
						'defaultvalue' => '18',
						'type' => 'integer',
						'size' => '2',
						'allow_empty' => false,
					),
					'nav_links_size' => array(
						'label' => T_('Links font size'),
						'note' => 'px. ' . T_('Set font size for navigation links.'),
						'defaultvalue' => '13',
						'type' => 'integer',
						'size' => '2',
						'allow_empty' => false,
					),
					'nav_links_color' => array(
						'label' => T_('Links color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'nav_button_color' => array(
						'label' => T_('Button text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
				'navigation_section_end' => array(
					'layout' => 'end_fieldset',
				),


				// ============ Page Top Section ============
				'pagetop_section_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Page Top Settings')
				),
					'pagetop_button_bg_color' => array(
						'label' => T_('Button background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'pagetop_button_color' => array(
						'label' => T_('Button text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
				'pagetop_section_end' => array(
					'layout' => 'end_fieldset',
				),


				// ============ Section 1 - Front Page Main Area ============
				'section_1_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Section 1 - Front Page Main Area')
				),
					'section_1_display' => array(
						'label' => T_('Display this section'),
						'note' => T_('Check this to enable Front Page Main Area.'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'section_1_navbar_text_color' => array(
						'label' => T_('Top navigation text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_1_image_file_ID' => array(
						'label' => T_('Background image'),
						'type' => 'fileselect',
						'initialize_with' => 'shared/global/monument-valley/monuments.jpg',
						'thumbnail_size' => 'fit-320x320'
					),
					'section_1_bg_color' => array(
						'label' => T_('Background color'),
						'note' => T_('This color will be used if Background image is not set or does not exist.'),
						'defaultvalue' => '#333333',
						'type' => 'color',
					),
					'section_1_cont_width' => array(
						'label' => T_('Maximum content width'),
						'note' => 'px. ' . T_('Set the ammount of maximum width for the content in this section.' ) . ' <strong>' . T_( 'Maximum value is') . ' 1170px.</strong>',
						'defaultvalue' => '1170',
						'type' => 'integer',
						'size' => '2',
						'allow_empty' => false,
					),
					'section_1_coll_title_color' => array(
						'label' => T_('Collection title color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_1_title_color' => array(
						'label' => T_('Content title color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_1_text_color' => array(
						'label' => T_('Normal text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_1_link_color' => array(
						'label' => T_('Links color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_1_muted_color' => array(
						'label' => T_('Muted text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#F0F0F0',
						'type' => 'color',
					),
					'section_1_icon_color' => array(
						'label' => T_('Inverse icon color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#CCCCCC',
						'type' => 'color',
					),
					'section_1_button_bg_color' => array(
						'label' => T_('Button background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_1_button_color' => array(
						'label' => T_('Button text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_1_text_align' => array(
						'label'    => T_('Align text'),
						'note'     => '',
						'type'     => 'radio',
						'options'  => array(
							array( 'section_1_left', T_('Left') ),
							array( 'section_1_center', T_('Center') ),
							array( 'section_1_right', T_('Right') ),
						),
						'defaultvalue' => 'section_1_center',
					),
				'section_1_end' => array(
					'layout' => 'end_fieldset',
				),


				// ============ Section 2 - Front Page Secondary Area ============
				'section_2_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Section 2 - Front Page Secondary Area')
				),
					'section_2_display' => array(
						'label' => T_('Display this section'),
						'note' => T_('Check this to enable Front Page Secondary Area.'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'section_2_image_file_ID' => array(
						'label' => T_('Background image'),
						'type' => 'fileselect',
						'defaultvalue' => NULL,
						'thumbnail_size' => 'fit-320x320'
					),
					'section_2_bg_color' => array(
						'label' => T_('Background color'),
						'note' => T_('This color will be used if Background image is not set or does not exist.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_2_cont_width' => array(
						'label' => T_('Maximum content width'),
						'note' => 'px. ' . T_('Set the ammount of maximum width for the content in this section.' ) . ' <strong>' . T_( 'Maximum value is') . ' 1170px.</strong>',
						'defaultvalue' => '1170',
						'type' => 'integer',
						'size' => '2',
						'allow_empty' => false,
					),
					'section_2_title_color' => array(
						'label' => T_('Title color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#222222',
						'type' => 'color',
					),
					'section_2_text_color' => array(
						'label' => T_('Normal text color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#adadad',
						'type' => 'color',
					),
					'section_2_link_color' => array(
						'label' => T_('Links color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_2_link_h_color' => array(
						'label' => T_('Links hover color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_2_button_bg_color' => array(
						'label' => T_('Button background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_2_button_color' => array(
						'label' => T_('Button text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_2_text_align' => array(
						'label'    => T_('Align text'),
						'note'     => '',
						'type'     => 'radio',
						'options'  => array(
							array( 'section_2_left', T_('Left') ),
							array( 'section_2_center', T_('Center') ),
							array( 'section_2_right', T_('Right') ),
						),
						'defaultvalue' => 'section_2_center',
					),
				'section_2_end' => array(
					'layout' => 'end_fieldset',
				),


				// ============ Section 3 - Front Page Area 3 ============
				'section_3_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Section 3 - Front Page Area 3')
				),
					'section_3_display' => array(
						'label' => T_('Display this section'),
						'note' => T_('Check this to enable Front Page Area 3.'),
						'defaultvalue' => 0,
						'type' => 'checkbox',
					),
					'section_3_image_file_ID' => array(
						'label' => T_('Background image'),
						'type' => 'fileselect',
						'initialize_with' => 'shared/global/monument-valley/monument-valley-road.jpg',
						'thumbnail_size' => 'fit-320x320'
					),
					'section_3_bg_color' => array(
						'label' => T_('Background color'),
						'note' => T_('This color will be used if Background image is not set or does not exist.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_3_cont_width' => array(
						'label' => T_('Maximum content width'),
						'note' => 'px. ' . T_('Set the ammount of maximum width for the content in this section.' ) . ' <strong>' . T_( 'Maximum value is') . ' 1170px.</strong>',
						'defaultvalue' => '1170',
						'type' => 'integer',
						'size' => '2',
						'allow_empty' => false,
					),
					'section_3_title_color' => array(
						'label' => T_('Title color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#222222',
						'type' => 'color',
					),
					'section_3_text_color' => array(
						'label' => T_('Normal text color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#adadad',
						'type' => 'color',
					),
					'section_3_link_color' => array(
						'label' => T_('Links color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_3_link_h_color' => array(
						'label' => T_('Links hover color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_3_button_bg_color' => array(
						'label' => T_('Button background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_3_button_color' => array(
						'label' => T_('Button text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_3_text_align' => array(
						'label'    => T_('Align text'),
						'note'     => '',
						'type'     => 'radio',
						'options'  => array(
							array( 'section_3_left', T_('Left') ),
							array( 'section_3_center', T_('Center') ),
							array( 'section_3_right', T_('Right') ),
						),
						'defaultvalue' => 'section_3_center',
					),
				'section_3_end' => array(
					'layout' => 'end_fieldset',
				),


				// ============ Section 4 - Front Page Area 4 ============
				'section_4_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Section 4 - Front Page Area 4')
				),
					'section_4_display' => array(
						'label' => T_('Display this section'),
						'note' => T_('Check this to enable Front Page Area 4.'),
						'defaultvalue' => 0,
						'type' => 'checkbox',
					),
					'section_4_image_file_ID' => array(
						'label' => T_('Background image'),
						'type' => 'fileselect',
						'defaultvalue' => NULL,
						'initialize_with' => 'shared/global/sunset/sunset.jpg',
						'thumbnail_size' => 'fit-320x320'
					),
					'section_4_bg_color' => array(
						'label' => T_('Background color'),
						'note' => T_('This color will be used if Background image is not set or does not exist.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_4_cont_width' => array(
						'label' => T_('Maximum content width'),
						'note' => 'px. ' . T_('Set the ammount of maximum width for the content in this section.' ) . ' <strong>' . T_( 'Maximum value is') . ' 1170px.</strong>',
						'defaultvalue' => '1170',
						'type' => 'integer',
						'size' => '2',
						'allow_empty' => false,
					),
					'section_4_title_color' => array(
						'label' => T_('Title color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#222222',
						'type' => 'color',
					),
					'section_4_text_color' => array(
						'label' => T_('Normal text color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#adadad',
						'type' => 'color',
					),
					'section_4_link_color' => array(
						'label' => T_('Links color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_4_link_h_color' => array(
						'label' => T_('Links hover color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_4_button_bg_color' => array(
						'label' => T_('Button background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_4_button_color' => array(
						'label' => T_('Button text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_4_text_align' => array(
						'label'    => T_('Align text'),
						'note'     => '',
						'type'     => 'radio',
						'options'  => array(
							array( 'section_4_left', T_('Left') ),
							array( 'section_4_center', T_('Center') ),
							array( 'section_4_right', T_('Right') ),
						),
						'defaultvalue' => 'section_4_center',
					),
				'section_4_end' => array(
					'layout' => 'end_fieldset',
				),


				// ============ Section 5 - Front Page Area 5 ============
				'section_5_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Section 5 - Front Page Area 5')
				),
					'section_5_display' => array(
						'label' => T_('Display this section'),
						'note' => T_('Check this to enable Front Page Area 5.'),
						'defaultvalue' => 0,
						'type' => 'checkbox',
					),
					'section_5_image_file_ID' => array(
						'label' => T_('Background image'),
						'type' => 'fileselect',
						'initialize_with' => 'shared/global/monument-valley/bus-stop-ahead.jpg',
						'thumbnail_size' => 'fit-320x320'
					),
					'section_5_bg_color' => array(
						'label' => T_('Background color'),
						'note' => T_('This color will be used if Background image is not set or does not exist.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_5_cont_width' => array(
						'label' => T_('Maximum content width'),
						'note' => 'px. ' . T_('Set the ammount of maximum width for the content in this section.' ) . ' <strong>' . T_( 'Maximum value is') . ' 1170px.</strong>',
						'defaultvalue' => '1170',
						'type' => 'integer',
						'size' => '2',
						'allow_empty' => false,
					),
					'section_5_title_color' => array(
						'label' => T_('Title color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#222222',
						'type' => 'color',
					),
					'section_5_text_color' => array(
						'label' => T_('Normal text color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#adadad',
						'type' => 'color',
					),
					'section_5_link_color' => array(
						'label' => T_('Links color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_5_link_h_color' => array(
						'label' => T_('Links hover color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_5_button_bg_color' => array(
						'label' => T_('Button background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_5_button_color' => array(
						'label' => T_('Button text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_5_text_align' => array(
						'label'    => T_('Align text'),
						'note'     => '',
						'type'     => 'radio',
						'options'  => array(
							array( 'section_5_left', T_('Left') ),
							array( 'section_5_center', T_('Center') ),
							array( 'section_5_right', T_('Right') ),
						),
						'defaultvalue' => 'section_5_center',
					),
				'section_5_end' => array(
					'layout' => 'end_fieldset',
				),


				// ============ Section 6 - Header for Standalone Pages ============
				'section_6_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Section 6 - Header for Standalone Pages')
				),
					'section_6_navbar_text_color' => array(
						'label' => T_('Top navigation text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_6_image_file_ID' => array(
						'label' => T_('Fallback brackground image'),
						'note' => T_('This will be displayed if the page has no cover image.'),
						'type' => 'fileselect',
						'initialize_with' => 'shared/global/monument-valley/monument-valley.jpg',
						'thumbnail_size' => 'fit-320x320'
					),
					'section_6_bg_color' => array(
						'label' => T_('Background color'),
						'note' => T_('This color will be used if Background image is not set or does not exist.'),
						'defaultvalue' => '#333333',
						'type' => 'color',
					),
					'section_6_cont_width' => array(
						'label' => T_('Maximum content width'),
						'note' => 'px. ' . T_('Set the ammount of maximum width for the content in this section.' ) . ' <strong>' . T_( 'Maximum value is') . ' 1170px.</strong>',
						'defaultvalue' => '1170',
						'type' => 'integer',
						'size' => '2',
						'allow_empty' => false,
					),
					'section_6_title_color' => array(
						'label' => T_('Title color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_6_text_color' => array(
						'label' => T_('Normal text color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#adadad',
						'type' => 'color',
					),
					'section_6_link_color' => array(
						'label' => T_('Links color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_6_link_h_color' => array(
						'label' => T_('Links hover color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_6_button_bg_color' => array(
						'label' => T_('Button background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_6_button_color' => array(
						'label' => T_('Button text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_6_text_align' => array(
						'label'    => T_('Align text'),
						'note'     => '',
						'type'     => 'radio',
						'options'  => array(
							array( 'section_6_left', T_('Left') ),
							array( 'section_6_center', T_('Center') ),
							array( 'section_6_right', T_('Right') ),
						),
						'defaultvalue' => 'section_6_center',
					),
				'section_6_end' => array(
					'layout' => 'end_fieldset',
				),


				// ============ Section 7 - Header for Contact form and Messaging ============
				'section_7_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Section 7 - Header for Contact form and Messaging')
				),
					'section_7_navbar_text_color' => array(
						'label' => T_('Top navigation text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_7_image_file_ID' => array(
						'label' => T_('Background image'),
						'type' => 'fileselect',
						'initialize_with' => 'shared/global/monument-valley/john-ford-point.jpg',
						'thumbnail_size' => 'fit-320x320'
					),
					'section_7_bg_color' => array(
						'label' => T_('Background color'),
						'note' => T_('This color will be used if Background image is not set or does not exist.'),
						'defaultvalue' => '#333333',
						'type' => 'color',
					),
					'section_7_cont_width' => array(
						'label' => T_('Maximum content width'),
						'note' => 'px. ' . T_('Set the ammount of maximum width for the content in this section.' ) . ' <strong>' . T_( 'Maximum value is') . ' 1170px.</strong>',
						'defaultvalue' => '1170',
						'type' => 'integer',
						'size' => '2',
						'allow_empty' => false,
					),
					'section_7_title_color' => array(
						'label' => T_('Title color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_7_text_color' => array(
						'label' => T_('Normal text color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#adadad',
						'type' => 'color',
					),
					'section_7_link_color' => array(
						'label' => T_('Links color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_7_link_h_color' => array(
						'label' => T_('Links hover color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_7_button_bg_color' => array(
						'label' => T_('Button background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_7_button_color' => array(
						'label' => T_('Button text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_7_text_align' => array(
						'label'    => T_('Align text'),
						'note'     => '',
						'type'     => 'radio',
						'options'  => array(
							array( 'section_7_left', T_('Left') ),
							array( 'section_7_center', T_('Center') ),
							array( 'section_7_right', T_('Right') ),
						),
						'defaultvalue' => 'section_7_center',
					),
				'section_7_end' => array(
					'layout' => 'end_fieldset',
				),


				// ============ Section - Header for restricted access disps ============
				'section_pictured_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Section - Restricted access disps') . ' ( disp=login || disp=register || disp=lostpassword || disp=activateinfo || disp=access_denied || disp=access_requires_login )'
				),
					'section_access_navbar_text_color' => array(
						'label' => T_('Top navigation text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_access_image_file_ID' => array(
						'label' => T_('Background image'),
						'type' => 'fileselect',
						'initialize_with' => 'shared/global/monument-valley/john-ford-point.jpg',
						'thumbnail_size' => 'fit-320x320'
					),
					'section_access_bg_color' => array(
						'label' => T_('Background color'),
						'note' => T_('This color will be used if Background image is not set or does not exist.'),
						'defaultvalue' => '#333333',
						'type' => 'color',
					),
				'section_pictured_end' => array(
					'layout' => 'end_fieldset',
				),


				// ============ Section - Header for other disps ============
				'section_oth_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Section - Header for other disps')
				),
					'section_oth_navbar_text_color' => array(
						'label' => T_('Top navigation text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_oth_image_file_ID' => array(
						'label' => T_('Background image'),
						'type' => 'fileselect',
						'initialize_with' => 'shared/global/monument-valley/john-ford-point.jpg',
						'thumbnail_size' => 'fit-320x320'
					),
					'section_oth_bg_color' => array(
						'label' => T_('Background color'),
						'note' => T_('This color will be used if Background image is not set or does not exist.'),
						'defaultvalue' => '#333333',
						'type' => 'color',
					),
					'section_oth_cont_width' => array(
						'label' => T_('Maximum content width'),
						'note' => 'px. ' . T_('Set the ammount of maximum width for the content in this section.' ) . ' <strong>' . T_( 'Maximum value is') . ' 1170px.</strong>',
						'defaultvalue' => '1170',
						'type' => 'integer',
						'size' => '2',
						'allow_empty' => false,
					),
					'section_oth_title_color' => array(
						'label' => T_('Title color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_oth_text_color' => array(
						'label' => T_('Normal text color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#adadad',
						'type' => 'color',
					),
					'section_oth_link_color' => array(
						'label' => T_('Links color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_oth_link_h_color' => array(
						'label' => T_('Links hover color'),
						'note' => T_('Click to select a color'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_oth_button_bg_color' => array(
						'label' => T_('Button background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'section_oth_button_color' => array(
						'label' => T_('Button text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'section_oth_text_align' => array(
						'label'    => T_('Align text'),
						'note'     => '',
						'type'     => 'radio',
						'options'  => array(
							array( 'section_oth_left', T_('Left') ),
							array( 'section_oth_center', T_('Center') ),
							array( 'section_oth_right', T_('Right') ),
						),
						'defaultvalue' => 'section_oth_center',
					),
				'section_oth_end' => array(
					'layout' => 'end_fieldset',
				),


				// ============ Footer Section ============
				'footer_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Footer Settings')
				),
					'footer_bg_color' => array(
						'label' => T_('Background color'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#222222',
						'type' => 'color',
					),
					'footer_content_color' => array(
						'label' => T_('Normal text color'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#ffffff',
						'type' => 'color',
					),
					'footer_link_color' => array(
						'label' => T_('Links color'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#ffffff',
						'type' => 'color',
					),
					'footer_link_h_color' => array(
						'label' => T_('Links hover color'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#ffffff',
						'type' => 'color',
					),
					'footer_button_bg_color' => array(
						'label' => T_('Button background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#318780',
						'type' => 'color',
					),
					'footer_button_color' => array(
						'label' => T_('Button text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'footer_text_align' => array(
						'label'    => T_('Align text'),
						'note'     => '',
						'type'     => 'radio',
						'options'  => array(
							array( 'footer_left', T_('Left') ),
							array( 'footer_center', T_('Center') ),
							array( 'footer_right', T_('Right') ),
						),
						'defaultvalue' => 'footer_center',
					),
				'footer_end' => array(
					'layout' => 'end_fieldset',
				),


				// ============ Featured Posts Settings ============
				'featured_posts_start' => array(
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
				'featured_posts_end' => array(
					'layout' => 'end_fieldset',
				),


				// ============ Buttons color customization ============
				'buttons_section_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Button Customization Settings')
				),
					// Login button
					'login_button_color' => array(
						'label' => T_('Login button color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#ffffff',
						'type' => 'color',
					),
					'login_button_bg_color' => array(
						'label' => T_('Login button background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#5cb85c',
						'type' => 'color',
					),
					// Register button
					'register_button_color' => array(
						'label' => T_('Register button color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#ffffff',
						'type' => 'color',
					),
					'register_button_bg_color' => array(
						'label' => T_('Register button background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#337ab7',
						'type' => 'color',
					),
					// Contact button
					'contact_button_color' => array(
						'label' => T_('Contact and Subscribe button color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#ffffff',
						'type' => 'color',
					),
					'contact_button_bg_color' => array(
						'label' => T_('Contact and Subscribe button background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#337ab7',
						'type' => 'color',
					),
					// Save/Submit button
					'submit_button_color' => array(
						'label' => T_('Submit and Save button color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#ffffff',
						'type' => 'color',
					),
					'submit_button_bg_color' => array(
						'label' => T_('Submit and Save button background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#337ab7',
						'type' => 'color',
					),
				'buttons_section_end' => array(
					'layout' => 'end_fieldset',
				),


				// ============ Colorbox Image Settings ============
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


				// ============ Username Settings ============
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


				// ============ Special Disps Settings ============
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

		add_headline( '<link href="https://fonts.googleapis.com/css?family=Ek+Mukta:300|Josefin+Sans:300,400" rel="stylesheet">' );

		$max_image_height = intval( $this->get_setting( 'max_image_height' ) );
		if( $max_image_height > 0 )
		{
			add_css_headline( '.evo_image_block img { max-height: '.$max_image_height.'px; width: auto; }' );
		}

		// Add custom CSS:
		$custom_css = '';


		// ============ Navigation Section ============
		$nav_links_color = $this->get_setting( 'nav_links_color' );
		$nav_bg_color    = $this->get_setting( 'nav_bg_color' );

		if( $custom_font_size = $this->get_setting( 'nav_colltitle_size' ) )
		{
			$custom_css .= ".navbar.main-header-navigation .navbar-brand > h3 a { font-size:". $custom_font_size. "px }\n";
		}

		if( $custom_font_size = $this->get_setting( 'nav_links_size' ) )
		{
			$custom_css .= ".navbar.main-header-navigation.navbar-default .navbar-nav > .active > a, .navbar.main-header-navigation.navbar-default .navbar-nav > .active > a:focus, .navbar.main-header-navigation.navbar-default .navbar-nav > .active > a:hover, .navbar.main-header-navigation.navbar-default .navbar-nav li > a { font-size:". $custom_font_size. "px }\n";
		}

		// If "Transparent background" option for navigation is TRUE
		if( $this->get_setting( 'nav_bg_transparent' ) )
		{
			// Set background-color for all cases, but (!)
			$custom_css .= ".navbar, .navbar.affix { background-color: $nav_bg_color }\n";
			// ... exclude background-color in mentioned media queries and set transparent
			$custom_css .= "@media (min-width: 1025px) { .navbar { background-color: transparent } }\n";

			// Section 1 navigation links color
			if( in_array( $disp, array( 'front', 'login', 'register', 'lostpassword', 'activateinfo', 'access_denied', 'access_requires_login' ) ) )
			{
				$section_nav_color = $this->get_setting( 'section_1_navbar_text_color' );
				$custom_css .= "@media (min-width: 1025px) { .affix-top a { color: $section_nav_color !important } }\n";
				$custom_css .= "@media (max-width: 1024px) { .affix-top a { color: $nav_links_color !important } }\n";
			}
			// Section 6 navigation links color
			if( $disp == 'page' )
			{
				$section_nav_color = $this->get_setting( 'section_6_navbar_text_color' );
				$custom_css .= "@media (min-width: 1025px) { .affix-top a { color: $section_nav_color !important } }\n";
			}
			// Section 7 navigation links color
			if( $disp == 'msgform' || $disp == 'threads' )
			{
				$section_nav_color = $this->get_setting( 'section_7_navbar_text_color' );
				$custom_css .= "@media (min-width: 1025px) { .affix-top a { color: $section_nav_color !important } }\n";
			}
			// Section access navigation links color
			if( in_array( $disp, array( 'login', 'register', 'lostpassword', 'activateinfo', 'access_denied', 'access_requires_login' ) ) )
			{
				$section_nav_color = $this->get_setting( 'section_access_navbar_text_color' );
				$custom_css .= "@media (min-width: 1025px) { .affix-top a { color: $section_nav_color !important } }\n";
			}
			// Section 8 navigation links color
			if( ! in_array( $disp, array( 'front', 'login', 'register', 'lostpassword', 'activateinfo', 'access_denied', 'access_requires_login', 'msgform', 'threads', 'page' ) ) ) {
				$section_nav_color = $this->get_setting( 'section_oth_navbar_text_color' );
				$custom_css .= "@media (min-width: 1025px) { .affix-top a { color: $section_nav_color !important } }\n";
			}

			// Default navigation links color, applied to all conditions EXCEPT when '.affix-top'
			// $custom_css .= ".navbar.navbar-default:not(.affix-top) a, .navbar.navbar-default:not(.affix-top) a:hover, .navbar-default:not(.affix-top) .navbar-nav>.active>a, .navbar-default:not(.affix-top) .navbar-nav>.active>a:focus, .navbar-default:not(.affix-top) .navbar-nav>.active>a:hover, .navbar-default:not(.affix-top) .navbar-nav>.active>a, .navbar-default:not(.affix-top) .navbar-nav>li>a, .navbar-default:not(.affix-top) .navbar-nav>li>a:focus, .navbar-default:not(.affix-top) .navbar-nav>li>a:hover { color: $nav_links_color }\n";
			$custom_css .= ".navbar.navbar-default a, .navbar.navbar-default a:hover, .navbar-default .navbar-nav>.active>a, .navbar-default .navbar-nav>.active>a:focus, .navbar-default .navbar-nav>.active>a:hover, .navbar-default .navbar-nav>.active>a, .navbar-default .navbar-nav>li>a, .navbar-default .navbar-nav>li>a:focus, .navbar-default .navbar-nav>li>a:hover { color: $nav_links_color }\n";

		}

		// If "Transparent background" option for navigation is FALSE
		else
		{
			// Set background-color for all cases
			$custom_css .= ".navbar { background-color: $nav_bg_color }\n";
			// Set all navigation links color to what is set as default
			$custom_css .= ".navbar.navbar-default a, .navbar.navbar-default a:hover, .navbar-default .navbar-nav>.active>a, .navbar-default .navbar-nav>.active>a:focus, .navbar-default .navbar-nav>.active>a:hover, .navbar-default .navbar-nav>.active>a, .navbar-default .navbar-nav>li>a, .navbar-default .navbar-nav>li>a:focus, .navbar-default .navbar-nav>li>a:hover { color: $nav_links_color }\n";
		}


		// ============ Page Top Section ============
		if( $color = $this->get_setting( 'pagetop_button_bg_color' ) )
		{
			$custom_css .= '.evo_container__page_top .evo_widget > .btn.btn-default { background-color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'pagetop_button_color' ) )
		{
			$custom_css .= '.evo_container__page_top .evo_widget > .btn.btn-default { color: '.$color." }\n";
		}


		$FileCache = & get_FileCache();
		if( in_array( $disp, array( 'front', 'login', 'register', 'lostpassword', 'activateinfo', 'access_denied', 'access_requires_login' ) ) )
		{


			// ============ Section 1 - Front Page Main Area ============
			if( $this->get_setting( 'section_1_display' ) )
			{
			if( $this->get_setting( 'section_1_image_file_ID' ) )
			{
				$bg_image_File1 = & $FileCache->get_by_ID( $this->get_setting( 'section_1_image_file_ID' ), false, false );
			}
			if( !empty( $bg_image_File1 ) && $bg_image_File1->exists() )
			{
				$custom_css .= '.evo_container__front_first_section { background-image: url('.$bg_image_File1->get_url().") }\n";
			}
			else
			{
				$color = $this->get_setting( 'section_1_bg_color' );
				$custom_css .= '.evo_container__front_first_section { background: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_1_coll_title_color' ) )
			{
				$custom_css .= 'body.pictured.disp_front .main_page_wrapper .widget_core_coll_title h1 a { color: '.$color." }\n";
			}
			if( $max_width = $this->get_setting( 'section_1_cont_width' ) )
			{
				$custom_css .= 'body.pictured.disp_front .container.main_page_wrapper { max-width: '.$max_width."px }\n";
			}
			if( $color = $this->get_setting( 'section_1_title_color' ) )
			{
				$custom_css .= 'body.pictured.disp_front .main_page_wrapper h2.page-header { color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_1_muted_color' ) )
			{
				$custom_css .= 'body.pictured.disp_front .main_page_wrapper .text-muted { color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_1_text_color' ) )
			{
				$custom_css .= 'body.pictured.disp_front .front_main_content, body.pictured .front_main_content h1 small, .evo_container__header, .evo_container__page_top { color: '.$color." }\n";
			}

			$link_color 		= $this->get_setting( 'section_1_link_color' );
			// $link_hover_color   = $this->get_setting( 'section_1_link_h_color' );
			$icon_color			= $this->get_setting( 'section_1_icon_color' );
			if( $link_color )
			{
				$custom_css .= 'body.pictured .main_page_wrapper .front_main_area a,
				body.pictured .main_page_wrapper .front_main_area div.evo_withteaser div.item_content > a { color: '.$link_color.' }
				body.pictured .main_page_wrapper .front_main_area div.widget_core_coll_item_list.evo_noexcerpt.evo_withteaser ul li div.item_content > a,
				body.pictured .main_page_wrapper .front_main_area div.widget_core_coll_post_list.evo_noexcerpt.evo_withteaser ul li div.item_content > a, .evo_container__page_top a { color: '.$link_color." }\n";
			}
			if( $link_color && $icon_color )
			{
				$custom_css .= 'body.pictured .front_main_content .ufld_icon_links a:not([class*="ufld__textcolor"]):not(:hover) { color: '.$icon_color." }\n";
				$custom_css .= 'body.pictured .front_main_content .ufld_icon_links a:not([class*="ufld__bgcolor"]):not(:hover) { background-color: '.$link_color." }\n";
				$custom_css .= 'body.pictured .front_main_content .ufld_icon_links a:hover:not([class*="ufld__hovertextcolor"]) { color: '.$link_color." }\n";
				$custom_css .= 'body.pictured .front_main_content .ufld_icon_links a:hover:not([class*="ufld__hoverbgcolor"]) { background-color: '.$icon_color." }\n";
			}
			if( $color = $this->get_setting( 'section_1_button_bg_color' ) )
			{
				$custom_css .= '.evo_container__front_page_main_area .evo_widget > .btn.btn-default { background-color: '.$color." }\n";
				$custom_css .= '.evo_container__front_page_main_area .evo_widget .item_excerpt > a.btn.btn-default,
				.evo_container__front_page_main_area .evo_widget .item_content > a.btn.btn-default
				{ background-color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_1_button_color' ) )
			{
				$custom_css .= '.evo_container__front_page_main_area .evo_widget > .btn.btn-default { color: '.$color." }\n";
				$custom_css .= '.evo_container__front_page_main_area .evo_widget .item_excerpt > a.btn.btn-default,
				.evo_container__front_page_main_area .evo_widget .item_content > a.btn.btn-default
				{ color: '.$color." }\n";
			}
			if( $this->get_setting( 'section_1_text_align' ) == 'section_1_center' )
			{
				$custom_css .= ".evo_container__front_page_main_area { text-align: center }\n";
			}
			if( $this->get_setting( 'section_1_text_align' ) == 'section_1_right' )
			{
				$custom_css .= ".evo_container__front_page_main_area { text-align: right }\n";
			}
			}


			// ============ Section 2 - Front Page Secondary Area ============
			if( $this->get_setting( 'section_2_display' ) )
			{
			if( $this->get_setting( 'section_2_image_file_ID' ) )
			{
				$bg_image_File2 = & $FileCache->get_by_ID( $this->get_setting( 'section_2_image_file_ID' ), false, false );
			}
			if( !empty( $bg_image_File2 ) && $bg_image_File2->exists() )
			{
				$custom_css .= '.evo_container__front_page_secondary_area { background-image: url('.$bg_image_File2->get_url().") }\n";
			}
			else
			{
				$color = $this->get_setting( 'section_2_bg_color' );
				$custom_css .= '.evo_container__front_page_secondary_area { background: '.$color." }\n";
			}
			if( $max_width = $this->get_setting( 'section_2_cont_width' ) )
			{
				$custom_css .= '.evo_container__front_page_secondary_area > .container { max-width: '.$max_width."px }\n";
			}
			if( $color = $this->get_setting( 'section_2_title_color' ) )
			{
				$custom_css .= '.evo_container__front_page_secondary_area h2.page-header { color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_2_text_color' ) )
			{
				$custom_css .= '.evo_container__front_page_secondary_area { color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_2_link_color' ) )
			{
				$custom_css .= '.evo_container__front_page_secondary_area a { color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_2_link_h_color' ) )
			{
				$custom_css .= '.evo_container__front_page_secondary_area a:hover { color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_2_button_bg_color' ) )
			{
				$custom_css .= '.evo_container__front_page_secondary_area .evo_widget > .btn.btn-default { background-color: '.$color." }\n";
				$custom_css .= '.evo_container__front_page_secondary_area .evo_widget .item_excerpt > a.btn.btn-default,
				.evo_container__front_page_secondary_area .evo_widget .item_content > a.btn.btn-default
				{ background-color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_2_button_color' ) )
			{
				$custom_css .= '.evo_container__front_page_secondary_area .evo_widget > .btn.btn-default { color: '.$color." }\n";
				$custom_css .= '.evo_container__front_page_secondary_area .evo_widget .item_excerpt > a.btn.btn-default,
				.evo_container__front_page_secondary_area .evo_widget .item_content > a.btn.btn-default
				{ color: '.$color." }\n";
			}
			if( $this->get_setting( 'section_2_text_align' ) == 'section_2_center' )
			{
				$custom_css .= ".evo_container__front_page_secondary_area { text-align: center }\n";
			}
			if( $this->get_setting( 'section_2_text_align' ) == 'section_2_right' )
			{
				$custom_css .= ".evo_container__front_page_secondary_area { text-align: right }\n";
			}
			}


			// ============ Section 3 - Front Page Area 3 ============
			if( $this->get_setting( 'section_3_display' ) )
			{
			if( $this->get_setting( 'section_3_image_file_ID' ) )
			{
				$bg_image_File3 = & $FileCache->get_by_ID( $this->get_setting( 'section_3_image_file_ID' ), false, false );
			}
			if( !empty( $bg_image_File3 ) && $bg_image_File3->exists() )
			{
				$custom_css .= '.evo_container__front_page_area_3 { background-image: url('.$bg_image_File3->get_url().") }\n";
			}
			else
			{
				$color = $this->get_setting( 'section_3_bg_color' );
				$custom_css .= '.evo_container__front_page_area_3 { background: '.$color." }\n";
			}
			if( $max_width = $this->get_setting( 'section_3_cont_width' ) )
			{
				$custom_css .= '.evo_container__front_page_area_3 > .container { max-width: '.$max_width."px }\n";
			}
			if( $color = $this->get_setting( 'section_3_title_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_3 h2.page-header { color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_3_text_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_3 { color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_3_link_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_3 a { color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_3_link_h_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_3 a:hover { color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_3_button_bg_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_3 .evo_widget > .btn.btn-default { background-color: '.$color." }\n";
				$custom_css .= '.evo_container__front_page_area_3 .evo_widget .item_excerpt > a.btn.btn-default,
				.evo_container__front_page_area_3 .evo_widget .item_content > a.btn.btn-default
				{ background-color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_3_button_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_3 .evo_widget > .btn.btn-default { color: '.$color." }\n";
				$custom_css .= '.evo_container__front_page_area_3 .evo_widget .item_excerpt > a.btn.btn-default,
				.evo_container__front_page_area_3 .evo_widget .item_content > a.btn.btn-default
				{ color: '.$color." }\n";
			}
			if( $this->get_setting( 'section_3_text_align' ) == 'section_3_center' )
			{
				$custom_css .= ".evo_container__front_page_area_3 { text-align: center }\n";
			}
			if( $this->get_setting( 'section_3_text_align' ) == 'section_3_right' )
			{
				$custom_css .= ".evo_container__front_page_area_3 { text-align: right }\n";
			}
			}


			// ============ Section 4 - Front Page Area 4 ============
			if( $this->get_setting( 'section_4_display' ) )
			{
			if( $this->get_setting( 'section_4_image_file_ID' ) )
			{
				$bg_image_File4 = & $FileCache->get_by_ID( $this->get_setting( 'section_4_image_file_ID' ), false, false );
			}
			if( !empty( $bg_image_File4 ) && $bg_image_File4->exists() )
			{
				$custom_css .= '.evo_container__front_page_area_4 { background-image: url('.$bg_image_File4->get_url().") }\n";
			}
			else
			{
				$color = $this->get_setting( 'section_4_bg_color' );
				$custom_css .= '.evo_container__front_page_area_4 { background: '.$color." }\n";
			}
			if( $max_width = $this->get_setting( 'section_4_cont_width' ) )
			{
				$custom_css .= '.evo_container__front_page_area_4 > .container { max-width: '.$max_width."px }\n";
			}
			if( $color = $this->get_setting( 'section_4_title_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_4 h2.page-header { color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_4_text_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_4 { color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_4_link_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_4 a { color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_4_link_h_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_4 a:hover { color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_4_button_bg_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_4 .evo_widget > .btn.btn-default { background-color: '.$color." }\n";
				$custom_css .= '.evo_container__front_page_area_4 .evo_widget .item_excerpt > a.btn.btn-default,
				.evo_container__front_page_area_4 .evo_widget .item_content > a.btn.btn-default
				{ background-color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_4_button_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_4 .evo_widget > .btn.btn-default { color: '.$color." }\n";
				$custom_css .= '.evo_container__front_page_area_4 .evo_widget .item_excerpt > a.btn.btn-default,
				.evo_container__front_page_area_4 .evo_widget .item_content > a.btn.btn-default
				{ color: '.$color." }\n";
			}
			if( $this->get_setting( 'section_4_text_align' ) == 'section_4_center' )
			{
				$custom_css .= ".evo_container__front_page_area_4 { text-align: center }\n";
			}
			if( $this->get_setting( 'section_4_text_align' ) == 'section_4_right' )
			{
				$custom_css .= ".evo_container__front_page_area_4 { text-align: right }\n";
			}
			}


			// ============ Section 5 - Front Page Area 5 ============
			if( $this->get_setting( 'section_5_display' ) )
			{
			if( $this->get_setting( 'section_5_image_file_ID' ) )
			{
				$bg_image_File5 = & $FileCache->get_by_ID( $this->get_setting( 'section_5_image_file_ID' ), false, false );
			}
			if( !empty( $bg_image_File5 ) && $bg_image_File5->exists() )
			{
				$custom_css .= '.evo_container__front_page_area_5 { background-image: url('.$bg_image_File5->get_url().") }\n";
			}
			else
			{
				$color = $this->get_setting( 'section_5_bg_color' );
				$custom_css .= '.evo_container__front_page_area_5 { background: '.$color." }\n";
			}
			if( $max_width = $this->get_setting( 'section_5_cont_width' ) )
			{
				$custom_css .= '.evo_container__front_page_area_5 > .container { max-width: '.$max_width."px }\n";
			}
			if( $color = $this->get_setting( 'section_5_title_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_5 h2.page-header { color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_5_text_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_5 { color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_5_link_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_5 a { color: '.$color." }\n";
			}
			if( $color =  $this->get_setting( 'section_5_link_h_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_5 a:hover { color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_5_button_bg_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_5 .evo_widget > .btn.btn-default { background-color: '.$color." }\n";
				$custom_css .= '.evo_container__front_page_area_5 .evo_widget .item_excerpt > a.btn.btn-default,
				.evo_container__front_page_area_5 .evo_widget .item_content > a.btn.btn-default
				{ background-color: '.$color." }\n";
			}
			if( $color = $this->get_setting( 'section_5_button_color' ) )
			{
				$custom_css .= '.evo_container__front_page_area_5 .evo_widget > .btn.btn-default { color: '.$color." }\n";
				$custom_css .= '.evo_container__front_page_area_5 .evo_widget .item_excerpt > a.btn.btn-default,
				.evo_container__front_page_area_5 .evo_widget .item_content > a.btn.btn-default
				{ color: '.$color." }\n";
			}
			if( $this->get_setting( 'section_5_text_align' ) == 'section_5_center' )
			{
				$custom_css .= ".evo_container__front_page_area_5 { text-align: center }\n";
			}
			if( $this->get_setting( 'section_5_text_align' ) == 'section_5_right' )
			{
				$custom_css .= ".evo_container__front_page_area_5 { text-align: right }\n";
			}
			}


		}


		// ============ Section 6 - Header for Standalone Pages ============
		if( $disp == 'page' || $disp == 'single' )
		{
		if( $color = $this->get_setting( 'section_6_navbar_text_color' ) )
		{
			$custom_css .= '@media only screen and (min-width: 766px) {.navbar.navbar-default a, .navbar.navbar-default a:hover, .navbar-default .navbar-nav>.active>a, .navbar-default .navbar-nav>.active>a:focus, .navbar-default .navbar-nav>.active>a:hover, .navbar-default .navbar-nav>.active>a, .navbar-default .navbar-nav>li>a, .navbar-default .navbar-nav>li>a:focus, .navbar-default .navbar-nav>li>a:hover { color: ' . $color . " }}\n";
		}
		if( $this->get_setting( 'section_6_image_file_ID' ) )
		{
			$bg_image_File6 = & $FileCache->get_by_ID( $this->get_setting( 'section_6_image_file_ID' ), false, false );
		}
		if( !empty( $bg_image_File6 ) && $bg_image_File6->exists() )
		{
			$custom_css .= '.evo_container__standalone_page_area_6 { background-image: url('.$bg_image_File6->get_url().") }\n";
		}
		else
		{
			$color = $this->get_setting( 'section_6_bg_color' );
			$custom_css .= '.evo_container__standalone_page_area_6 { background: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'section_6_title_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_6 .evo_post_title h1, .evo_container__single_page_cover .evo_post_title h1 { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'section_6_text_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_6, .evo_container__single_page_cover { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'section_6_link_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_6 a, .evo_container__single_page_cover a { color: '.$color." }\n";
		}
		if( $color =  $this->get_setting( 'section_6_link_h_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_6 a:hover, .evo_container__single_page_cover a:hover { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'section_6_button_bg_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_6 .evo_widget > .btn.btn-default, .evo_container__single_page_cover .evo_widget > .btn.btn-default { background-color: '.$color." }\n";
			$custom_css .= '.evo_container__standalone_page_area_6 .evo_widget .item_excerpt > a.btn.btn-default,
			.evo_container__standalone_page_area_6 .evo_widget .item_content > a.btn.btn-default
			{ background-color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'section_6_button_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_6 .evo_widget > .btn.btn-default, .evo_container__single_page_cover .evo_widget > .btn.btn-default { color: '.$color." }\n";
			$custom_css .= '.evo_container__standalone_page_area_6 .evo_widget .item_excerpt > a.btn.btn-default,
			.evo_container__standalone_page_area_6 .evo_widget .item_content > a.btn.btn-default
			{ color: '.$color." }\n";
		}
		if( $this->get_setting( 'section_6_text_align' ) == 'section_6_center' )
		{
			$custom_css .= ".evo_container__standalone_page_area_6, .evo_container__single_page_cover { text-align: center }\n";
		}
		if( $this->get_setting( 'section_6_text_align' ) == 'section_6_right' )
		{
			$custom_css .= ".evo_container__standalone_page_area_6, .evo_container__single_page_cover { text-align: right }\n";
		}
		}


		// ============ Section 7 - Header for Contact form and Messaging ============
		if( $disp == 'msgform' || $disp == 'threads' )
		{
		if( $this->get_setting( 'section_7_image_file_ID' ) )
		{
			$bg_image_File7 = & $FileCache->get_by_ID( $this->get_setting( 'section_7_image_file_ID' ), false, false );
		}
		if( !empty( $bg_image_File7 ) && $bg_image_File7->exists() )
		{
			$custom_css .= '.evo_container__standalone_page_area_7 { background-image: url('.$bg_image_File7->get_url().") }\n";
		}
		else
		{
			$color = $this->get_setting( 'section_7_bg_color' );
			$custom_css .= '.evo_container__standalone_page_area_7 { background: '.$color." }\n";
		}
		if( $max_width = $this->get_setting( 'section_7_cont_width' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_7 > .container { max-width: '.$max_width."px }\n";
		}
		if( $color = $this->get_setting( 'section_7_title_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_7 .msgform_disp_title h1 { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'section_7_text_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_7 { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'section_7_link_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_7 a { color: '.$color." }\n";
		}
		if( $color =  $this->get_setting( 'section_7_link_h_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_7 a:hover { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'section_7_button_bg_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_7 .evo_widget > .btn.btn-default { background-color: '.$color." }\n";
			$custom_css .= '.evo_container__standalone_page_area_7 .evo_widget .item_excerpt > a.btn.btn-default,
			.evo_container__standalone_page_area_7 .evo_widget .item_content > a.btn.btn-default
			{ background-ccolor: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'section_7_button_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_7 .evo_widget > .btn.btn-default { color: '.$color." }\n";
			$custom_css .= '.evo_container__standalone_page_area_7 .evo_widget .item_excerpt > a.btn.btn-default,
			.evo_container__standalone_page_area_7 .evo_widget .item_content > a.btn.btn-default
			{ color: '.$color." }\n";
		}
		if( $this->get_setting( 'section_7_text_align' ) == 'section_7_center' )
		{
			$custom_css .= ".evo_container__standalone_page_area_7 { text-align: center }\n";
		}
		if( $this->get_setting( 'section_7_text_align' ) == 'section_7_right' )
		{
			$custom_css .= ".evo_container__standalone_page_area_7 { text-align: right }\n";
		}
		}


		// ============ Section - Header for restricted access disps ============
		if( in_array( $disp, array( 'login', 'register', 'lostpassword', 'activateinfo', 'access_denied', 'access_requires_login' ) ) )
		{
			if( $this->get_setting( 'section_access_image_file_ID' ) )
			{
				$bg_image_File_access = & $FileCache->get_by_ID( $this->get_setting( 'section_access_image_file_ID' ), false, false );
			}
			if( !empty( $bg_image_File_access ) && $bg_image_File_access->exists() )
			{
				$custom_css .= '.restricted_access_disps { background-image: url('.$bg_image_File_access->get_url().") }\n";
			}
			else
			{
				$color = $this->get_setting( 'section_access_bg_color' );
				$custom_css .= '.restricted_access_disps { background: '.$color." }\n";
			}
		}


		// ============ Section - Header for other disps  ============
		if( $this->get_setting( 'section_oth_image_file_ID' ) )
		{
			$bg_image_File_oth = & $FileCache->get_by_ID( $this->get_setting( 'section_oth_image_file_ID' ), false, false );
		}
		if( !empty( $bg_image_File_oth ) && $bg_image_File_oth->exists() )
		{
			$custom_css .= '.evo_container__standalone_page_area_oth { background-image: url('.$bg_image_File_oth->get_url().") }\n";
		}
		else
		{
			$color = $this->get_setting( 'section_oth_bg_color' );
			$custom_css .= '.evo_container__standalone_page_area_oth { background: '.$color." }\n";
		}
		if( $max_width = $this->get_setting( 'section_oth_cont_width' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_oth > .container { max-width: '.$max_width."px }\n";
		}
		if( $color = $this->get_setting( 'section_oth_text_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_oth { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'section_oth_title_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_oth h1.page_title { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'section_oth_link_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_oth a { color: '.$color." }\n";
		}
		if( $color =  $this->get_setting( 'section_oth_link_h_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_oth a:hover { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'section_oth_button_bg_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_oth .evo_widget > .btn.btn-default { background-color: '.$color." }\n";
			$custom_css .= '.evo_container__standalone_page_area_oth .evo_widget .item_excerpt > a.btn.btn-default,
			.evo_container__standalone_page_area_oth .evo_widget .item_content > a.btn.btn-default
			{ background-color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'section_oth_button_color' ) )
		{
			$custom_css .= '.evo_container__standalone_page_area_oth .evo_widget > .btn.btn-default { color: '.$color." }\n";
			$custom_css .= '.evo_container__standalone_page_area_oth .evo_widget .item_excerpt > a.btn.btn-default,
			.evo_container__standalone_page_area_oth .evo_widget .item_content > a.btn.btn-default
			{ color: '.$color." }\n";
		}
		if( $this->get_setting( 'section_oth_text_align' ) == 'section_oth_center' )
		{
			$custom_css .= ".evo_container__standalone_page_area_oth { text-align: center }\n";
		}
		if( $this->get_setting( 'section_oth_text_align' ) == 'section_oth_right' )
		{
			$custom_css .= ".evo_container__standalone_page_area_oth { text-align: right }\n";
		}


		// ============ Featured Posts Settings ============
		if( $color = $this->get_setting( 'bgimg_text_color' ) )
		{	// Custom text color on background image:
			$custom_css .= '.evo_hasbgimg { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'bgimg_link_color' ) )
		{	// Custom link color on background image:
			$custom_css .= '.evo_hasbgimg a { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'bgimg_hover_link_color' ) )
		{	// Custom link hover color on background image:
			$custom_css .= '.evo_hasbgimg a:hover { color: '.$color." }\n";
		}


		// ============ Buttons color customization ============
		if( $color = $this->get_setting( 'login_button_color' ) )
		{	// Custom text color on login button:
			$custom_css .= 'input[type="submit"].btn-success, .widget_core_user_login input.submit.btn-default { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'login_button_bg_color' ) )
		{	// Custom text background-color on login button:
			$custom_css .= 'input[type="submit"].btn-success, .widget_core_user_login input.submit.btn-default { background-color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'register_button_color' ) )
		{	// Custom text color on register button:
			$custom_css .= 'a.btn.btn-primary.btn-lg, input.btn.btn-primary.btn-lg, .widget_register_form input.submit { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'register_button_bg_color' ) )
		{	// Custom text background-color on register button:
			$custom_css .= 'a.btn.btn-primary.btn-lg, input.btn.btn-primary.btn-lg, .widget_register_form input.submit { background-color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'contact_button_color' ) )
		{	// Custom text color on contact button:
			$custom_css .= 'input[type="submit"].submit.btn-primary { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'contact_button_bg_color' ) )
		{	// Custom text background-color on contact button:
			$custom_css .= 'input[type="submit"].submit.btn-primary { background-color: '.$color." }\n";
		}


		// ============ Footer Section ============
		if( $color = $this->get_setting( 'footer_bg_color' ) )
		{	// Custom text color on background image:
			$custom_css .= '.footer_wrapper { background-color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'footer_content_color' ) )
		{	// Custom link color on background image:
			$custom_css .= '.footer_wrapper { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'footer_link_color' ) )
		{	// Custom link color on background image:
			$custom_css .= '.footer_wrapper a { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'footer_link_h_color' ) )
		{	// Custom link color on background image:
			$custom_css .= '.footer_wrapper a:hover { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'footer_button_bg_color' ) )
		{ // Custom background color:
			$custom_css .= '.footer_wrapper .evo_widget > .btn.btn-default { background-color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'footer_button_color' ) )
		{ // Custom background color:
			$custom_css .= '.footer_wrapper .evo_widget > .btn.btn-default { color: '.$color." }\n";
		}
		if( $this->get_setting( 'footer_text_align' ) == 'footer_center' )
		{
			$custom_css .= ".footer_wrapper { text-align: center }\n";
		}
		if( $this->get_setting( 'footer_text_align' ) == 'footer_right' )
		{
			$custom_css .= ".footer_wrapper { text-align: right }\n";
		}

		if( ! empty( $custom_css ) )
		{
			$custom_css = '<style type="text/css">
<!--
'.$custom_css.'
-->
</style>';
		add_headline( $custom_css );
		}

		// Init JS to affix Messages:
		init_affix_messages_js( $this->get_setting( 'message_affix_offset' ) );
	}


	/**
	 * Check if we can display a widget container when access is denied to collection by current user
	 *
	 * @param string Widget container key: 'header', 'page_top', 'menu', 'sidebar', 'sidebar2', 'footer'
	 * @return boolean TRUE to display
	 */
	function show_container_when_access_denied( $container_key )
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