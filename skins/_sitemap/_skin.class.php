<?php
/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage custom
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class _sitemap_Skin extends Skin
{
	/**
	 * Skin version
	 * @var string
	 */
	var $version = '6.7.5';

	/**
	 * Get default name for the skin.
	 * Note: the admin can customize it.
	 */
	function get_default_name()
	{
		return 'XML sitemap';
	}


	/**
	 * Get default type for the skin.
	 */
	function get_default_type()
	{
		return 'sitemap';
	}
}

?>