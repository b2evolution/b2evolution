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
class _sitemap_Skin extends Skin
{
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

/*
 * $Log$
 * Revision 1.1  2009/09/26 13:41:55  tblue246
 * If XML feeds are disabled for a blog, still allow accessing "sitemap" skins.
 *
 */
?>
