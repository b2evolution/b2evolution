<?php
/**
 * evoSkins support functions
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

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
		echo "$baseurl/$skins_subdir/$skin/";
	}
	else
	{	// No skin used:
		if( isset( $blog ) && $blog > 0 )
		{
			bloginfo( 'baseurl', 'raw' );
		}
		else
		{
			echo "$baseurl/";
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
	return is_dir(get_path( 'skins' ).'/'.$name);
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

?>