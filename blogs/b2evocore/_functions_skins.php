<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */

/*
 * skinbase(-)
 */
function skinbase()
{
	global $skins_subdir, $skin;
	
	bloginfo('siteurl');
	
	if( !empty( $skin ) )
	{
		echo "/$skins_subdir/$skin/";
	}
	else
	{	// No skin used:
		echo '/';
	}
}


/*
 * skin_list_start(-)
 * 
 * Start list iterator
 * lists all folders in skin directory
 *
 * fplanque: created
 */
function skin_list_start()
{
	global $skin_path, $skin_dir, $skin_name;

	$skin_path = get_path( 'skins' );
	
	$skin_dir = dir( $skin_path );
	do
	{
		if( !($skin_name = $skin_dir->read()) )
			return false;		// No subfolder
	} while( !( is_dir($skin_path.'/'.$skin_name) ) || ($skin_name=='..') );
	
	return $skin_name;
}


/*
 * skin_list_next(-)
 * 
 * Next skin iteration
 *
 * fplanque: created
 */
function skin_list_next()
{
	global $skin_path, $skin_dir, $skin_name;

	do
	{
		if( !($skin_name = $skin_dir->read()) )
			return false;		// No subfolder
	} while( !( is_dir($skin_path.'/'.$skin_name) ) || ($skin_name=='..') );
	
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
	echo bloginfo('blogurl').'?skin='.rawurlencode(skin_list_iteminfo('name',false));
}

?>