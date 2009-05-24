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
					'valid_pattern' => array( 'pattern'=>'¤^(#([a-f0-9]{3}){1,2})?$¤i',
																		'error'=>T_('Invalid color code.') ),
				),
				'menu_bg_color' => array(
					'label' => T_('Menu Background Color'),
					'note' => T_('E-g: #ff0000 for red'),
					'defaultvalue' => '#ddd',
					'valid_pattern' => array( 'pattern'=>'¤^(#([a-f0-9]{3}){1,2})?$¤i',
																		'error'=>T_('Invalid color code.') ),
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
			$custom_css = '
	<style type="text/css">
	<!--
'.$custom_css.'	-->
	</style>';
			add_headline( $custom_css );
		}

	}

}

/*
 * $Log$
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
