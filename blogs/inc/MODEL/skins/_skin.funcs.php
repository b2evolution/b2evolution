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
 * Output content type header
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
 * Output content type http_equiv meta tag
 *
 * Needed when generating static files
 *
 * @param string content-type; override for RSS feeds
 */
function skin_content_meta( $type = 'text/html' )
{
	global $generating_static, $io_charset;

	if( ! empty($generating_static) )
	{	// We use this method when we ARE generating a static page
		echo '<meta http-equiv="Content-Type" content="'.$type.'; charset='.$io_charset.'">';
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
 * Outputs an <option> set with default skin selected
 *
 * skin_options(-)
 *
 */
function skin_options( $default = '' )
{
	echo skin_options_return( $default );
}


/**
 * Returns an <option> set with default skin selected
 *
 * @return string
 */
function skin_options_return( $default = '' )
{
	$r = '';

	for( skin_list_start(); skin_list_next(); )
	{
		$r .= '<option value="';
		$r .= skin_list_iteminfo( 'name', false );
		$r .=  '"';
		if( skin_list_iteminfo( 'name',false ) == $default )
		{
			$r .= ' selected="selected" ';
		}
		$r .=  '>';
		$r .= skin_list_iteminfo( 'name', false );
		$r .=  "</option>\n";
	}

	return $r;
}


/**
 * Initializes skin list iterator
 *
 * lists all folders in skin directory
 */
function skin_list_start()
{
	global $skins_path, $skin_dir;

	if( empty( $skins_path ) )
	{	// Check if conf has been properly for version 1.9 (remove in approx 12 months)
		debug_die( '$skins_path is not properly set in /conf/_advanced.php' );
	}

	$skin_dir = dir( $skins_path );
}


/**
 * Get next skin
 *
 * Lists all folders in skin directory,
 * except the ones starting with a . (UNIX style) or a _ (FrontPage style)
 *
 * @return string skin name
 */
function skin_list_next()
{
	global $skins_path, $skin_dir, $skin_name;

	do
	{ // Find next subfolder:
		if( !($skin_name = $skin_dir->read()) )
		{
			return false;		// No more subfolder
		}
	} while( ( ! is_dir($skins_path.$skin_name) )	// skip regular files
						|| ($skin_name[0] == '.')								// skip UNIX hidden files/dirs
						|| ($skin_name[0] == '_')								// skip FRONTPAGE hidden files/dirs
						|| ($skin_name == 'CVS' ) );						// Skip CVS directory
	// echo 'ret=',  $skin_name;
	return $skin_name;
}


/**
 * skin_list_iteminfo(-)
 *
 * Display info about item
 *
 * fplanque: created
 */
function skin_list_iteminfo( $what='', $display = true )
{
	global $skins_path, $skins_url, $skin_name;

	switch( $what )
	{
		case 'path':
			$info = $skins_path.$skin_name;
			break;

		case 'url':
			$info = $skins_url.$skin_name;
			break;

		case 'name':
		default:
			$info = $skin_name;
	}

	if( $display ) echo $info;

	return $info;
}


/**
 * @param boolean display (true) or return?
 */
function skin_change_url( $display = true )
{
	$r = url_add_param( get_bloginfo('blogurl'), 'skin='.rawurlencode(skin_list_iteminfo('name',false)) );
	if( $display )
	{
		echo $r;
	}
	else
	{
		return $r;
	}
}


/*
 * $Log$
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
 *
 * Revision 1.9  2005/12/12 19:21:23  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.8  2005/11/24 16:51:08  blueyed
 * minor
 *
 * Revision 1.7  2005/11/18 22:26:07  blueyed
 * skin_exists(): check for readable filename (_main.php by default), instead of is_dir()
 *
 * Revision 1.6  2005/09/06 17:13:55  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.5  2005/08/04 13:05:10  fplanque
 * bugfix
 *
 * Revision 1.4  2005/06/12 07:02:51  blueyed
 * Added skin_options_return()
 *
 * Revision 1.3  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.21  2004/10/12 18:48:34  fplanque
 * Edited code documentation.
 *
 */
?>