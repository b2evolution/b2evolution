<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage pluralism
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class pluralism_Skin extends Skin
{
	/**
	 * colorbox enable
	 */ 
  	var $colorbox=true;
  	
	/**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Pluralism';
	}

	/**
	 * Get default type for the skin.
	 */
	function get_default_type()
	{
		return 'normal';
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


		// Colorbox (a lightweight Lightbox alternative) allows to zoom on images and do slideshows with groups of images:
		if ($this->colorbox) 
		{
			require_js_helper( 'colorbox' );
		}
	}
	/**
	 * Get definitions for editable params
	 *
	 * fp>alev : please put the functions in the ***same*** order in all skins. Use evopress as a reference.
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
				'colorbox' => array(
					'label' => T_('Colorbox enabled'),
					'note' => T_('Check if colorbox enabled'),
					'defaultvalue' => true,
					'type'	=>	'checkbox',
					'valid_pattern' => array( 'pattern'=>'~^([0-4]{1})?$~',
																		'error'=>T_('Invalid colorbox value.') ),
					'for_editing'	=>	true,
				)
			), parent::get_param_definitions( $params )	);

		return $r;
	}
	
}

?>