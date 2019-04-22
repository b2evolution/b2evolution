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
						'size' => '4',
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
						'size' => '4',
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
						'size' => '4',
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
						'size' => '4',
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
						'size' => '4',
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
						'size' => '4',
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
						'size' => '4',
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
						'size' => '4',
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

		$FileCache = & get_FileCache();

		// Skin specific initializations:

		// Limit images by max height:

		add_headline( '<link href="https://fonts.googleapis.com/css?family=Ek+Mukta:300|Josefin+Sans:300,400" rel="stylesheet">' );

		// **** Layout Settings / START ****
		// Max image height:
		$this->dynamic_style_rule( 'max_image_height', '.evo_image_block img { max-height: $setting_value$; width: auto; }', array(
			'suffix' => 'px'
		) );
		// **** Layout Settings / END ****

		// **** Top Navigation Bar Settings / START ****
		if( $this->get_setting( 'nav_bg_transparent' ) )
		{	// If "Transparent background" is enabled:
			// Background color:
			$this->dynamic_style_rule( 'nav_bg_color',
				// Set background-color for all cases, but (!)
				'.navbar, .navbar.affix { background-color: $setting_value$ }'.
				// ... exclude background-color in mentioned media queries and set transparent
				'@media (min-width: 1025px) { .navbar { background-color: transparent } }' );
			if( in_array( $disp, array( 'front', 'login', 'register', 'lostpassword', 'activateinfo', 'access_denied', 'access_requires_login' ) ) )
			{	// Links color:
				$this->dynamic_style_rule( 'nav_links_color', '@media (max-width: 1024px) { .affix-top a { color: $setting_value$ !important } }' );
			}
		}
		else
		{	// If "Transparent background" is disabled:
			// Background color:
			$this->dynamic_style_rule( 'nav_bg_color', '.navbar { background-color: $setting_value$ }' );
		}
		// Collection title font size:
		$this->dynamic_style_rule( 'nav_colltitle_size', '.navbar.main-header-navigation .navbar-brand > h3 a { font-size: $setting_value$ }', array(
			'suffix' => 'px'
		) );
		// Links font size:
		$this->dynamic_style_rule( 'nav_links_size',
			'.navbar.main-header-navigation.navbar-default .navbar-nav > .active > a, '.
			'.navbar.main-header-navigation.navbar-default .navbar-nav > .active > a:focus, '.
			'.navbar.main-header-navigation.navbar-default .navbar-nav > .active > a:hover, '.
			'.navbar.main-header-navigation.navbar-default .navbar-nav li > a { font-size: $setting_value$ }',
			array( 'suffix' => 'px' )
		);
		// Links color:
		$this->dynamic_style_rule( 'nav_links_color',
			'.navbar.navbar-default a, '.
			'.navbar.navbar-default a:hover, '.
			'.navbar-default .navbar-nav>.active>a, '.
			'.navbar-default .navbar-nav>.active>a:focus, '.
			'.navbar-default .navbar-nav>.active>a:hover, '.
			'.navbar-default .navbar-nav>.active>a, '.
			'.navbar-default .navbar-nav>li>a, '.
			'.navbar-default .navbar-nav>li>a:focus, '.
			'.navbar-default .navbar-nav>li>a:hover { color: $setting_value$ } }' );
		// **** Top Navigation Bar Settings / END ****

		// **** Page Top Settings / START ****
		// Button background color:
		$this->dynamic_style_rule( 'pagetop_button_bg_color', '.evo_container__page_top .evo_widget > .btn.btn-default { background-color: $setting_value$ }' );
		// Button text color:
		$this->dynamic_style_rule( 'pagetop_button_color', '.evo_container__page_top .evo_widget > .btn.btn-default { color: $setting_value$ }' );
		// **** Page Top Settings / END ****

		if( in_array( $disp, array( 'front', 'login', 'register', 'lostpassword', 'activateinfo', 'access_denied', 'access_requires_login' ) ) )
		{
			// **** Section 1 - Front Page Main Area / START ****
			if( $this->get_setting( 'section_1_display' ) )
			{
				if( $this->get_setting( 'nav_bg_transparent' ) )
				{	// Top navigation text color:
					$this->dynamic_style_rule( 'section_1_navbar_text_color', '@media (min-width: 1025px) { .affix-top a { color: $setting_value$ !important } }' );
				}
				// Background image:
				$this->dynamic_style_rule( 'section_1_image_file_ID', '.evo_container__front_first_section { background-image: $setting_value$ }', array( 'type' => 'image_file' ) );
				// Background color:
				$this->dynamic_style_rule( 'section_1_bg_color', '.evo_container__front_first_section { background-color: $setting_value$ }' );
				// Maximum content width:
				$this->dynamic_style_rule( 'section_1_cont_width', 'body.pictured.disp_front .container.main_page_wrapper { max-width: $setting_value$ }', array( 'suffix' => 'px' ) );
				// Collection title color:
				$this->dynamic_style_rule( 'section_1_coll_title_color', 'body.pictured.disp_front .main_page_wrapper .widget_core_coll_title h1 a { color: $setting_value$ }' );
				// Content title color:
				$this->dynamic_style_rule( 'section_1_title_color', 'body.pictured.disp_front .main_page_wrapper h2.page-header { color: $setting_value$ }' );
				// Normal text color:
				$this->dynamic_style_rule( 'section_1_text_color', 'body.pictured.disp_front .front_main_content, body.pictured .front_main_content h1 small, .evo_container__header, .evo_container__page_top { color: $setting_value$ }' );
				// Links color:
				$this->dynamic_style_rule( 'section_1_link_color',
					'body.pictured .main_page_wrapper .front_main_area a,'.
					'body.pictured .main_page_wrapper .front_main_area div.evo_withteaser div.item_content > a { color: $setting_value$ }'.
					'body.pictured .main_page_wrapper .front_main_area div.widget_core_coll_item_list.evo_noexcerpt.evo_withteaser ul li div.item_content > a,'.
					'body.pictured .main_page_wrapper .front_main_area div.widget_core_coll_post_list.evo_noexcerpt.evo_withteaser ul li div.item_content > a, .evo_container__page_top a { color: $setting_value$ }'.
					'body.pictured .front_main_content .ufld_icon_links a:not([class*="ufld__bgcolor"]):not(:hover) { background-color: $setting_value$ }'.
					'body.pictured .front_main_content .ufld_icon_links a:hover:not([class*="ufld__hovertextcolor"]) { color: $setting_value$ }'
				);
				// Muted text color:
				$this->dynamic_style_rule( 'section_1_muted_color', 'body.pictured.disp_front .main_page_wrapper .text-muted { color: $setting_value$ }' );
				// Inverse icon color:
				$this->dynamic_style_rule( 'section_1_icon_color',
					'body.pictured .front_main_content .ufld_icon_links a:not([class*="ufld__textcolor"]):not(:hover) { color: $setting_value$ }'.
					'body.pictured .front_main_content .ufld_icon_links a:hover:not([class*="ufld__hoverbgcolor"]) { background-color: $setting_value$ }'
				);
				// Button background color:
				$this->dynamic_style_rule( 'section_1_button_bg_color',
					'.evo_container__front_page_main_area .evo_widget > .btn.btn-default { background-color: $setting_value$ }'.
					'.evo_container__front_page_main_area .evo_widget .item_excerpt > a.btn.btn-default,'.
					'.evo_container__front_page_main_area .evo_widget .item_content > a.btn.btn-default { background-color: $setting_value$ }'
				);
				// Button text color:
				$this->dynamic_style_rule( 'section_1_button_color',
					'.evo_container__front_page_main_area .evo_widget > .btn.btn-default { color: $setting_value$ }'.
					'.evo_container__front_page_main_area .evo_widget .item_excerpt > a.btn.btn-default,'.
					'.evo_container__front_page_main_area .evo_widget .item_content > a.btn.btn-default { color: $setting_value$ }'
				);
				// Align text:
				$this->dynamic_style_rule( 'section_1_text_align', '.evo_container__front_page_main_area { text-align: $setting_value$ }', array(
					'options' => array(
						'section_1_left'   => 'left',
						'section_1_center' => 'center',
						'section_1_right'  => 'right;',
					)
				) );
			}
			// **** Section 1 - Front Page Main Area / END ****

			// **** Section 2 - Front Page Secondary Area / END ****
			if( $this->get_setting( 'section_2_display' ) )
			{
				// Background image:
				$this->dynamic_style_rule( 'section_2_image_file_ID', '.evo_container__front_page_secondary_area { background-image: $setting_value$ }', array( 'type' => 'image_file' ) );
				// Background color:
				$this->dynamic_style_rule( 'section_2_bg_color', '.evo_container__front_page_secondary_area { background-color: $setting_value$ }' );
				// Maximum content width:
				$this->dynamic_style_rule( 'section_2_cont_width', '.evo_container__front_page_secondary_area > .container { max-width: $setting_value$ }', array( 'suffix' => 'px' ) );
				// Title color:
				$this->dynamic_style_rule( 'section_2_title_color', '.evo_container__front_page_secondary_area h2.page-header { color: $setting_value$ }' );
				// Normal text color:
				$this->dynamic_style_rule( 'section_2_text_color', '.evo_container__front_page_secondary_area { color: $setting_value$ }' );
				// Links color:
				$this->dynamic_style_rule( 'section_2_link_color', '.evo_container__front_page_secondary_area a { color: $setting_value$ }' );
				// Links hover color:
				$this->dynamic_style_rule( 'section_2_link_h_color', '.evo_container__front_page_secondary_area a:hover { color: $setting_value$ }' );
				// Button background color:
				$this->dynamic_style_rule( 'section_2_button_bg_color',
					'.evo_container__front_page_secondary_area .evo_widget > .btn.btn-default { background-color: $setting_value$ }'.
					'.evo_container__front_page_secondary_area .evo_widget .item_excerpt > a.btn.btn-default,'.
					'.evo_container__front_page_secondary_area .evo_widget .item_content > a.btn.btn-default { background-color: $setting_value$ }'
				);
				// Button text color:
				$this->dynamic_style_rule( 'section_2_button_color',
					'.evo_container__front_page_secondary_area .evo_widget > .btn.btn-default { color: $setting_value$ }'.
					'.evo_container__front_page_secondary_area .evo_widget .item_excerpt > a.btn.btn-default,'.
					'.evo_container__front_page_secondary_area .evo_widget .item_content > a.btn.btn-default { color: $setting_value$ }'
				);
				// Align text:
				$this->dynamic_style_rule( 'section_2_text_align', '.evo_container__front_page_secondary_area { text-align: $setting_value$ }', array(
					'options' => array(
						'section_2_left'   => 'left',
						'section_2_center' => 'center',
						'section_2_right'  => 'right;',
					)
				) );
			}
			// **** Section 2 - Front Page Secondary Area / END ****

			// **** Section 3 - Front Page Area 3 / START ****
			if( $this->get_setting( 'section_3_display' ) )
			{
				// Background image:
				$this->dynamic_style_rule( 'section_3_image_file_ID', '.evo_container__front_page_area_3 { background-image: $setting_value$ }', array( 'type' => 'image_file' ) );
				// Background color:
				$this->dynamic_style_rule( 'section_3_bg_color', '.evo_container__front_page_area_3 { background-color: $setting_value$ }' );
				// Maximum content width:
				$this->dynamic_style_rule( 'section_3_cont_width', '.evo_container__front_page_area_3 > .container { max-width: $setting_value$ }', array( 'suffix' => 'px' ) );
				// Title color:
				$this->dynamic_style_rule( 'section_3_title_color', '.evo_container__front_page_area_3 h2.page-header { color: $setting_value$ }' );
				// Normal text color:
				$this->dynamic_style_rule( 'section_3_text_color', '.evo_container__front_page_area_3 { color: $setting_value$ }' );
				// Links color:
				$this->dynamic_style_rule( 'section_3_link_color', '.evo_container__front_page_area_3 a { color: $setting_value$ }' );
				// Links hover color:
				$this->dynamic_style_rule( 'section_3_link_h_color', '.evo_container__front_page_area_3 a:hover { color: $setting_value$ }' );
				// Button background color:
				$this->dynamic_style_rule( 'section_3_button_bg_color',
					'.evo_container__front_page_area_3 .evo_widget > .btn.btn-default { background-color: $setting_value$ }'.
					'.evo_container__front_page_area_3 .evo_widget .item_excerpt > a.btn.btn-default,'.
					'.evo_container__front_page_area_3 .evo_widget .item_content > a.btn.btn-default { background-color: $setting_value$ }'
				);
				// Button text color:
				$this->dynamic_style_rule( 'section_3_button_color',
					'.evo_container__front_page_area_3 .evo_widget > .btn.btn-default { color: $setting_value$ }'.
					'.evo_container__front_page_area_3 .evo_widget .item_excerpt > a.btn.btn-default,'.
					'.evo_container__front_page_area_3 .evo_widget .item_content > a.btn.btn-default { color: $setting_value$ }'
				);
				// Align text:
				$this->dynamic_style_rule( 'section_3_text_align', '.evo_container__front_page_area_3 { text-align: $setting_value$ }', array(
					'options' => array(
						'section_3_left'   => 'left',
						'section_3_center' => 'center',
						'section_3_right'  => 'right;',
					)
				) );
			}
			// **** Section 3 - Front Page Area 3 / END ****

			// **** Section 4 - Front Page Area 4 / START ****
			if( $this->get_setting( 'section_4_display' ) )
			{
				// Background image:
				$this->dynamic_style_rule( 'section_4_image_file_ID', '.evo_container__front_page_area_4 { background-image: $setting_value$ }', array( 'type' => 'image_file' ) );
				// Background color:
				$this->dynamic_style_rule( 'section_4_bg_color', '.evo_container__front_page_area_4 { background-color: $setting_value$ }' );
				// Maximum content width:
				$this->dynamic_style_rule( 'section_4_cont_width', '.evo_container__front_page_area_4 > .container { max-width: $setting_value$ }', array( 'suffix' => 'px' ) );
				// Title color:
				$this->dynamic_style_rule( 'section_4_title_color', '.evo_container__front_page_area_4 h2.page-header { color: $setting_value$ }' );
				// Normal text color:
				$this->dynamic_style_rule( 'section_4_text_color', '.evo_container__front_page_area_4 { color: $setting_value$ }' );
				// Links color:
				$this->dynamic_style_rule( 'section_4_link_color', '.evo_container__front_page_area_4 a { color: $setting_value$ }' );
				// Links hover color:
				$this->dynamic_style_rule( 'section_4_link_h_color', '.evo_container__front_page_area_4 a:hover { color: $setting_value$ }' );
				// Button background color:
				$this->dynamic_style_rule( 'section_4_button_bg_color',
					'.evo_container__front_page_area_4 .evo_widget > .btn.btn-default { background-color: $setting_value$ }'.
					'.evo_container__front_page_area_4 .evo_widget .item_excerpt > a.btn.btn-default,'.
					'.evo_container__front_page_area_4 .evo_widget .item_content > a.btn.btn-default { background-color: $setting_value$ }'
				);
				// Button text color:
				$this->dynamic_style_rule( 'section_4_button_color',
					'.evo_container__front_page_area_4 .evo_widget > .btn.btn-default { color: $setting_value$ }'.
					'.evo_container__front_page_area_4 .evo_widget .item_excerpt > a.btn.btn-default,'.
					'.evo_container__front_page_area_4 .evo_widget .item_content > a.btn.btn-default { color: $setting_value$ }'
				);
				// Align text:
				$this->dynamic_style_rule( 'section_4_text_align', '.evo_container__front_page_area_4 { text-align: $setting_value$ }', array(
					'options' => array(
						'section_4_left'   => 'left',
						'section_4_center' => 'center',
						'section_4_right'  => 'right;',
					)
				) );
			}
			// **** Section 4 - Front Page Area 4 / END ****

			// **** Section 5 - Front Page Area 5 / START ****
			if( $this->get_setting( 'section_5_display' ) )
			{
				// Background image:
				$this->dynamic_style_rule( 'section_5_image_file_ID', '.evo_container__front_page_area_5 { background-image: $setting_value$ }', array( 'type' => 'image_file' ) );
				// Background color:
				$this->dynamic_style_rule( 'section_5_bg_color', '.evo_container__front_page_area_5 { background-color: $setting_value$ }' );
				// Maximum content width:
				$this->dynamic_style_rule( 'section_5_cont_width', '.evo_container__front_page_area_5 > .container { max-width: $setting_value$ }', array( 'suffix' => 'px' ) );
				// Title color:
				$this->dynamic_style_rule( 'section_5_title_color', '.evo_container__front_page_area_5 h2.page-header { color: $setting_value$ }' );
				// Normal text color:
				$this->dynamic_style_rule( 'section_5_text_color', '.evo_container__front_page_area_5 { color: $setting_value$ }' );
				// Links color:
				$this->dynamic_style_rule( 'section_5_link_color', '.evo_container__front_page_area_5 a { color: $setting_value$ }' );
				// Links hover color:
				$this->dynamic_style_rule( 'section_5_link_h_color', '.evo_container__front_page_area_5 a:hover { color: $setting_value$ }' );
				// Button background color:
				$this->dynamic_style_rule( 'section_5_button_bg_color',
					'.evo_container__front_page_area_5 .evo_widget > .btn.btn-default { background-color: $setting_value$ }'.
					'.evo_container__front_page_area_5 .evo_widget .item_excerpt > a.btn.btn-default,'.
					'.evo_container__front_page_area_5 .evo_widget .item_content > a.btn.btn-default { background-color: $setting_value$ }'
				);
				// Button text color:
				$this->dynamic_style_rule( 'section_5_button_color',
					'.evo_container__front_page_area_5 .evo_widget > .btn.btn-default { color: $setting_value$ }'.
					'.evo_container__front_page_area_5 .evo_widget .item_excerpt > a.btn.btn-default,'.
					'.evo_container__front_page_area_5 .evo_widget .item_content > a.btn.btn-default { color: $setting_value$ }'
				);
				// Align text:
				$this->dynamic_style_rule( 'section_5_text_align', '.evo_container__front_page_area_5 { text-align: $setting_value$ }', array(
					'options' => array(
						'section_5_left'   => 'left',
						'section_5_center' => 'center',
						'section_5_right'  => 'right;',
					)
				) );
			}
			// **** Section 5 - Front Page Area 5 / END ****
		}

		// **** Section 6 - Header for Standalone Pages / START ****
		if( $disp == 'page' || $disp == 'single' )
		{
			if( $this->get_setting( 'nav_bg_transparent' ) )
			{
				// Top navigation text color:
				$this->dynamic_style_rule( 'section_6_navbar_text_color', '@media (min-width: 1025px) { .affix-top a { color: $setting_value$ !important } }' );
			}
			// Background image:
			$this->dynamic_style_rule( 'section_6_image_file_ID', '.evo_container__standalone_page_area_6 { background-image: $setting_value$ }', array( 'type' => 'image_file' ) );
			// Background color:
			$this->dynamic_style_rule( 'section_6_bg_color', '.evo_container__standalone_page_area_6 { background-color: $setting_value$ }' );
			// Maximum content width:
			$this->dynamic_style_rule( 'section_6_cont_width', '.evo_container__standalone_page_area_6 > .container { max-width: $setting_value$ }', array( 'suffix' => 'px' ) );
			// Title color:
			$this->dynamic_style_rule( 'section_6_title_color', '.evo_container__standalone_page_area_6 .evo_post_title h1, .evo_container__single_page_cover .evo_post_title h1 { color: $setting_value$ }' );
			// Normal text color:
			$this->dynamic_style_rule( 'section_6_text_color', '.evo_container__standalone_page_area_6, .evo_container__single_page_cover { color: $setting_value$ }' );
			// Links color:
			$this->dynamic_style_rule( 'section_6_link_color', '.evo_container__standalone_page_area_6 a, .evo_container__single_page_cover a { color: $setting_value$ }' );
			// Links hover color:
			$this->dynamic_style_rule( 'section_6_link_h_color', '.evo_container__standalone_page_area_6 a:hover, .evo_container__single_page_cover a:hover { color: $setting_value$ }' );
			// Button background color:
			$this->dynamic_style_rule( 'section_6_button_bg_color',
				'.evo_container__standalone_page_area_6 .evo_widget > .btn.btn-default, .evo_container__single_page_cover .evo_widget > .btn.btn-default { background-color: $setting_value$ }'.
				'.evo_container__standalone_page_area_6 .evo_widget .item_excerpt > a.btn.btn-default,'.
				'.evo_container__standalone_page_area_6 .evo_widget .item_content > a.btn.btn-default { background-color: $setting_value$ }'
			);
			// Button text color:
			$this->dynamic_style_rule( 'section_6_button_color',
				'.evo_container__standalone_page_area_6 .evo_widget > .btn.btn-default, .evo_container__single_page_cover .evo_widget > .btn.btn-default { color: $setting_value$ }'.
				'.evo_container__standalone_page_area_6 .evo_widget .item_excerpt > a.btn.btn-default,'.
				'.evo_container__standalone_page_area_6 .evo_widget .item_content > a.btn.btn-default { color: $setting_value$ }'
			);
			// Align text:
			$this->dynamic_style_rule( 'section_6_text_align', '.evo_container__standalone_page_area_6, .evo_container__single_page_cover { text-align: $setting_value$ }', array(
				'options' => array(
					'section_6_left'   => 'left',
					'section_6_center' => 'center',
					'section_6_right'  => 'right;',
				)
			) );
		}
		// **** Section 6 - Header for Standalone Pages / END ****

		// **** Section 7 - Header for Contact form and Messaging / START ****
		if( $disp == 'msgform' || $disp == 'threads' || $disp == 'messages' )
		{
			if( $this->get_setting( 'nav_bg_transparent' ) )
			{
				// Top navigation text color:
				$this->dynamic_style_rule( 'section_7_navbar_text_color', '@media (min-width: 1025px) { .affix-top a { color: $section_nav_color !important } }' );
			}
			// Background image:
			$this->dynamic_style_rule( 'section_7_image_file_ID', '.evo_container__standalone_page_area_7 { background-image: $setting_value$ }', array( 'type' => 'image_file' ) );
			// Background color:
			$this->dynamic_style_rule( 'section_7_bg_color', '.evo_container__standalone_page_area_7 { background-color: $setting_value$ }' );
			// Maximum content width:
			$this->dynamic_style_rule( 'section_7_cont_width', '.evo_container__standalone_page_area_7 > .container { max-width: $setting_value$ }', array( 'suffix' => 'px' ) );
			// Title color:
			$this->dynamic_style_rule( 'section_7_title_color', '.evo_container__standalone_page_area_7 .evo_post_title h1 { color: $setting_value$ }' );
			// Normal text color:
			$this->dynamic_style_rule( 'section_7_text_color', '.evo_container__standalone_page_area_7 { color: $setting_value$ }' );
			// Links color:
			$this->dynamic_style_rule( 'section_7_link_color', '.evo_container__standalone_page_area_7 a { color: $setting_value$ }' );
			// Links hover color:
			$this->dynamic_style_rule( 'section_7_link_h_color', '.evo_container__standalone_page_area_7 a:hover { color: $setting_value$ }' );
			// Button background color:
			$this->dynamic_style_rule( 'section_7_button_bg_color',
				'.evo_container__standalone_page_area_7 .evo_widget > .btn.btn-default { background-color: $setting_value$ }'.
				'.evo_container__standalone_page_area_7 .evo_widget .item_excerpt > a.btn.btn-default,'.
				'.evo_container__standalone_page_area_7 .evo_widget .item_content > a.btn.btn-default { background-color: $setting_value$ }'
			);
			// Button text color:
			$this->dynamic_style_rule( 'section_7_button_color',
				'.evo_container__standalone_page_area_7 .evo_widget > .btn.btn-default { color: $setting_value$ }'.
				'.evo_container__standalone_page_area_7 .evo_widget .item_excerpt > a.btn.btn-default,'.
				'.evo_container__standalone_page_area_7 .evo_widget .item_content > a.btn.btn-default { color: $setting_value$ }'
			);
			// Align text:
			$this->dynamic_style_rule( 'section_7_text_align', '.evo_container__standalone_page_area_7 { text-align: $setting_value$ }', array(
				'options' => array(
					'section_7_left'   => 'left',
					'section_7_center' => 'center',
					'section_7_right'  => 'right;',
				)
			) );
		}
		// **** Section 7 - Header for Contact form and Messaging / END ****

		// **** Section - Restricted access disps / START ****
		if( in_array( $disp, array( 'login', 'register', 'lostpassword', 'activateinfo', 'access_denied', 'access_requires_login' ) ) )
		{
			if( $this->get_setting( 'nav_bg_transparent' ) )
			{
				// Top navigation text color:
				$this->dynamic_style_rule( 'section_access_navbar_text_color', '@media (min-width: 1025px) { .affix-top a { color: $section_nav_color !important } }' );
			}
			// Background image:
			$this->dynamic_style_rule( 'section_access_image_file_ID', '.restricted_access_disps { background-image: $setting_value$ }', array( 'type' => 'image_file' ) );
			// Background color:
			$this->dynamic_style_rule( 'section_7_bg_color', '.restricted_access_disps { background-color: $setting_value$ }' );
			
		}
		// **** Section - Restricted access disps / END ****

		// **** Section - Header for other disps / START ****
		if( $this->get_setting( 'nav_bg_transparent' ) &&
		    ! in_array( $disp, array( 'front', 'login', 'register', 'lostpassword', 'activateinfo', 'access_denied', 'access_requires_login', 'msgform', 'threads', 'page' ) ) )
		{
			// Top navigation text color:
			$this->dynamic_style_rule( 'section_oth_navbar_text_color', '@media (min-width: 1025px) { .affix-top a { color: $section_nav_color !important } }' );
		}
		// Background image:
		$this->dynamic_style_rule( 'section_oth_image_file_ID', '.evo_container__standalone_page_area_oth { background-image: $setting_value$ }', array( 'type' => 'image_file' ) );
		// Background color:
		$this->dynamic_style_rule( 'section_oth_bg_color', '.evo_container__standalone_page_area_oth { background-color: $setting_value$ }' );
		// Maximum content width:
		$this->dynamic_style_rule( 'section_oth_cont_width', '.evo_container__standalone_page_area_oth > .container { max-width: $setting_value$ }', array( 'suffix' => 'px' ) );
		// Title color:
		$this->dynamic_style_rule( 'section_oth_title_color', '.evo_container__standalone_page_area_oth h1.page_title { color: $setting_value$ }' );
		// Normal text color:
		$this->dynamic_style_rule( 'section_oth_text_color', '.evo_container__standalone_page_area_oth { color: $setting_value$ }' );
		// Links color:
		$this->dynamic_style_rule( 'section_oth_link_color', '.evo_container__standalone_page_area_oth a { color: $setting_value$ }' );
		// Links hover color:
		$this->dynamic_style_rule( 'section_oth_link_h_color', '.evo_container__standalone_page_area_oth a:hover { color: $setting_value$ }' );
		// Button background color:
		$this->dynamic_style_rule( 'section_oth_button_bg_color',
			'.evo_container__standalone_page_area_oth .evo_widget > .btn.btn-default { background-color: $setting_value$ }'.
			'.evo_container__standalone_page_area_oth .evo_widget .item_excerpt > a.btn.btn-default,'.
			'.evo_container__standalone_page_area_oth .evo_widget .item_content > a.btn.btn-default { background-color: $setting_value$ }'
		);
		// Button text color:
		$this->dynamic_style_rule( 'section_oth_button_color',
			'.evo_container__standalone_page_area_oth .evo_widget > .btn.btn-default { color: $setting_value$ }'.
			'.evo_container__standalone_page_area_oth .evo_widget .item_excerpt > a.btn.btn-default,'.
			'.evo_container__standalone_page_area_oth .evo_widget .item_content > a.btn.btn-default { color: $setting_value$ }'
		);
		// Align text:
		$this->dynamic_style_rule( 'section_oth_text_align', '.evo_container__standalone_page_area_oth { text-align: $setting_value$ }', array(
			'options' => array(
				'section_oth_left'   => 'left',
				'section_oth_center' => 'center',
				'section_oth_right'  => 'right;',
			)
		) );
		// **** Section - Header for other disps / END ****

		// **** Footer Settings / START ****
		// Background color:
		$this->dynamic_style_rule( 'footer_bg_color', '.footer_wrapper { background-color: $setting_value$ }' );
		// Normal text color:
		$this->dynamic_style_rule( 'footer_content_color', '.footer_wrapper { color: $setting_value$ }' );
		// Links color:
		$this->dynamic_style_rule( 'footer_link_color', '.footer_wrapper a { color: $setting_value$ }' );
		// Links hover color:
		$this->dynamic_style_rule( 'footer_link_h_color', '.footer_wrapper a:hover { color: $setting_value$ }' );
		// Button background color:
		$this->dynamic_style_rule( 'footer_button_bg_color', '.footer_wrapper .evo_widget > .btn.btn-default { background-color: $setting_value$ }' );
		// Button text color:
		$this->dynamic_style_rule( 'footer_button_color', '.footer_wrapper .evo_widget > .btn.btn-default { color: $setting_value$ }' );
		// Align text:
		$this->dynamic_style_rule( 'footer_text_align', '.footer_wrapper { text-align: $setting_value$ }', array(
			'options' => array(
				'footer_left'   => 'left',
				'footer_center' => 'center',
				'footer_right'  => 'right;',
			)
		) );
		// **** Footer Settings / END ****

		// **** Featured posts Settings / START ****
		// Text color on background image:
		$this->dynamic_style_rule( 'bgimg_text_color', '.evo_hasbgimg { color: $setting_value$ }' );
		// Link color on background image:
		$this->dynamic_style_rule( 'bgimg_link_color', '.evo_hasbgimg a { color { color: $setting_value$ }' );
		// Hover link color on background image:
		$this->dynamic_style_rule( 'bgimg_hover_link_color', '.evo_hasbgimg a:hover { color: $setting_value$ }' );
		// **** Featured posts Settings / END ****

		// **** Button Customization Settings / START ****
		// Login button color:
		$this->dynamic_style_rule( 'login_button_color', 'input[type="submit"].btn-success, .widget_core_user_login input.submit.btn-default { color: $setting_value$ }' );
		// Login button background color:
		$this->dynamic_style_rule( 'login_button_bg_color', 'input[type="submit"].btn-success, .widget_core_user_login input.submit.btn-default { background-color: $setting_value$ }' );
		// Register button color:
		$this->dynamic_style_rule( 'register_button_color', 'a.btn.btn-primary.btn-lg, input.btn.btn-primary.btn-lg, .widget_register_form input.submit { color: $setting_value$ }' );
		// Register button background color:
		$this->dynamic_style_rule( 'register_button_bg_color', 'a.btn.btn-primary.btn-lg, input.btn.btn-primary.btn-lg, .widget_register_form input.submit { background-color: $setting_value$ }' );
		// Contact and Subscribe button color:
		$this->dynamic_style_rule( 'contact_button_color', 'input[type="submit"].submit.btn-primary { color: $setting_value$ }' );
		// Contact and Subscribe button background color:
		$this->dynamic_style_rule( 'contact_button_bg_color', 'input[type="submit"].submit.btn-primary { background-color: $setting_value$ }' );
		// **** Button Customization Settings / END ****

		// Add dynamic CSS rules headline:
		$this->add_dynamic_css_headline();

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