<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage bootstrap_gallery
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class bootstrap_gallery_Skin extends Skin
{
	
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
		return 'Bootstrap Gallery';
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
				'page_text_color' => array(
					'label' => T_('Page text color'),
					'note' => T_('E-g: #00ff00 for green'),
					'defaultvalue' => '#333',
					'type' => 'color',
				),
				'menu_text_color' => array(
					'label' => T_('Menu text color'),
					'note' => T_('E-g: #ff6600 for orange'),
					'defaultvalue' => '#337ab7',
					'type' => 'color',
				),
				'page_bg_color' => array(
					'label' => T_('Page background color'),
					'note' => T_('E-g: #ff0000 for red'),
					'defaultvalue' => '#fff',
					'type' => 'color',
				),
				'post_text_color' => array(
					'label' => T_('Post info text color'),
					'note' => T_('E-g: #ff6600 for orange'),
					'defaultvalue' => '#333',
					'type' => 'color',
				),
				// Colorbox
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
				// Other settings
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
				'banner_public' => array(
					'label' => T_('"Public" banner'),
					'note' => T_('Display banner for "Public" posts (posts & comments)'),
					'defaultvalue' => 1,
					'type' => 'checkbox',
				),
				'mediaidx_thumb_size' => array(
					'label' => T_('Thumbnail size for media index'),
					'note' => '',
					'defaultvalue' => 'fit-256x256',
					'options' => get_available_thumb_sizes(),
					'type' => 'select',
				),
				'posts_thumb_size' => array(
					'label' => T_('Thumbnail size in post list'),
					'note' => '',
					'defaultvalue' => 'crop-192x192',
					'options' => get_available_thumb_sizes(),
					'type' => 'select',
				),
				'single_thumb_size' => array(
					'label' => T_('Thumbnail size in single page'),
					'note' => '',
					'defaultvalue' => 'fit-256x256',
					'options' => get_available_thumb_sizes(),
					'type' => 'select',
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
				'jquery', 							// Load jQuery
				'font_awesome', 					// Load Font Awesome (and use its icons as a priority over the Bootstrap glyphicons)
				'bootstrap', 						// Load Bootstrap (without 'bootstrap_theme_css')
				'bootstrap_evo_css', 			// Load the b2evo_base styles for Bootstrap (instead of the old b2evo_base styles)
				'bootstrap_messages',			// Initialize $Messages Class to use Bootstrap styles
				'style_css', 						// Load the style.css file of the current skin
				'colorbox',							// Load Colorbox (a lightweight Lightbox alternative + customizations for b2evo)
				'bootstrap_init_tooltips', 	// Inline JS to init Bootstrap tooltips (E.g. on comment form for allowed file extensions)
			) );

		// Skin specific initializations:

		// Add custom CSS:
		$custom_css = '';
		// ===== Custom page styles: =====
		$custom_styles = array();	
		
		// Menu text color
		if( $text_color = $this->get_setting( 'menu_text_color' ) )
		{
			$custom_styles[] = 'color: '.$text_color;
		}
		if( ! empty( $custom_styles ) )
		{
			$custom_css .= '	ul.nav.nav-tabs li a { '.implode( ';', $custom_styles )." }\n";
		}		
		
		// Page background color
		if( $bg_color = $this->get_setting( 'page_bg_color' ) )
		{
			$custom_styles[] = 'background-color: '.$bg_color;
		}
		
		// Text color
		if( $text_color = $this->get_setting( 'page_text_color' ) )
		{ // Text color:
			$custom_styles[] = 'color: '.$text_color;
		}
		if( ! empty( $custom_styles ) )
		{
			$custom_css .= '	body { '.implode( ';', $custom_styles )." }\n";
		}
		
		// Custom post area styles:
		if( $text_color = $this->get_setting( 'post_text_color' ) )
		{ // Text color:
			$custom_styles[] = 'color: '.$text_color;
		}
		if( ! empty( $custom_styles ) )
		{
			$custom_css .= '	section.evo_post__full { '.implode( ';', $custom_styles )." }\n";
		}				
		
		if( $text_color = $this->get_setting( 'post_text_color' ) )
		{ // Text color:
			$custom_styles[] = 'color: '.$text_color;
		}
		global $thumbnail_sizes;
		$posts_thumb_size = $this->get_setting( 'posts_thumb_size' );
		if( isset( $thumbnail_sizes[ $posts_thumb_size ] ) )
		{
			// Make the width of image block as fixed to don't expand it by long post title text
			$custom_css .= '	.posts_list .bPost { max-width:'.$thumbnail_sizes[ $posts_thumb_size ][1]."px }\n";
			// Set width & height for block with text "No pictures yet"
			$custom_css .= '	.posts_list .bPost b { width:'.( $thumbnail_sizes[ $posts_thumb_size ][1] - 20 ).'px;'
				.'height:'.( $thumbnail_sizes[ $posts_thumb_size ][2] - 20 ).'px'." }\n";
		}
		$single_thumb_size = $this->get_setting( 'single_thumb_size' );
		if( isset( $thumbnail_sizes[ $single_thumb_size ] ) )
		{
			// Make the width of image block as fixed to don't expand it by long post title text
			$custom_css .= '	.post_images .image_block .image_legend { width:'.$thumbnail_sizes[ $single_thumb_size ][1].'px; max-width:'.$thumbnail_sizes[ $single_thumb_size ][1]."px }\n";
			// Set width & height for block with text "No pictures yet"
			/*$custom_css .= '	.posts_list .bPost b { width:'.( $thumbnail_sizes[ $single_thumb_size ][1] - 20 ).'px;'
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
}
?>