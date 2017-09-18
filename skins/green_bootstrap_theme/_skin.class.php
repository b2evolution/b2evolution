<?php
/**
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
	var $version = '6.8.1';

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
		return 'Green Bootstrap Theme';
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
		$r = array(
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
					'max_image_height' => array(
						'label' => T_('Max image height'),
						'note' => 'px. ' . T_('Set maximum height for post images.'),
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
								'medium'         => T_('Medium (18px)'),
								'large'          => T_('Large (20px)'),
								'very_large'     => T_('Very large (22px)'),
							),
						'type' => 'select',
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
						'note' => T_('E-g: #ff0000 for red'),
						'defaultvalue' => '#dfd',
						'type' => 'color',
					),
					'page_text_color' => array(
						'label' => T_('Text color'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#333',
						'type' => 'color',
					),
					'page_link_color' => array(
						'label' => T_('Link color'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#337ab7',
						'type' => 'color',
					),
					'page_hover_link_color' => array(
						'label' => T_('Hover link color'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#23527c',
						'type' => 'color',
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
					'current_tab_text_color' => array(
						'label' => T_('Current tab text color'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#333',
						'type' => 'color',
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
						'note' => 'px. ' . T_('Set maximum height for comment images.'),
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
					'mediaidx_thumb_size' => array(
						'label' => T_('Thumbnail size in Media index'),
						'note' => T_('Select thumbnail size for Media index images') . ' (disp=mediaidx).',
						'defaultvalue' => 'fit-256x256',
						'options' => get_available_thumb_sizes(),
						'type' => 'select',
					),
					'banner_public' => array(
						'label' => T_('Display "Public" banner'),
						'note' => T_('Display banner for "Public" albums (albums & comments)'),
						'defaultvalue' => 1,
						'type' => 'checkbox',
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
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#333',
						'type' => 'color',
					),
					'page_link_color' => array(
						'label' => T_('Page link color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#337ab7',
						'type' => 'color',
					),
					'current_tab_text_color' => array(
						'label' => T_('Current tab text color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#333',
						'type' => 'color',
					),
					'page_bg_color' => array(
						'label' => T_('Page background color'),
						'note' => T_('Click to select a color.'),
						'defaultvalue' => '#dfd',
						'type' => 'color',
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
					  'label'    => T_('Workflow column'),
					  'note'     => '',
					  'type'     => 'radio',
					  'field_lines' => true,
					  'options'  => array(
						 array( 'status_and_author', T_('Display Status & Item Author') ),
						 array( 'assignee_and_status', T_('Display Assignee (with Priority color coding) & Status') ),
					  ),
					  'defaultvalue' => 'status_and_author',
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
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#333',
						'type' => 'color',
					),
					'page_link_color' => array(
						'label' => T_('Page link color'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#337ab7',
						'type' => 'color',
					),
					'current_tab_text_color' => array(
						'label' => T_('Current tab text color'),
						'note' => T_('E-g: #ff6600 for orange'),
						'defaultvalue' => '#333',
						'type' => 'color',
					),
					'page_bg_color' => array(
						'label' => T_('Page background color'),
						'note' => T_('E-g: #ff0000 for red'),
						'defaultvalue' => '#fff',
						'type' => 'color',
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
		$r = array(
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
					'page_navigation' => array(
						'label' => T_('Page navigation'),
						'note' => T_('(EXPERIMENTAL)').' '.T_('Check this to show previous/next page links to navigate inside the <b>current</b> chapter.'),
						'defaultvalue' => 0,
						'type' => 'checkbox',
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
							array( 'footer',   sprintf( T_('"%s" container'), NT_('Footer') ),    1 ) ),
						),
				'section_access_end' => array(
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
	}


	/**
	 * Get ready for displaying the skin for collection kind "main".
	 *
	 * This may register some CSS or JS...
	 */
	function display_init_main()
	{
		global $Messages, $disp, $debug;

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
	 * Get ready for displaying the skin for collection kind "std".
	 *
	 * This may register some CSS or JS...
	 */
	function display_init_std()
	{
		global $Messages, $disp, $debug, $media_url, $media_path;

		// Add custom CSS:
		$custom_css = '';

		if( $color = $this->get_setting( 'page_bg_color' ) )
		{ // Custom page background color:
			$custom_css .= 'body { background-color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'page_text_color' ) )
		{ // Custom page text color:
			$custom_css .= 'body { color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'page_link_color' ) )
		{ // Custom page link color:
			$custom_css .= 'a { color: '.$color." }\n";
			$custom_css .= 'h4.evo_comment_title a, h4.panel-title a.evo_comment_type, .pagination li:not(.active) a, .pagination li:not(.active) span { color: '.$color." !important }\n";
			$custom_css .= '.pagination li.active a, .pagination li.active span { color: #fff; background-color: '.$color.' !important; border-color: '.$color." }\n";
			if( $this->get_setting( 'gender_colored' ) !== 1 )
			{ // If gender option is not enabled, choose custom link color. Otherwise, chose gender link colors:
				$custom_css .= 'h4.panel-title a { color: '.$color." }\n";
			}
		}
		if( $color = $this->get_setting( 'page_hover_link_color' ) )
		{ // Custom page link color on hover:
			$custom_css .= 'a:hover { color: '.$color." }\n";
		}
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
		if( $color = $this->get_setting( 'current_tab_text_color' ) )
		{ // Custom current tab text color:
			$custom_css .= 'ul.nav.nav-tabs li a.selected { color: '.$color." }\n";
		}

		// Limit images by max height:
		$max_image_height = intval( $this->get_setting( 'max_image_height' ) );
		if( $max_image_height > 0 )
		{
			$custom_css .= '.evo_image_block img { max-height: '.$max_image_height.'px; width: auto; }'." }\n";
		}

		// Font size customization
		if( $font_size = $this->get_setting( 'font_size' ) )
		{
			switch( $font_size )
			{
				case 'default': // When default font size, no CSS entry
					//$custom_css .= '';
					break;

				case 'standard':// When standard layout
					$custom_css .= '.container { font-size: 16px !important'." }\n";
					$custom_css .= '.container input.search_field { height: 100%'." }\n";
					$custom_css .= '.container h1 { font-size: 38px'." }\n";
					$custom_css .= '.container h2 { font-size: 32px'." }\n";
					$custom_css .= '.container h3 { font-size: 26px'." }\n";
					$custom_css .= '.container h4 { font-size: 18px'." }\n";
					$custom_css .= '.container h5 { font-size: 16px'." }\n";
					$custom_css .= '.container h6 { font-size: 14px'." }\n";
					$custom_css .= '.container .small { font-size: 85% !important'." }\n";
					break;

				case 'medium': // When default font size, no CSS entry
					$custom_css .= '.container { font-size: 18px !important'." }\n";
					$custom_css .= '.container input.search_field { height: 100%'." }\n";
					$custom_css .= '.container h1 { font-size: 40px'." }\n";
					$custom_css .= '.container h2 { font-size: 34px'." }\n";
					$custom_css .= '.container h3 { font-size: 28px'." }\n";
					$custom_css .= '.container h4 { font-size: 20px'." }\n";
					$custom_css .= '.container h5 { font-size: 18px'." }\n";
					$custom_css .= '.container h6 { font-size: 16px'." }\n";
					$custom_css .= '.container .small { font-size: 85% !important'." }\n";
					break;

				case 'large': // When default font size, no CSS entry
					$custom_css .= '.container { font-size: 20px !important'." }\n";
					$custom_css .= '.container input.search_field { height: 100%'." }\n";
					$custom_css .= '.container h1 { font-size: 42px'." }\n";
					$custom_css .= '.container h2 { font-size: 36px'." }\n";
					$custom_css .= '.container h3 { font-size: 30px'." }\n";
					$custom_css .= '.container h4 { font-size: 22px'." }\n";
					$custom_css .= '.container h5 { font-size: 20px'." }\n";
					$custom_css .= '.container h6 { font-size: 18px'." }\n";
					$custom_css .= '.container .small { font-size: 85% !important'." }\n";
					break;

				case 'very_large': // When default font size, no CSS entry
					$custom_css .= '.container { font-size: 22px !important'." }\n";
					$custom_css .= '.container input.search_field { height: 100%'." }\n";
					$custom_css .= '.container h1 { font-size: 44px'." }\n";
					$custom_css .= '.container h2 { font-size: 38px'." }\n";
					$custom_css .= '.container h3 { font-size: 32px'." }\n";
					$custom_css .= '.container h4 { font-size: 24px'." }\n";
					$custom_css .= '.container h5 { font-size: 22px'." }\n";
					$custom_css .= '.container h6 { font-size: 20px'." }\n";
					$custom_css .= '.container .small { font-size: 85% !important'." }\n";
					break;
			}
		}

		if( ! empty( $custom_css ) )
		{	// Function for custom_css:
			$custom_css = '<style type="text/css">
<!--
'.$custom_css.'
-->
		</style>';
			add_headline( $custom_css );
		}
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
			require_js( '#jqueryUI#', 'blog' );
		}

		if( in_array( $disp, array( 'single', 'page' ) ) )
		{	// Init JS to autcomplete the user logins
			require_js( '#bootstrap_typeahead#', 'blog' );
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
	}


	/**
	 * Get ready for displaying the skin for collection kind "manual".
	 *
	 * This may register some CSS or JS...
	 */
	function display_init_manual()
	{
		global $Messages, $disp, $debug;

		// Limit images by max height:
		$max_image_height = intval( $this->get_setting( 'max_image_height' ) );
		if( $max_image_height > 0 )
		{
			add_css_headline( '.evo_image_block img { max-height: '.$max_image_height.'px; width: auto; }' );
		}

		// Initialize a template depending on current page
		switch( $disp )
		{
			case 'front':
				// Init star rating for intro posts:
				init_ratings_js( 'blog', true );
				break;

			case 'posts':
				global $cat, $bootstrap_manual_posts_text;

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

		if( $this->is_left_navigation_visible() )
		{ // Include JS code for left navigation panel only when it is displayed:
			$this->require_js( 'left_navigation.js' );
		}
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


	/**** Functions for collection kind "std" ****/


	/**
	 * Check if we can display a sidebar for the current layout
	 *
	 * @param boolean TRUE to check if at least one sidebar container is visible
	 * @return boolean TRUE to display a sidebar
	 */
	function is_visible_sidebar( $check_containers = false )
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


	/**
	 * Get value for attribute "class" of column block
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

			case 'left_sidebar':
				// Left Sidebar
				return 'col-md-9 pull-right';

			case 'right_sidebar':
				// Right Sidebar
				return 'col-md-9';

			case 'no_sidebar':
				// No Sidebar (Single large column)
			default:
				return 'col-md-12';
		}
	}


	/**** Functions for collection kind "photo" + "forum" ****/


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


	/**** Functions for collection kind "forum" ****/


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

		if( ! is_logged_in() || ! $Blog->get_setting( 'track_unread_content' ) )
		{	// For not logged in users AND if the tracking of unread content is turned off for the collection
			$btn_class = 'btn-info';
			$btn_title = T_('Recent Topics');
		}
		else
		{	// For logged in users:
			global $current_User, $DB, $localtimenow;

			// Initialize SQL query to get only the posts which are displayed by global $MainList on disp=posts:
			$ItemList2 = new ItemList2( $Blog, $Blog->get_timestamp_min(), $Blog->get_timestamp_max(), NULL, 'ItemCache', 'recent_topics' );
			$ItemList2->set_default_filters( array(
					'unit' => 'all', // set this to don't calculate total rows
				) );
			$ItemList2->query_init();

			// Get a count of the unread topics for current user:
			$unread_posts_SQL = new SQL();
			$unread_posts_SQL->SELECT( 'COUNT( post_ID )' );
			$unread_posts_SQL->FROM( 'T_items__item' );
			$unread_posts_SQL->FROM_add( 'LEFT JOIN T_items__user_data ON post_ID = itud_item_ID AND itud_user_ID = '.$DB->quote( $current_User->ID ) );
			$unread_posts_SQL->FROM_add( 'INNER JOIN T_categories ON post_main_cat_ID = cat_ID' );
			$unread_posts_SQL->FROM_add( 'LEFT JOIN T_items__type ON post_ityp_ID = ityp_ID' );
			$unread_posts_SQL->WHERE( $ItemList2->ItemQuery->get_where( '' ) );
			$unread_posts_SQL->WHERE_and( 'post_last_touched_ts > '.$DB->quote( date2mysql( $localtimenow - 30 * 86400 ) ) );
			// In theory, it would be more safe to use this comparison:
			// $unread_posts_SQL->WHERE_and( 'itud_item_ID IS NULL OR itud_read_item_ts <= post_last_touched_ts' );
			// But until we have milli- or micro-second precision on timestamps, we decided it was a better trade-off to never see our own edits as unread. So we use:
			$unread_posts_SQL->WHERE_and( 'itud_item_ID IS NULL OR itud_read_item_ts < post_last_touched_ts' );

			// Execute a query with to know if current user has new data to view:
			$unread_posts_count = $DB->get_var( $unread_posts_SQL->get(), 0, NULL, 'Get a count of the unread topics for current user' );

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
		}

		// Print out the button:
		echo '<a href="'.$Blog->get( 'recentpostsurl' ).'" class="btn '.$btn_class.' pull-right btn_recent_topics">'.$btn_title.'</a>';
	}


	/**
	 * Display a panel with voting buttons for item
	 *
	 * @param object Item
	 * @param array Params
	 */
	function display_item_voting_panel( $Item, $params = array() )
	{
		skin_widget( array_merge( array(
				// CODE for the widget:
				'widget'      => 'item_vote',
				// Optional display params
				'Item'        => $Item,
				'block_start' => '',
				'block_end'   => '',
				'skin_ID'     => $this->ID,
			), $params ) );
	}


	/**
	 * Display a panel with voting buttons for item
	 *
	 * @param object Comment
	 * @param array Params
	 */
	function display_comment_voting_panel( $Comment, $params = array() )
	{
		$Comment->vote_helpful( '', '', '&amp;', true, true, array_merge( array(
				'before_title'          => '',
				'helpful_text'          => T_('Is this reply helpful?'),
				'class'                 => 'vote_helpful',
				'skin_ID'               => $this->ID,
			), $params ) );
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

		$write_new_post_url = $Blog->get_write_item_url( $chapter_ID );
		if( $write_new_post_url != '' )
		{ // Display button to write a new post
			$post_button = '<a href="'.$write_new_post_url.'" class="btn btn-primary '.$params['button_class'].'" title="'.T_('Post new topic').'"><i class="fa fa-pencil"></i> '.T_('New topic').'</a>';
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
				$post_button .= ' <a href="'.$Item->get_feedback_url().'#form_p'.$Item->ID.'" class="btn btn-default '.$params['button_class'].'" title="'.T_('Reply to topic').'"><i class="fa fa-reply"></i> '.T_('Reply').'</a>';
			}
		}

		if( !empty( $post_button ) )
		{ // Display button
			return '<div class="post_button btn-group '.$params['group_class'].'">'.$post_button.'</div>';
		}
	}


	/**** Functions for collection kind "manual" ****/


	/**
	 * Check if left navigation is visible for current page
	 *
	 * @return boolean TRUE
	 */
	function is_left_navigation_visible()
	{
		global $disp;

		if( in_array( $disp, array( 'access_requires_login', 'access_denied' ) ) )
		{ // Display left navigation column on this page when at least one sidebar container is visible:
			return $this->is_visible_container( 'sidebar' ) || $this->is_visible_container( 'sidebar2' );
		}

		// Display left navigation column only on these pages:
		return in_array( $disp, array( 'front', 'posts', 'flagged', 'single', 'search', 'edit', 'edit_comment', 'catdir', 'search', '404' ) );
	}
}

?>