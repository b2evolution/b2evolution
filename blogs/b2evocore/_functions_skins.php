<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */

/*
 * skinbase(-)
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
 * lists all folders in skin directory
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
						|| ($skin_name[0] == '.')								// skip hidden files/dirs
						|| ($skin_name == 'CVS' ) );						// Skip CVS directory
	// echo 'ret=',  $skin_name;
	return $skin_name;
}


/*
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


/*
 * skin_change_url(-)
 */
function skin_change_url()
{
	echo get_bloginfo('blogurl').'?skin='.rawurlencode(skin_list_iteminfo('name',false));
}

?>