<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage bootstrap_blog
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class bootstrap_blog_Skin extends Skin
{
	/**
	 * Skin version
	 * @var string
	 */
	var $version = '6.9.0';

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
		return 'Bootstrap Blog';
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
				'main' => 'partial',
				'std' => 'yes',		// Blog
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
	 * Get the declarations of the widgets that the skin wants to use.
	 *
	 * The skin class defines a default set of widgets to used. Skins should override this.
	 *
	 * @param string Collection kind: 'std', 'main', 'photo', 'group', 'forum', 'manual'
	 * @param array Additional params. Example value 'init_as_blog_b' => true
	 * @return array Array of default widgets:
	 *               - Key - Container name, 
	 *               - Value - array of widgets:
	 *                         0 - Type: 'core', 'plugin'.
	 *                         1 - Code.
	 *                         2 - Params: Array with params: Key - param code, Value - param value; NULL - for default params. (Default = NULL)
	 *                         3 - Order. (Default is started from 1 and incremented inside container)
	 *                         4 - Enabled? 1 or 0. (Default = 1)
	 */
	function get_default_widgets( $coll_kind, $context = array() )
	{
		global $DB;

		$context = array_merge( array(
				'coll_home_ID'          => NULL,
				'coll_photoblog_ID'     => NULL,
				'init_as_home'          => false,
				'init_as_blog_a'        => false,
				'init_as_blog_b'        => false,
				'init_as_events'        => false,
				'install_test_features' => false,
			), $context );

		$declared_widgets = array();

		// HEADER:
		$declared_widgets['Header'][] = array( 'core', 'coll_title' );
		$declared_widgets['Header'][] = array( 'core', 'coll_tagline' );


		// MENU:
		// Home page
		$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'home' ) );
		if( $context['init_as_blog_b'] )
		{	// Recent Posts
			$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'recentposts', 'link_text' => T_('News') ) );
		}
		// Pages list:
		$declared_widgets['Menu'][] = array( 'core', 'coll_page_list' );
		// Categories
		$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'catdir' ) );
		// Archives
		$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'arcdir' ) );
		// Latest comments
		$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'latestcomments' ) );
		$declared_widgets['Menu'][] = array( 'core', 'msg_menu_link', array( 'link_type' => 'messages' ), NULL, 0 );
		$declared_widgets['Menu'][] = array( 'core', 'msg_menu_link', array( 'link_type' => 'contacts', 'show_badge' => 0 ), NULL, 0 );
		$declared_widgets['Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'login' ), NULL, 0 );


		/* Item Single Header */
		$declared_widgets['Item Single Header'][] = array( 'core', 'item_info_line');


		/* Item Single */
		$declared_widgets['Item Single'][] = array( 'core', 'item_content' );
		$declared_widgets['Item Single'][] = array( 'core', 'item_attachments' );
		if( ! $context['init_as_blog_a'] && ! $context['init_as_events'] )
		{ // Item Tags
			$declared_widgets['Item Single'][] = array( 'core', 'item_tags' );
		}
		if( $context['init_as_blog_b'] )
		{ // About Author
			$declared_widgets['Item Single'][] = array( 'core', 'item_about_author' );
		}
		if( ( $context['init_as_blog_a'] || $context['init_as_events'] ) && $context['install_test_features'] )
		{ // Google Maps
			$declared_widgets['Item Single'][] = array( 'plugin', 'evo_Gmaps' );
		}
		if( $context['init_as_blog_a'] )
		{ // Small Print
			$declared_widgets['Item Single'][] = array( 'core', 'item_small_print', array( 'format' => ( $context['init_as_blog_a'] ? 'standard' : 'revision' ) ) );
		}
		// Seen by
		$declared_widgets['Item Single'][] = array( 'core', 'item_seen_by' );
		// Item voting panel:
		$declared_widgets['Item Single'][] = array( 'core', 'item_vote' );


		/* Page Top */
		$social_default_links = array(
				'twitter'    => 'https://twitter.com/b2evolution/',
				'facebook'   => 'https://www.facebook.com/b2evolution',
				'googleplus' => 'https://plus.google.com/+b2evolution/posts',
				'linkedin'   => 'https://www.linkedin.com/company/b2evolution-net',
				'github'     => 'https://github.com/b2evolution/b2evolution',
			);
		$social_fields_SQL = new SQL( 'Get user fields to create default social links widget' );
		$social_fields_SQL->SELECT( 'ufdf_code, ufdf_ID' );
		$social_fields_SQL->FROM( 'T_users__fielddefs' );
		$social_fields_SQL->WHERE( 'ufdf_type = "url"' );
		$social_fields_SQL->WHERE_and( 'ufdf_icon_name IS NOT NULL' );
		$social_fields_SQL->WHERE_and( 'ufdf_code IN ( "twitter", "facebook", "googleplus", "linkedin", "github" )' );
		$social_fields = $DB->get_assoc( $social_fields_SQL->get(), $social_fields_SQL->title );
		$social_link_params = array();
		$social_link_index = 1;
		foreach( $social_fields as $social_field_code => $social_field_ID )
		{
			$social_link_params['link'.$social_link_index] = $social_field_ID;
			$social_link_params['link'.$social_link_index.'_href'] = $social_default_links[ $social_field_code ];
			$social_link_index++;
		}
		$declared_widgets['Page Top'][] = array( 'core', 'social_links', $social_link_params );

		$default_blog_param = 's:7:"blog_ID";s:0:"";';
		if( ! empty( $context['coll_photoblog_ID'] ) )
		{ // In the case of initial install, we grab photos out of the photoblog (Blog #4)
			$default_blog_param = 's:7:"blog_ID";s:1:"'.intval( $context['coll_photoblog_ID'] ).'";';
		}

		/* Sidebar */
		if( $context['install_test_features'] )
		{
			// Current filters widget
			$declared_widgets['Sidebar'][] = array( 'core', 'coll_current_filters' );
			// User login widget
			$declared_widgets['Sidebar'][] = array( 'core', 'user_login' );
		}

// Specifically disabled to test this feature:		$declared_widgets['Sidebar'][] = array( 'core', 'coll_avatar' );
		if( ! $context['init_as_blog_a'] )
		{
			$declared_widgets['Sidebar'][] = array( 'plugin', 'evo_Calr' );
		}
		$declared_widgets['Sidebar'][] = array( 'core', 'coll_longdesc', array( 'title' => '$title$' ) );
		$declared_widgets['Sidebar'][] = array( 'core', 'coll_search_form' );
		$declared_widgets['Sidebar'][] = array( 'core', 'coll_category_list' );


		if( ! $context['init_as_blog_b'] )
		{
			$declared_widgets['Sidebar'][] = array( 'core', 'coll_media_index', array(
					'title'        => 'Random photo',
					'thumb_size'   => 'fit-160x120',
					'thumb_layout' => 'grid',
					'grid_nb_cols' => 1,
					'limit'        => 1,
					'order_by'     => 'RAND',
					'order_dir'    => 'ASC',
					// In the case of initial install, we grab photos out of the photoblog:
					'blog_ID'      => ( empty( $context['coll_photoblog_ID'] ) ? '' : intval( $context['coll_photoblog_ID'] ) ),
				) );
		}
		if( ! empty( $context['coll_home_ID'] ) && ( $context['init_as_blog_a'] || $context['init_as_blog_b'] ) )
		{
			$sidebar_type_ID = $DB->get_var( 'SELECT ityp_ID FROM T_items__type WHERE ityp_name = "Sidebar link"' );
			$declared_widgets['Sidebar'][] = array( 'core', 'coll_item_list', array(
					'blog_ID'              => $context['coll_home_ID'],
					'item_type'            => empty( $sidebar_type_ID ) ? '#' : $sidebar_type_ID,
					'title'                => 'Linkblog',
					'item_group_by'        => 'chapter',
					'item_title_link_type' => 'auto',
					'item_type_usage'      => 'special',
				) );
		}


		$declared_widgets['Sidebar'][] = array( 'core', 'coll_xml_feeds' );
		$declared_widgets['Sidebar'][] = array( 'core', 'mobile_skin_switcher' );



		/* Sidebar 2 */
		$declared_widgets['Sidebar 2'][] = array( 'core', 'coll_post_list' );
		if( $context['init_as_blog_b'] )
		{
			$declared_widgets['Sidebar 2'][] = array( 'core', 'coll_item_list', array(
					'title'                => 'Sidebar links',
					'order_by'             => 'RAND',
					'item_title_link_type' => 'auto',
					'item_type_usage'      => 'special',
				) );
		}
		$declared_widgets['Sidebar 2'][] = array( 'core', 'coll_comment_list' );
		$declared_widgets['Sidebar 2'][] = array( 'core', 'coll_media_index', array(
				'grid_nb_cols' => 3,
				'limit'        => 9,
				// In the case of initial install, we grab photos out of the photoblog:
				'blog_ID'      => ( empty( $context['coll_photoblog_ID'] ) ? '' : intval( $context['coll_photoblog_ID'] ) ),
			) );
		$declared_widgets['Sidebar 2'][] = array( 'core', 'free_html', array(
				'title'   => 'Sidebar 2',
				'content' => 'This is the "Sidebar 2" container. You can place any widget you like in here. In the evo toolbar at the top of this page, select "Customize", then "Blog Widgets".',
			) );


		/* Front Page Main Area */
		$declared_widgets['Front Page Main Area'][] = array( 'core', 'coll_featured_intro', NULL );
		$declared_widgets['Front Page Main Area'][] = array( 'core', 'coll_featured_posts', NULL );
		$declared_widgets['Front Page Main Area'][] = array( 'core', 'coll_post_list', array( 'title' => T_('More Posts'), 'featured' => 'other' ) );
		$declared_widgets['Front Page Main Area'][] = array( 'core', 'coll_comment_list' );
		if( $context['init_as_blog_b'] )
		{	// Install widget "Poll" only for Blog B on install:
			$declared_widgets['Front Page Main Area'][] = array( 'core', 'poll', array( 'poll_ID' => 1 ) );
		}


		/* Front Page Secondary Area */
		$declared_widgets['Front Page Secondary Area'][] = array( 'core', 'coll_flagged_list' );


		/* 404 Page */
		$declared_widgets['404 Page'][] = array( 'core', 'page_404_not_found' );
		$declared_widgets['404 Page'][] = array( 'core', 'coll_search_form' );
		$declared_widgets['404 Page'][] = array( 'core', 'coll_tag_cloud' );


		/* Mobile Footer */
		$declared_widgets['Mobile: Footer'][] = array( 'core', 'coll_longdesc' );
		$declared_widgets['Mobile: Footer'][] = array( 'core', 'mobile_skin_switcher' );


		/* Mobile Navigation Menu */
		$declared_widgets['Mobile: Navigation Menu'][] = array( 'core', 'coll_page_list' );
		$declared_widgets['Mobile: Navigation Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'ownercontact' ) );
		$declared_widgets['Mobile: Navigation Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'home' ) );
		if( $coll_kind == 'forum' )
		{ // Add menu with User Directory
			$declared_widgets['Mobile: Navigation Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'users' ) );
		}


		/* Mobile Tools Menu */
		$declared_widgets['Mobile: Tools Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'login' ) );
		$declared_widgets['Mobile: Tools Menu'][] = array( 'core', 'msg_menu_link', array( 'link_type' => 'messages' ) );
		$declared_widgets['Mobile: Tools Menu'][] = array( 'core', 'msg_menu_link', array( 'link_type' => 'contacts', 'show_badge' => 0 ) );
		$declared_widgets['Mobile: Tools Menu'][] = array( 'core', 'menu_link', array( 'link_type' => 'logout' ) );


		return $declared_widgets;
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 * @return array
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
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
						'defaultvalue' => '#fff',
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
					'current_tab_bg_color' => array(
						'label' => T_('Current tab background color'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#fff',
						'type' => 'color',
					),
					'hover_tab_bg_color' => array(
						'label' => T_('Hovered tab background color'),
						'note' => T_('E-g: #00ff00 for green'),
						'defaultvalue' => '#eee',
						'type' => 'color',
					),
					'panel_bg_color' => array(
						'label' => T_('Panel background color'),
						'note' => T_('Choose background color for function panels and widgets.'),
						'defaultvalue' => '#ffffff',
						'type' => 'color',
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
		global $media_url, $media_path;

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
		if( $color = $this->get_setting( 'current_tab_bg_color' ) )
		{ // Custom current tab background color:
			$custom_css .= 'ul.nav.nav-tabs li a.selected { background-color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'hover_tab_bg_color' ) )
		{ // Custom hovered tab background text color:
			$custom_css .= 'ul.nav.nav-tabs li a.default:hover { background-color: '.$color."; border-top-color: $color; border-left-color: $color; border-right-color: $color }\n";
		}
		if( $color = $this->get_setting( 'panel_bg_color' ) )
		{ // Panel background text color:
			$custom_css .= '.panel, .pagination>li>a { background-color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'panel_border_color' ) )
		{ // Panel border color:
			$custom_css .= '
			.pagination li a, .pagination>li>a:focus, .pagination>li>a:hover, .pagination>li>span:focus, .pagination>li>span:hover,
			.nav-tabs,
			.panel-default, .panel .panel-footer,
			.panel .table, .panel .table th, .table-bordered>tbody>tr>td, .table-bordered>tbody>tr>th, .table-bordered>tfoot>tr>td, .table-bordered>tfoot>tr>th, .table-bordered>thead>tr>td, .table-bordered>thead>tr>th
			{ border-color: '.$color." }\n";
			$custom_css .= '.panel .panel-heading { border-color: '.$color."; background-color: $color }\n";
			$custom_css .= '.nav-tabs>li>a:hover { border-bottom: 1px solid '.$color." }\n";
			$custom_css .= '.nav-tabs>li.active>a, .nav-tabs>li.active>a:focus, .nav-tabs>li.active>a:hover { border-top-color: '.$color."; border-left-color: $color; border-right-color: $color }\n";
		}
		if( $color = $this->get_setting( 'panel_heading_bg_color' ) )
		{ // Panel border color:
			$custom_css .= '.panel .panel-heading, .panel .panel-footer { background-color: '.$color." }\n";
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

			case 'left_sidebar':
				// Left Sidebar
				return 'col-md-9 pull-right';

			case 'right_sidebar':
				// Right Sidebar
			default:
				return 'col-md-9';
		}
	}
}

?>