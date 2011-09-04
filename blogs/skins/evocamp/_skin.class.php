<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage evocamp
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class evocamp_Skin extends Skin
{
  /**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'evoCamp';
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
		require_js_helper( 'colorbox' );
	}

}

/*
 * $Log$
 * Revision 1.1  2011/09/04 02:30:20  fplanque
 * colorbox integration (MIT license)
 *
 */
?>
