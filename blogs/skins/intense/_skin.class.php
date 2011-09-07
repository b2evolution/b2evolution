<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage intense
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class intense_Skin extends Skin
{
	/**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'Intense';
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
				'head_image' => array(
					'label' => T_('Header Image'),
					'note' => T_('0 for random header image; 1,2,3,4 for custom header image.'),
					'defaultvalue' => 0,
					'valid_pattern' => array( 'pattern'=>'~^([0-4]{1})?$~',
																		'error'=>T_('Invalid Header Image.') ),
				)
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
	}
}

?>