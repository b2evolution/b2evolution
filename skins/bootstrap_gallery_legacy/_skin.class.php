<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package evoskins
 * @subpackage bootstrap_gallery_skin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class bootstrap_gallery_legacy_Skin extends Skin
{
	/**
	 * Skin version
	 * @var string
	 */
	var $version = '6.9.3';

	/**
	 * Do we want to use style.min.css instead of style.css ?
	 */
	var $use_min_css = 'check';  // true|false|'check' Set this to true for better optimization
	/**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Bootstrap Gallery Legacy Skin';
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
				'main' => 'no',
				'std' => 'no',		// Blog
				'photo' => 'yes',
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
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		// Load to use function get_available_thumb_sizes()
		load_funcs( 'files/model/_image.funcs.php' );

		$r = array_merge( array(

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
						'defaultvalue' => 'fit-256x256',
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
						'defaultvalue' => '#fff',
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
			$custom_css .= '	#skin_wrapper { '.implode( ';', $custom_styles )." }\n";
		}

		$custom_styles = array();
		// Text color
		if( $text_color = $this->get_setting( 'page_text_color' ) )
		{
			$custom_styles[] = 'color: '.$text_color;
		}
		if( ! empty( $custom_styles ) )
		{
			$custom_css .= '	#skin_wrapper { '.implode( ';', $custom_styles )." }\n";
		}

		// Link color
		if( $text_color = $this->get_setting( 'page_link_color' ) )
		{
			$custom_styles[] = 'color: '.$text_color;
		}
		if( ! empty( $custom_styles ) )
		{
			$custom_css .= '	#skin_wrapper .container a { '.implode( ';', $custom_styles )." }\n";
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
			$custom_css .= '	#skin_wrapper { '.implode( ';', $custom_styles )." }\n";
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
			$custom_css .= '	.post_images .single-image .evo_image_legend { width:'.$thumbnail_sizes[ $single_thumb_size ][1].'px; max-width:'.$thumbnail_sizes[ $single_thumb_size ][1]."px }\n";
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
	 * Those templates are used for example by the messaging screens.
	 */
	function get_template( $name )
	{
		switch( $name )
		{
			case 'compact_form':
			case 'Form':
				// Default Form settings (Used for any form on front-office):
				return array_merge( parent::get_template( $name ), array(
					'fieldset_begin' => '<div class="clear"></div><div class="fieldset_wrapper $class$" id="fieldset_wrapper_$id$"><fieldset $fieldset_attribs$><div class="panel panel-default">'."\n"
															.'<legend class="panel-heading" $title_attribs$>$fieldset_title$</legend><div class="panel-body $class$">'."\n",
					'labelclass'     => 'control-label',
					'labelempty'     => '<label class="control-label"></label>',
					'inputstart'     => '<div class="controls">',
					'infostart'      => '<div class="controls"><div class="form-control-static">',
					'buttonsstart'   => '<div class="form-group"><div class="control-buttons">',
				) );

			default:
				// Delegate to parent class:
				return parent::get_template( $name );
		}
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