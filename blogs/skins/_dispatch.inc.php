<?php
/**
 * This is the template that dipatches display of the main area, based on the disp param
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the _main.php template.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( $disp != 'posts' && $disp != 'single' )
{ // We must display a sub template:
	$disp_handlers = array(
			'arcdir'   => '_arcdir.php',
			'comments' => '_lastcomments.php',
			'msgform'  => '_msgform.php',
			'profile'  => '_profile.php',
			'subs'     => '_subscriptions.php',
		);

	if( empty( $disp_handlers[$disp] ) )
	{
		debug_die( 'Unhandled disp type ['.$disp.']' );
	}
		
	$disp_handler = $disp_handlers[$disp];
		
	if( file_exists( $current_skin_includes_path.$disp_handler ) )
	{	// The skin has a customized handler for this display:
		require $current_skin_includes_path.$disp_handler;
	}
	else
	{	// Use the default handler from the skins dir:
		require $skins_path.$disp_handler;
	}
}


// Sponsored links (if you don't mind, please don't move them):
if( !isset( $use_sponsored_links ) || !empty( $use_sponsored_links ) )
{
	echo '<ul class="evo_sponsored_links">';
	foreach( $sponsored_links as $sponsored_link )
	{
		echo '<li><a href="'.$sponsored_link[0].'">'.$sponsored_link[1].'</a></li>';
	}
	echo '</ul>';
}

/*
 * $Log$
 * Revision 1.1  2006/07/24 00:05:44  fplanque
 * cleaned up skins
 *
 */
?>