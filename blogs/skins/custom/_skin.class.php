<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage custom
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class custom_Skin extends Skin
{
  /**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Custom';
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
					'defaultvalue' => '#78a',
					'valid_pattern' => array( 'pattern'=>'~^(#([a-f0-9]{3}){1,2})?$~i',
																		'error'=>T_('Invalid color code.') ),
				),
				'menu_bg_color' => array(
					'label' => T_('Menu Background Color'),
					'note' => T_('E-g: #ff0000 for red'),
					'defaultvalue' => '#ddd',
					'valid_pattern' => array( 'pattern'=>'~^(#([a-f0-9]{3}){1,2})?$~i',
																		'error'=>T_('Invalid color code.') ),
				),
				'display_post_time' => array(
					'label' => T_('Post time'),
					'note' => T_('Display time for each post'),
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

		// Make sure standard CSS is called ahead of custom CSS generated below:
		require_css( 'style.css', true );

		// Add custom CSS:
		$custom_css = '';

		if( $bg_color = $this->get_setting( 'head_bg_color') )
		{	// Custom Header background color:
			$custom_css .= '	div.pageHeader { background-color: '.$bg_color." }\n";
		}

		if( $bg_color = $this->get_setting( 'menu_bg_color') )
		{	// Custom Meu background color:
			$custom_css .= '	div.top_menu ul { background-color: '.$bg_color." }\n";
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
		require_js_helper( 'colorbox' );
	}

}

/*
 * $Log$
 * Revision 1.9  2011/09/07 00:28:26  sam2kb
 * Replace non-ASCII character in regular expressions with ~
 *
 * Revision 1.8  2011/09/04 02:30:20  fplanque
 * colorbox integration (MIT license)
 *
 * Revision 1.7  2010/01/19 19:38:41  fplanque
 * minor
 *
 * Revision 1.6  2010/01/16 17:08:36  efy-eugene
 * Selection of sidebar position added
 *
 * Revision 1.5  2010/01/13 23:40:21  fplanque
 * cleanup
 *
 * Revision 1.4  2010/01/13 17:21:40  efy-eugene
 * Checkbox parameter added
 *
 * Revision 1.3  2009/05/24 21:14:38  fplanque
 * _skin.class.php can now provide skin specific settings.
 * Demo: the custom skin has configurable header colors.
 * The settings can be changed through Blog Settings > Skin Settings.
 * Anyone is welcome to extend those settings for any skin you like.
 *
 * Revision 1.2  2009/05/23 22:49:10  fplanque
 * skin settings
 *
 * Revision 1.1  2009/05/23 20:20:17  fplanque
 * Skins can now have a _skin.class.php file to override default Skin behaviour. Currently only the default name but can/will be extended.
 *
 */
?>
