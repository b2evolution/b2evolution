<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class manual_Skin extends Skin
{
	/**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Manual';
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
				'head_bg_color' => array(
					'label' => T_('Header Background Color'),
					'note' => T_('E-g: #ff0000 for red'),
					'defaultvalue' => '#03699C',
					'type' => 'color',
				),
				'head_text_color' => array(
					'label' => T_('Header Text Color'),
					'note' => T_('E-g: #00ff00 for green'),
					'defaultvalue' => '#FFFFFF',
					'type' => 'color',
				),
				'menu_bg_color' => array(
					'label' => T_('Menu Background Color'),
					'note' => T_('E-g: #ff0000 for red'),
					'defaultvalue' => '#74b4d4',
					'type' => 'color',
				),
				'menu_text_color' => array(
					'label' => T_('Menu Text Color'),
					'note' => T_('E-g: #00ff00 for green'),
					'defaultvalue' => '#000000',
					'type' => 'color',
				),
				'footer_bg_color' => array(
					'label' => T_('Footer Background Color'),
					'note' => T_('E-g: #0000ff for blue'),
					'defaultvalue' => '#DEE3E7',
					'type' => 'color',
				),
				'colorbox' => array(
					'label' => T_('Colorbox Image Zoom'),
					'note' => T_('Check to enable javascript zooming on images (using the colorbox script)'),
					'defaultvalue' => 1,
					'type'	=>	'checkbox',
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
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Get current skin post navigation setting. Always use this navigation setting where this skin is applied.
	 */
	function get_post_navigation()
	{
		return 'same_category';
	}


	/**
	 * Get ready for displaying the skin.
	 *
	 * This may register some CSS or JS...
	 */
	function display_init()
	{
		// call parent:
		parent::display_init();		// We pass NO params. This gives up the default Skins API v5 behavior.

		// Add custom CSS:
		$custom_css = '';

		if( $color = $this->get_setting( 'head_bg_color' ) )
		{ // Custom Header background color:
			$custom_css .= '	div.pageHeader { background-color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'head_text_color' ) )
		{ // Custom Header text color:
			$custom_css .= '	div.pageHeader, div.pageHeader a { color: '.$color." }\n";
		}

		if( $color = $this->get_setting( 'menu_bg_color' ) )
		{ // Custom Menu background color:
			$custom_css .= '	div.top_menu_bg { background-color: '.$color." }\n";
		}
		if( $color = $this->get_setting( 'menu_text_color' ) )
		{ // Custom Menu text color:
			$custom_css .= '	div.top_menu a { color: '.$color." }\n";
		}

		if( $color = $this->get_setting( 'footer_bg_color' ) )
		{ // Custom Footer background color:
			$custom_css .= '	div#pageFooter { background-color: '.$color." }\n";
		}

		if( !empty( $custom_css ) )
		{
			$custom_css = '<style type="text/css">
	<!--
'.$custom_css.'	-->
	</style>';
			add_headline( $custom_css );
		}

		// Functions to switch between the width sizes
		require_js( '#jquery#', 'blog' );
		require_js( 'widthswitcher.js', 'blog' );
	}
}

?>