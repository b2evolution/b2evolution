<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage evopress
 *
 * @version $Id: _skin.class.php 3629 2013-05-01 08:05:20Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class evopress_Skin extends Skin
{
  /**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'evoPress';
	}


  /**
	 * Get default type for the skin.
	 */
	function get_default_type()
	{
		return 'normal';
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
				'head_bg_color_top' => array(
					'label' => T_('Header gradient top color'),
					'note' => T_('E-g: #ff0000 for red'),
					'defaultvalue' => '#6aace6',
					'type' => 'color',
				),
				'head_bg_color_bottom' => array(
					'label' => T_('Header gradient bottom color'),
					'note' => T_('E-g: #00ff00 for green'),
					'defaultvalue' => '#4280b6',
					'type' => 'color',
				),
				'display_post_date' => array(
					'label' => T_('Post date'),
					'note' => T_('Display the date of each post'),
					'defaultvalue' => 1,
					'type' => 'checkbox',
				),
				'sidebar_position' => array(
					'label' => T_('Sidebar position'),
					'note' => '',
					'defaultvalue' => 'right',
					'options' => array( 'left' => $this->T_('Left'), 'right' => $this->T_('Right') ),
					'type' => 'select',
				),
				'colorbox' => array(
					'label' => T_('Colorbox Image Zoom'),
					'note' => T_('Check to enable javascript zooming on images (using the colorbox script)'),
					'defaultvalue' => 1,
					'type' => 'checkbox',
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
			), parent::get_param_definitions( $params )	);

		return $r;
	}


	/**
	 * Get ready for displaying the skin.
	 *
	 * This may register some CSS or JS...
	 */
	function display_init()
	{
		// call parent:
		parent::display_init();

		// Add CSS:
		// fp> Note: having those here should allow
		// 1) Requesting them earlier as if they are @import'ed
		// 2) Allow bundling
		// fp> I am not 100% sure though. Comments welcome :)
		require_css( 'basic_styles.css', 'blog' ); // the REAL basic styles
		require_css( 'basic.css', 'blog' ); // Basic styles
		require_css( 'blog_base.css', 'blog' ); // Default styles for the blog navigation
		require_css( 'item_base.css', 'blog' ); // Default styles for the post CONTENT
		require_css( 'item.css', 'relative' );
		require_css( 'style.css', 'relative' );

		// Add custom CSS:
		$custom_css = '';

		$head_bg_color_top = $this->get_setting( 'head_bg_color_top' );
		$head_bg_color_bottom = $this->get_setting( 'head_bg_color_bottom' );
		if( !empty( $head_bg_color_top ) && !empty( $head_bg_color_bottom ) )
		{ // Custom Header background color:
			$custom_css .= '#headerimg {'."\n"
					.'background:-webkit-linear-gradient(top, '.$head_bg_color_top.', '.$head_bg_color_bottom.');'."\n"
					.'background:-moz-linear-gradient(top, '.$head_bg_color_top.', '.$head_bg_color_bottom.');'."\n"
					.'background:-o-linear-gradient(top, '.$head_bg_color_top.', '.$head_bg_color_bottom.');'."\n"
					.'background: -ms-linear-gradient(top, '.$head_bg_color_top.', '.$head_bg_color_bottom.');'."\n"
					.'filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=\''.$head_bg_color_top.'\', endColorstr=\''.$head_bg_color_bottom.'\');'."\n"
				.'}'."\n";
		}

		if( !empty( $custom_css ) )
		{
			$custom_css = '<style type="text/css">
	<!--
'.$custom_css.'	-->
	</style>';
			add_headline( $custom_css );
		}

		// Colorbox (a lightweight Lightbox alternative) allows to zoom on images and do slideshows with groups of images:
		if( $this->get_setting("colorbox") )
		{
			require_js_helper( 'colorbox', 'blog' );
		}
	}

}

?>