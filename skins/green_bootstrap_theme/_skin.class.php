<?php
/**
 * This is an EXPERIMENTAL DEMO of grouping several skins into a single Theme. 
 *
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage green_bootstrap_theme
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class green_bootstrap_theme_Skin extends Skin
{
	/**
	 * Skin version
	 * @var string
	 */
	var $version = '7.2.5';

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
		return 'Green Bootstrap Theme (EXPERIMENTAL)';
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
				'main' => 'yes',
				'std' => 'yes',		// Blog
				'photo' => 'yes',
				'forum' => 'yes',
				'manual' => 'yes',
				'group' => 'maybe',  // Tracker
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
			// TODO: Implement widget containers for this skin!
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
	 * Get definitions for editable params for collection kind "main"
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions_main( $params )
	{
		// Load for function get_available_thumb_sizes():
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array(
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
						'defaultvalue' => 'rgba(0,0,0,0.1)',
						'type' => 'color',
						'transparency' => true,
					),
					'pict_title_color' => array(
						'label' => T_('Title color'),
						'defaultvalue' => '#F0F0F0',
						'type' => 'color',
					),
					'front_text_color' => array(
						'label' => T_('Text color'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'front_link_color' => array(
						'label' => T_('Link color'),
						'defaultvalue' => '#FFFFFF',
						'type' => 'color',
					),
					'pict_muted_color' => array(
						'label' => T_('Muted text color'),
						'defaultvalue' => '#F0F0F0',
						'type' => 'color',
					),
					'front_icon_color' => array(
						'label' => T_('Inverse icon color'),
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
						'defaultvalue' => '#fff',
						'type' => 'color',
						'transparency' => true,
					),
					'secondary_text_color' => array(
						'label' => T_('Text color'),
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
						'defaultvalue' => '#fff',
						'type' => 'color',
					),
					'bgimg_link_color' => array(
						'label' => T_('Link color on background image'),
						'defaultvalue' => '#6cb2ef',
						'type' => 'color',
					),
					'bgimg_hover_link_color' => array(
						'label' => T_('Hover link color on background image'),
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
			);

		return $r;
	}


	/**
	 * Get definitions for editable params for collection kind "std"
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 * @return array
	 */
	function get_param_definitions_std( $params )
	{
		// Load for function get_available_thumb_sizes():
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array(
				'section_layout_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Layout Settings')
				),
					'layout' => array(
						'label' => T_('Layout'),
						'note' => T_('Select skin layout.'),
						'defaultvalue' => 'right_sidebar',
						'options' => array(
								'single_column'              => T_('Single Column Large'),
								'single_column_normal'       => T_('Single Column'),
								'single_column_narrow'       => T_('Single Column Narrow'),
								'single_column_extra_narrow' => T_('Single Column Extra Narrow'),
								'left_sidebar'               => T_('Left Sidebar'),
								'right_sidebar'              => T_('Right Sidebar'),
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

					'font' => array(
						'label' => T_('Default font'),
						'type'  => 'input_group',
						'inputs' => array(
							'_family' => array(
								'defaultvalue' => 'system_helveticaneue',
								'options'      => $this->get_font_definitions(),
								'type'         => 'select'
							),
							'_size' => array(
								'label' => T_('Size'),
								'defaultvalue' => 'default',
								'options'      => array(
									'default'        => T_('Default (14px)'),
									'standard'       => T_('Standard (16px)'),
									'medium'         => T_('Medium (18px)'),
									'large'          => T_('Large (20px)'),
									'very_large'     => T_('Very large (22px)'),
								),
								'type' => 'select'
							),
							'_weight' => array(
								'label' => T_('Weight'),
								'defaultvalue' => '400',
								'options' => array(
										'100' => '100',
										'200' => '200',
										'300' => '300',
										'400' => '400 ('.T_('Normal').')',
										'500' => '500',
										'600' => '600',
										'700' => '700 ('.T_('Bold').')',
										'800' => '800',
										'900' => '900',
									),
								'type' => 'select',
							)
						)
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


				'section_color_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Custom Settings')
				),
					'page_bg_color' => array(
						'label' => T_('Background color'),
						'defaultvalue' => '#DFD',
						'type' => 'color',
						'transparency' => true,
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
					'page_hover_link_color' => array(
						'label' => T_('Hover link color'),
						'defaultvalue' => '#23527c',
						'type' => 'color',
					),
					'bgimg_text_color' => array(
						'label' => T_('Text color on background image'),
						'defaultvalue' => '#fff',
						'type' => 'color',
					),
					'bgimg_link_color' => array(
						'label' => T_('Link color on background image'),
						'defaultvalue' => '#6cb2ef',
						'type' => 'color',
					),
					'bgimg_hover_link_color' => array(
						'label' => T_('Hover link color on background image'),
						'defaultvalue' => '#6cb2ef',
						'type' => 'color',
					),
					'current_tab_text_color' => array(
						'label' => T_('Current tab text color'),
						'defaultvalue' => '#333',
						'type' => 'color',
					),
					'current_tab_bg_color' => array(
						'label' => T_('Current tab background color'),
						'defaultvalue' => '#fff',
						'type' => 'color',
						'transparency' => true,
					),
					'hover_tab_bg_color' => array(
						'label' => T_('Hovered tab background color'),
						'defaultvalue' => '#eee',
						'type' => 'color',
						'transparency' => true,
					),
					'panel_bg_color' => array(
						'label' => T_('Panel background color'),
						'note' => T_('Choose background color for function panels and widgets.'),
						'defaultvalue' => '#ffffff',
						'type' => 'color',
						'transparency' => true,
					),
					'panel_border_color' => array(
						'label' => T_('Panel border color'),
						'note' => T_('Choose border color for function panels and widgets.'),
						'defaultvalue' => '#ddd',
						'type' => 'color',
					),
					'panel_heading_bg_color' => array(
						'label' => T_('Panel heading background color'),
						'note' => T_('Choose background color for function panels and widgets.'),
						'defaultvalue' => '#f5f5f5',
						'type' => 'color',
						'transparency' => true,
					),
				'section_color_end' => array(
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
							array( 'footer',   sprintf( T_('"%s" container'), NT_('Footer') ),    1 ) ),
						),
				'section_access_end' => array(
					'layout' => 'end_fieldset',
				),

			);

		return $r;
	}


	/**
	 * Get definitions for editable params for collection kind "photo"
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions_photo( $params )
	{
		// Load to use function get_available_thumb_sizes()
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array(

				'section_image_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Image Viewing')
				),
					'max_image_height' => array(
						'label' => T_('Max comment image height'),
						'input_suffix' => ' px ',
						'note' => T_('Set maximum height for comment images.'),
						'defaultvalue' => '',
						'type' => 'integer',
						'size' => '7',
						'allow_empty' => true,
					),
					'posts_thumb_size' => array(
						'label' => T_('Thumbnail size for Albums'),
						'note' => T_('Select thumbnail size for Albums') . ' (disp=catdir).',
						'defaultvalue' => 'crop-192x192',
						'options' => get_available_thumb_sizes(),
						'type' => 'select',
					),
					'single_thumb_size' => array(
						'label' => T_('Thumbnail size inside Album'),
						'note' => T_('Select thumbnail size for images inside Albums') . ' (disp=single).',
						'defaultvalue' => 'fit-640x480',
						'options' => get_available_thumb_sizes(),
						'type' => 'select',
					),
					'banner_public' => array(
						'label' => T_('Display "Public" banner'),
						'note' => T_('Display banner for "Public" albums (albums & comments)'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
					),
					'message_affix_offset' => array(
						'label' => T_('Messages affix offset'),
						'note' => 'px. ' . T_('Set message top offset value.'),
						'defaultvalue' => '',
						'type' => 'integer',
						'allow_empty' => true,
					),
				'section_image_end' => array(
					'layout' => 'end_fieldset',
				),


				'section_page_start' => array(
					'layout' => 'begin_fieldset',
					'label'  => T_('Page Styles')
				),
					'page_text_size' => array(
						'label' => T_('Page text size'),
						'note' => T_('Default value is 14 pixels.'),
						'defaultvalue' => '14px',
						'size' => '7',
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
						'defaultvalue' => '#DFD',
						'type' => 'color',
						'transparency' => true,
					),
				'section_page_end' => array(
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
							array( 'footer',   sprintf( T_('"%s" container'), NT_('Footer') ),    1 ) ),
						),
				'section_access_end' => array(
					'layout' => 'end_fieldset',
				),

			);

		return $r;
	}


	/**
	 * Get definitions for editable params for collection kind "forum"
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions_forum( $params )
	{
		// Load for function get_available_thumb_sizes():
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array(
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
					'sidebar_general_affix' => array(
						'label' => T_('Fixed position for General Sidebar'),
						'note'  => T_('Use affix to keep visible when scrolling down.'),
						'type'  => 'checkbox',
						'defaultvalue' => 0,
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
					'sidebar_single_affix' => array(
						'label' => T_('Fixed position for Single Sidebar'),
						'note'  => T_('Use affix to keep visible when scrolling down.'),
						'type'  => 'checkbox',
						'defaultvalue' => 1,
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

			);

		return $r;
	}


	/**
	 * Get definitions for editable params for collection kind "manual"
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions_manual( $params )
	{
		// Load for function get_available_thumb_sizes():
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array(
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

			);

		return $r;
	}


	/**
	 * Get ready for displaying the skin.
	 *
	 * This may register some CSS or JS...
	 */
	function display_init()
	{
		// Request some common features that the parent function (Skin::display_init()) knows how to provide:
		parent::display_init( array(
				'superbundle',             // Load general front-office JS + bundled jQuery and Bootstrap
				'bootstrap_messages',      // Initialize $Messages Class to use Bootstrap styles
				'style_css',               // Load the style.css file of the current skin
				'colorbox',                // Load Colorbox (a lightweight Lightbox alternative + customizations for b2evo)
				'disp_auto',               // Automatically include additional CSS and/or JS required by certain disps (replace with 'disp_off' to disable this)
			) );
	}


	/**
	 * Get ready for displaying the skin for collection kind "main".
	 *
	 * This may register some CSS or JS...
	 */
	function display_init_main()
	{
		global $Messages, $disp, $debug, $Session, $blog;

		// Skin specific initializations:

		// **** Layout Settings / START ****
		// Max image height:
		$this->dynamic_style_rule( 'max_image_height', '.evo_image_block img { max-height: $setting_value$px; width: auto; }', array(
			'check' => 'not_empty'
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
				'body.pictured .main_page_wrapper .front_main_area div.widget_uil_autotemp.evo_noexcerpt.evo_withteaser ul li div.item_content > a { color: $setting_value$'." }\n".
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


	/**
	 * Get ready for displaying the skin for collection kind "std".
	 *
	 * This may register some CSS or JS...
	 */
	function display_init_std()
	{
		global $Messages, $disp, $debug, $media_url, $media_path;

		// Skin specific initializations:

		// **** Layout Settings / START ****
		// Max image height:
		$this->dynamic_style_rule( 'max_image_height', '.evo_image_block img { max-height: $setting_value$px; width: auto; }', array(
			'check' => 'not_empty'
		) );
		// Default font - Family:
		$this->dynamic_style_rule( 'font_family', '#skin_wrapper { font-family: $setting_value$ }', array(
			'options' => $this->get_font_definitions( 'style' )
		) );
		// Default font - Size:
		$this->dynamic_style_rule( 'font_size', '$setting_value$', array(
			'options' => array(
				'default' => '',
				'standard' =>
					'.container { font-size: 16px !important}'.
					'.container input.search_field { height: 100%}'.
					'.container h1 { font-size: 38px }'.
					'.container h2 { font-size: 32px }'.
					'.container h3 { font-size: 26px }'.
					'.container h4 { font-size: 18px }'.
					'.container h5 { font-size: 16px }'.
					'.container h6 { font-size: 14px }'.
					'.container .small { font-size: 85% !important }',
				'medium' =>
					'.container { font-size: 18px !important }'.
					'.container input.search_field { height: 100% }'.
					'.container h1 { font-size: 40px }'.
					'.container h2 { font-size: 34px }'.
					'.container h3 { font-size: 28px }'.
					'.container h4 { font-size: 20px }'.
					'.container h5 { font-size: 18px }'.
					'.container h6 { font-size: 16px }'.
					'.container .small { font-size: 85% !important }',
				'large' =>
					'.container { font-size: 20px !important }'.
					'.container input.search_field { height: 100% }'.
					'.container h1 { font-size: 42px }'.
					'.container h2 { font-size: 36px }'.
					'.container h3 { font-size: 30px }'.
					'.container h4 { font-size: 22px }'.
					'.container h5 { font-size: 20px }'.
					'.container h6 { font-size: 18px }'.
					'.container .small { font-size: 85% !important }',
				'very_large' =>
					'.container { font-size: 22px !important }'.
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
		// Default font - Weight:
		$this->dynamic_style_rule( 'font_weight', '#skin_wrapper { font-weight: $setting_value$ }' );
		// **** Layout Settings / END ****

		// **** Custom Settings / START ****
		// Background color:
		$this->dynamic_style_rule( 'page_bg_color', '#skin_wrapper { background-color: $setting_value$ }' );
		// Text color:
		$this->dynamic_style_rule( 'page_text_color', '#skin_wrapper { color: $setting_value$ }' );
		// Link color:
		$this->dynamic_style_rule( 'page_link_color',
			'a { color: $setting_value$ }'.
			'h4.evo_comment_title a, h4.panel-title a.evo_comment_type, .pagination li:not(.active) a, .pagination li:not(.active) span { color: $setting_value$ !important }'.
			'.pagination li.active a, .pagination li.active span { color: #fff; background-color: $setting_value$ !important; border-color: $setting_value$ }'
		);
		if( $this->get_setting( 'gender_colored' ) !== 1 )
		{	// If gender option is not enabled, choose custom link color. Otherwise, chose gender link colors:
			$this->dynamic_style_rule( 'page_link_color', 'h4.panel-title a { color: $setting_value$ }' );
		}
		// Hover link color:
		$this->dynamic_style_rule( 'page_hover_link_color', 'a:hover { color: $setting_value$ }' );
		// Text color on background image:
		$this->dynamic_style_rule( 'bgimg_text_color', '.evo_hasbgimg { color: $setting_value$ }' );
		// Link color on background image:
		$this->dynamic_style_rule( 'bgimg_link_color', '.evo_hasbgimg a:not(.btn) { color: $setting_value$ }' );
		// Hover link color on background image:
		$this->dynamic_style_rule( 'bgimg_hover_link_color', '.evo_hasbgimg a:not(.btn):hover { color: $setting_value$ }' );
		// Current tab text color:
		$this->dynamic_style_rule( 'current_tab_text_color', 'ul.nav.nav-tabs li a.selected { color: $setting_value$ }' );
		// Current tab background color:
		$this->dynamic_style_rule( 'current_tab_bg_color', 'ul.nav.nav-tabs li a.selected { background-color: $setting_value$ }' );
		// Hovered tab background color:
		$this->dynamic_style_rule( 'hover_tab_bg_color', 'ul.nav.nav-tabs li a.default:hover { background-color: $setting_value$; border-top-color: $setting_value$; border-left-color: $color; border-right-color: $setting_value$ }' );
		// Panel background color:
		$this->dynamic_style_rule( 'panel_bg_color', '.panel, .pagination>li>a { background-color: $setting_value$ }' );
		// Panel border color:
		$this->dynamic_style_rule( 'panel_border_color',
			'.pagination li a, .pagination>li>a:focus, .pagination>li>a:hover, .pagination>li>span:focus, .pagination>li>span:hover,'.
			'.nav-tabs, .panel-default, .panel .panel-footer,'.
			'.panel .table, .panel .table th, .table-bordered>tbody>tr>td, .table-bordered>tbody>tr>th, .table-bordered>tfoot>tr>td, .table-bordered>tfoot>tr>th, .table-bordered>thead>tr>td, .table-bordered>thead>tr>th'.
			'{ border-color: $setting_value$ }'.
			'.panel .panel-heading { border-color: $setting_value$; background-color: $setting_value$ }'.
			'.nav-tabs>li>a:hover { border-bottom: 1px solid $setting_value$ }'.
			'.nav-tabs>li.active>a, .nav-tabs>li.active>a:focus, .nav-tabs>li.active>a:hover { border-top-color: $setting_value$; border-left-color: $setting_value$; border-right-color: $setting_value$ }'
		);
		// Panel heading background color:
		$this->dynamic_style_rule( 'panel_heading_bg_color', '.panel .panel-heading, .panel .panel-footer { background-color: $setting_value$ }' );
		// **** Custom Settings / END ****

		// Add dynamic CSS rules headline:
		$this->add_dynamic_css_headline();

		// Init JS to affix Messages:
		init_affix_messages_js( $this->get_setting( 'message_affix_offset' ) );
	}


	/**
	 * Get ready for displaying the skin for collection kind "photo".
	 *
	 * This may register some CSS or JS...
	 */
	function display_init_photo()
	{
		global $Messages, $debug;

		// Add custom CSS:
		$custom_css = '';

		// Limit images by max height:
		$max_image_height = intval( $this->get_setting( 'max_image_height' ) );
		if( $max_image_height > 0 )
		{
			$custom_css .= '.evo_image_block img { max-height: '.$max_image_height.'px; width: auto; }'."\n";
		}

// fp> TODO: the following code WORKS but produces UGLY CSS with tons of repetitions. It needs a full rewrite.

		// ===== Custom page styles: =====
		$custom_styles = array();

		// Text size <=== THIS IS A WORK IN PROGRESS
		if( $text_size = $this->get_setting( 'page_text_size' ) )
		{
			$custom_styles[] = 'font-size: '.$text_size;
		}
		if( ! empty( $custom_styles ) )
		{
			$custom_css .= '	body { '.implode( ';', $custom_styles )." }\n";
		}

		$custom_styles = array();
		// Text color
		if( $text_color = $this->get_setting( 'page_text_color' ) )
		{
			$custom_styles[] = 'color: '.$text_color;
		}
		if( ! empty( $custom_styles ) )
		{
			$custom_css .= '	body { '.implode( ';', $custom_styles )." }\n";
		}

		// Link color
		if( $text_color = $this->get_setting( 'page_link_color' ) )
		{
			$custom_styles[] = 'color: '.$text_color;
		}
		if( ! empty( $custom_styles ) )
		{
			$custom_css .= '	body .container a { '.implode( ';', $custom_styles )." }\n";
			$custom_css .= '	ul li a { '.implode( ';', $custom_styles )." }\n";
			$custom_css .= "	ul li a {background-color: transparent;}\n";
			$custom_css .= "	.ufld_icon_links a {color: #fff !important;}\n";
		}

		// Current tab text color
		if( $text_color = $this->get_setting( 'current_tab_text_color' ) )
		{
			$custom_styles[] = 'color: '.$text_color;
		}
		if( ! empty( $custom_styles ) )
		{
			$custom_css .= '	ul.nav.nav-tabs li a.selected { '.implode( ';', $custom_styles )." }\n";
		}

		// Page background color
		if( $bg_color = $this->get_setting( 'page_bg_color' ) )
		{
			$custom_styles[] = 'background-color: '.$bg_color;
		}
		if( ! empty( $custom_styles ) )
		{
			$custom_css .= '	body { '.implode( ';', $custom_styles )." }\n";
		}

		global $thumbnail_sizes;
		$posts_thumb_size = $this->get_setting( 'posts_thumb_size' );
		if( isset( $thumbnail_sizes[ $posts_thumb_size ] ) )
		{
			// Make the width of image block as fixed to don't expand it by long post title text
			$custom_css .= '	.posts_list .evo_post { max-width:'.$thumbnail_sizes[ $posts_thumb_size ][1]."px }\n";
			// Set width & height for block with text "No pictures yet"
			$custom_css .= '	.posts_list .evo_post b { width:'.( $thumbnail_sizes[ $posts_thumb_size ][1] - 20 ).'px;'
				.'height:'.( $thumbnail_sizes[ $posts_thumb_size ][2] - 20 ).'px'." }\n";
		}
		$single_thumb_size = $this->get_setting( 'single_thumb_size' );
		if( isset( $thumbnail_sizes[ $single_thumb_size ] ) )
		{
			// Make the width of image block as fixed to don't expand it by long post title text
			$custom_css .= '.post_images .single-image .evo_image_legend { width: 100%; }\n';
			// Set width & height for block with text "No pictures yet"
			/*$custom_css .= '	.posts_list .evo_post b { width:'.( $thumbnail_sizes[ $single_thumb_size ][1] - 20 ).'px;'
				.'height:'.( $thumbnail_sizes[ $single_thumb_size ][2] - 20 ).'px'." }\n";*/
		}
		if( !empty( $custom_css ) )
		{
			$custom_css = '<style type="text/css">
	<!--
'.$custom_css.'	-->
	</style>';
			add_headline( $custom_css );
		}
	}


	/**
	 * Get ready for displaying the skin for collection kind "photo".
	 *
	 * This may register some CSS or JS...
	 */
	function display_init_forum()
	{
		global $disp, $Messages, $debug;

		// Limit images by max height:
		$max_image_height = intval( $this->get_setting( 'max_image_height' ) );
		if( $max_image_height > 0 )
		{
			add_css_headline( '.evo_image_block img { max-height: '.$max_image_height.'px; width: auto; }' );
		}

		if( in_array( $disp, array( 'single', 'page', 'comments' ) ) )
		{ // Load jquery UI to animate background color on change comment status or on vote
			require_js_defer( '#jqueryUI#', 'blog' );
		}

		if( in_array( $disp, array( 'single', 'page' ) ) )
		{	// Init JS to autcomplete the user logins
			require_js_defer( '#bootstrap_typeahead#', 'blog' );
			init_autocomplete_login_js( 'blog', 'typeahead' );
			// Initialize date picker for _item_expert.form.php
			init_datepicker_js( 'blog' );
		}

		// Add custom CSS:
		$custom_css = '';


		// If sidebar == true + col-lg
		if( $layout = $this->get_setting( 'layout_general' ) != 'no_sidebar' )
		{
			$custom_css = "@media screen and (min-width: 1200px) {
				.forums_list .ft_date {
					white-space: normal;
					margin-top: 3px;
				}
				.disp_single .single_topic .evo_content_block .panel-body .evo_post__full,
				.disp_single .evo_comment .panel-body .evo_comment_text p,
				.disp_single .post_tags,
				.disp_single .evo_voting_panel,
				.disp_single .evo_seen_by
				{
					padding-left: 15px;
				}
				\n
			}";
		}

		if( ! empty( $custom_css ) )
		{ // Function for custom_css:
		$custom_css = '<style type="text/css">
<!--
'.$custom_css.'
-->
		</style>';
		add_headline( $custom_css );
		}

		if( ( $this->get_setting( 'sidebar_general_affix' ) && $this->is_visible_sidebar_forums( true, 'general' ) ) ||
		    ( $this->get_setting( 'sidebar_single_affix' ) && $this->is_visible_sidebar_forums( true, 'single' ) ) )
		{	// Init JS to fix sidebars on scroll down:
			require_js_defer( 'src/evo_affix_sidebars.js', 'blog', false, '#', 'footerlines' );
		}
	}


	/**
	 * Get ready for displaying the skin for collection kind "manual".
	 *
	 * This may register some CSS or JS...
	 */
	function display_init_manual()
	{
		global $Messages, $disp, $debug;

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
				break;
		}

		if( $this->is_side_navigation_visible() )
		{ // Include JS code for left navigation panel only when it is displayed:
			$this->require_js_defer( 'left_navigation.js' );
		}

		// Init JS to affix Messages:
		init_affix_messages_js( $this->get_setting( 'message_affix_offset' ) );
	}


	/**
	 * Those templates are used for example by the messaging screens.
	 */
	function get_template( $name )
	{
		global $Blog;

		switch( $Blog->get( 'type' ) )
		{
			case 'photo':
				// Collection kind "photo":
				switch( $name )
				{
					case 'compact_form':
					case 'Form':
						// Default Form settings (Used for any form on front-office):
						return array_merge( parent::get_template( $name, false ), array(
							'fieldset_begin' => '<div class="clear"></div><div class="fieldset_wrapper $class$" id="fieldset_wrapper_$id$"><fieldset $fieldset_attribs$><div class="panel panel-default">'."\n"
																	.'<legend class="panel-heading" $title_attribs$>$fieldset_title$</legend><div class="panel-body $class$">'."\n",
							'labelclass'     => 'control-label',
							'labelempty'     => '<label class="control-label"></label>',
							'inputstart'     => '<div class="controls">',
							'infostart'      => '<div class="controls"><div class="form-control-static">',
							'buttonsstart'   => '<div class="form-group"><div class="control-buttons">',
						) );
				}
				break;

			case 'forum':
				// Collection kind "forum":
				switch( $name )
				{
					case 'cat_array_mode':
						// What category level use to display the items on disp=posts:
						//   - 'children' - Get items from current category and from all its sub-categories recirsively
						//   - 'parent' - Get items ONLY from current category WITHOUT sub-categories
						return 'parent';
				}
				break;

			case 'manual':
				// Collection kind "manual":
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
							// Register
							'register_page_before'      => '<div class="evo_panel__register">',
							'register_page_after'       => '</div>',
							'register_form_title'       => T_('Register'),
							'register_links_attrs'      => '',
							'register_use_placeholders' => true,
							'register_field_width'      => 252,
							'register_disabled_page_before' => '<div class="evo_panel__register register-disabled">',
							'register_disabled_page_after'  => '</div>',
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
							'featured_intro_before' => '<div class="jumbotron">',
							'featured_intro_after'  => '</div>',
							// Form "Sending a message"
							'msgform_form_title' => T_('Sending a message'),
						);
				}
				break;
		}

		// Delegate to parent class:
		return parent::get_template( $name );
	}


	/**** Template Functions depending on collection kind: ****/

	/**
	 * Get value for attribute "class" of column block
	 * depending on skin setting "Layout"
	 *
	 * @param string Layout: 'general' or 'single'
	 * @return string
	 */
	function get_column_class( $layout = 'general' )
	{
		return $this->call_func_by_coll_type( __FUNCTION__, func_get_args() );
	}
	// Alias of get_column_class() for collection kind "Blog":
	function get_column_class_std( $layout = 'general' )
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

			case 'left_sidebar':
				// Left Sidebar
				return 'col-md-9 pull-right-md';

			case 'right_sidebar':
				// Right Sidebar
			default:
				return 'col-md-9';
		}
	}
	// Alias of get_column_class() for collection kind "Forums":
	function get_column_class_forums( $layout = 'general' )
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
	// Alias of get_column_class() for collection kind "Photo":
	function get_column_class_photo( $layout = 'general' )
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


	/**
	 * Get value for attribute "class" of column block
	 * depending on skin setting "Layout"
	 *
	 * @param string Layout: 'general' or 'single'
	 * @return string
	 */
	function is_visible_sidebar( $check_containers = false, $layout = 'general' )
	{
		return $this->call_func_by_coll_type( __FUNCTION__, func_get_args() );
	}
	// Alias of is_visible_sidebar() for collection kind "Blog":
	function is_visible_sidebar_std( $check_containers = false, $layout = 'general' )
	{
		$layout = $this->get_setting( 'layout' );

		if( $layout != 'left_sidebar' && $layout != 'right_sidebar' )
		{ // Sidebar is not displayed for selected skin layout
			return false;
		}

		if( $check_containers )
		{ // Check if at least one sidebar container is visible
			return ( $this->is_visible_container( 'sidebar' ) ||  $this->is_visible_container( 'sidebar2' ) );
		}
		else
		{ // We should not check the visibility of the sidebar containers for this case
			return true;
		}
	}
	// Alias of is_visible_sidebar() for collection kind "Forums":
	function is_visible_sidebar_forums( $check_containers = false, $layout = 'general' )
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


	/**** Functions for collection kind "Forums" ****/


	/**
	 * Determine to display status banner or to don't display
	 *
	 * @param string Status of Item or Comment
	 * @return boolean TRUE if we can display status banner for given status
	 */
	function enabled_status_banner( $status )
	{
		if( $status != 'published' )
		{ // Display status banner everytime when status is not 'published'
			return true;
		}
		if( is_logged_in() && $this->get_setting( 'banner_public' ) )
		{ // Also display status banner if status is 'published'
			//   AND current user is logged in
			//   AND this feature is enabled in skin settings
			return true;
		}
		// Don't display status banner
		return false;
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
						'display_noactive'       => true,
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
						'display_noactive'       => true,
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
	 * Display header for posts list
	 *
	 * @param string Title
	 */
	function display_posts_list_header( $title, $params = array() )
	{
		global $Blog;

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
				'before_workflow_status'  => '<div class="col-lg-2 col-md-2 col-sm-3 col-xs-6">',
				'after_workflow_status'   => '</div>',
				'before_workflow_actions' => '<div class="col-lg-2 col-md-2 col-sm-3 col-xs-6 text-right">',
				'after_workflow_actions'  => '</div>',
			), $params );

		// Check if current User can view workflow properties:
		$can_view_workflow =
			// Workflow must be enabled for current Collection:
			$Blog->get_setting( 'use_workflow' ) &&
			// Current User must has a permission to be assigned for tasks of the current Collection:
			check_user_perm( 'blog_can_be_assignee', 'edit', false, $Blog->ID );

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
				/*echo '<script>
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
				</script>';*/
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


	/**** Functions for collection kind "manual" ****/


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
