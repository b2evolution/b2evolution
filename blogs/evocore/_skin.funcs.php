<?php
/**
 * This file implements evoSkins support functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
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
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Template function: output base URL to current skin
 *
 * {@internal skinbase(-)}}
 */
function skinbase()
{
	global $baseurl, $skins_subdir, $skin, $blog;

	if( !empty( $skin ) )
	{
		echo $baseurl.$skins_subdir.$skin.'/';
	}
	else
	{ // No skin used:
		if( isset( $blog ) && $blog > 0 )
		{
			bloginfo( 'baseurl', 'raw' );
		}
		else
		{
			echo $baseurl;
		}
	}
}


/**
 * checks if a skin exists
 *
 * {@internal skin_exists(-)}}
 *
 * @return boolean true is exists, false if not
 * @param skin name (directory name)
 */
function skin_exists( $name )
{
	return is_dir( get_path( 'skins' ).$name );
}


/**
 * Outputs an <option> set with default skin selected
 *
 * skin_options(-)
 *
 */
function skin_options( $default = '' )
{
	for( skin_list_start(); skin_list_next(); )
	{
		echo '<option value="';
		skin_list_iteminfo( 'name' );
		echo '"';
		if( skin_list_iteminfo( 'name',false ) == $default ) echo ' selected="selected" ';
		echo '>';
		skin_list_iteminfo( 'name' );
		echo "</option>\n";
	}
}

/**
 * Initializes skin list iterator
 *
 * lists all folders in skin directory
 *
 * {@internal skin_list_start(-) }}
 */
function skin_list_start()
{
	global $skin_path, $skin_dir;

	$skin_path = get_path( 'skins' );
	$skin_dir = dir( $skin_path );
}


/**
 * Get next skin
 *
 * Lists all folders in skin directory,
 * except the ones starting with a . (UNIX style) or a _ (FrontPage style)
 *
 * {@internal skin_list_start(-) }}
 *
 * @return string skin name
 */
function skin_list_next()
{
	global $skin_path, $skin_dir, $skin_name;

	do
	{ // Find next subfolder:
		if( !($skin_name = $skin_dir->read()) )
			return false;		// No more subfolder
	} while( ( ! is_dir($skin_path.'/'.$skin_name) )	// skip regular files
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
	global $skin_path, $skin_name;

	switch( $what )
	{
		case 'path':
			$info = $skin_path.'/'.$skin_name;

		case 'name':
		default:
			$info = $skin_name;
	}

	if( $display ) echo $skin_name;

	return $skin_name;
}


/**
 * skin_change_url(-)
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