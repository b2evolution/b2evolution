<?php
/**
 * This file implements evoSkins support functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Template function: output HTML base tag to current skin
 */
function skin_base_tag()
{
	global $skins_url, $skin, $Blog;

	if( ! empty( $skin ) )
	{
		$base_href = $skins_url.$skin.'/';
	}
	else
	{ // No skin used:
		if( ! empty( $Blog ) )
		{
			$base_href = $Blog->get( 'baseurl' );
		}
		else
		{
			$base_href = $baseurl;
		}
	}

	base_tag( $base_href );
}


/**
 * Output content-type header
 * 
 * We use this method when we are NOT generating a static page
 *
 * @see skin_content_meta()
 * 
 * @param string content-type; override for RSS feeds
 */
function skin_content_header( $type = 'text/html' )
{
	global $generating_static, $io_charset;

	if( empty($generating_static) )
	{	// We use this method when we are NOT generating a static page
		header( 'Content-type: '.$type.'; charset='.$io_charset );
	}
}


/**
 * Output content-type http_equiv meta tag
 * 
 * We use this method when we ARE generating a static page
 *
 * @see skin_content_header()
 *
 * @param string content-type; override for RSS feeds
 */
function skin_content_meta( $type = 'text/html' )
{
	global $generating_static, $io_charset;

	if( ! empty($generating_static) )
	{	// We use this method when we ARE generating a static page
		echo '<meta http-equiv="Content-Type" content="'.$type.'; charset='.$io_charset.'" />';
	}
}


/**
 * Checks if a skin is provided by a plugin.
 *
 * @uses Plugin::GetProvidedSkins()
 * @return false|integer False in case no plugin provides the skin or ID of the first plugin that provides it.
 */
function skin_provided_by_plugin( $name )
{
	static $plugin_skins;
	if( ! isset($plugin_skins) || ! isset($plugin_skins[$name]) )
	{
		global $Plugins;

		$plugin_r = $Plugins->trigger_event_first_return('GetProvidedSkins', NULL, array('in_array'=>$name));
		if( $plugin_r )
		{
			$plugin_skins[$name] = $plugin_r['plugin_ID'];
		}
		else
		{
			$plugin_skins[$name] = false;
		}
	}

	return $plugin_skins[$name];
}


/**
 * Checks if a skin exists. This can either be a regular skin directory
 * or can be in the list {@link Plugin::GetProvidedSkins()}.
 *
 * @param skin name (directory name)
 * @return boolean true is exists, false if not
 */
function skin_exists( $name, $filename = '_main.php' )
{
	global $skins_path;

	if( is_readable( $skins_path.$name.'/'.$filename ) )
	{
		return true;
	}

	// Check list provided by plugins:
	if( skin_provided_by_plugin($name) )
	{
		return true;
	}

	return false;
}


/**
 * Install a skin
 *
 * @todo do not install if skin doesn't exist. Important for upgrade. Need to NOT fail if ZERO skins installed though :/
 *
 * @param string
 * @return Skin
 */
function & skin_install( $skin_folder )
{
	load_class( 'MODEL/skins/_skin.class.php' );
	$edited_Skin = new Skin(); // COPY (FUNC)

	$edited_Skin->set( 'name', $skin_folder );
	$edited_Skin->set( 'folder', $skin_folder );
	$edited_Skin->set( 'type', substr($skin_folder,0,1) == '_' ? 'feed' : 'normal' );

	// Look for containers in skin file:
	$edited_Skin->discover_containers();

	// INSERT NEW SKIN INTO DB:
	$edited_Skin->dbinsert();

	return $edited_Skin;
}


/*
 * $Log$
 * Revision 1.14  2007/01/08 02:11:56  fplanque
 * Blogs now make use of installed skins
 * next step: make use of widgets inside of skins
 *
 * Revision 1.13  2006/12/04 21:25:18  fplanque
 * removed user skin switching
 *
 * Revision 1.12  2006/11/13 17:00:02  fplanque
 * doc
 *
 * Revision 1.11  2006/10/30 12:57:33  blueyed
 * Fix for XHTML
 *
 * Revision 1.10  2006/10/08 22:59:31  blueyed
 * Added GetProvidedSkins and DisplaySkin hooks. Allow for optimization in Plugins::trigger_event_first_return()
 *
 * Revision 1.9  2006/09/05 22:29:21  fplanque
 * fixed content types (I hope)
 *
 * Revision 1.8  2006/08/18 17:23:58  fplanque
 * Visual skin selector
 *
 * Revision 1.7  2006/08/02 13:00:51  fplanque
 * detect incomplete upgrade of conf file
 *
 * Revision 1.6  2006/07/25 18:38:38  fplanque
 * fixed skin list
 *
 * Revision 1.5  2006/07/24 00:05:44  fplanque
 * cleaned up skins
 *
 * Revision 1.4  2006/07/04 17:32:29  fplanque
 * no message
 *
 * Revision 1.3  2006/03/24 19:40:49  blueyed
 * Only use absolute URLs if necessary because of used <base/> tag. Added base_tag()/skin_base_tag(); deprecated skinbase()
 *
 * Revision 1.2  2006/03/12 23:08:59  fplanque
 * doc cleanup
 *
 * Revision 1.1  2006/02/23 21:11:58  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 */
?>