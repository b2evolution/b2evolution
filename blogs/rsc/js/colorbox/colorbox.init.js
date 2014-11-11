/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * Used only to initialize colorbox for the links with attribute "rel" ^= "lightbox"
 * Don't load this file directly, It is appended to "/build/jquery.colorbox.b2evo.min.js" by Grunt, because we load colorbox plugin asynchronously.
 *
 * @version $Id: colorbox.init.js 7363 2014-10-06 05:05:13Z yura $
 */
jQuery( document ).ready( function()
{
	jQuery( 'a[rel^="lightbox"]' ).colorbox( b2evo_colorbox_params );

	jQuery( '#colorbox' ).swipe(
	{ // Use swipe plugin for touch devices
		swipeLeft: function( event, direction, distance, duration, fingerCount )
		{
			jQuery.colorbox.next();
		},
		swipeRight: function( event, direction, distance, duration, fingerCount )
		{
			jQuery.colorbox.prev();
		},
	} );
	jQuery( document ).on( 'click', '#colorbox img.cboxPhoto', function()
	{
		if( jQuery( this ).hasClass( 'zoomout' ) )
		{ // Disable swipe plugin when picture is zoomed
			jQuery( '#colorbox' ).swipe( 'disable' );
		}
		else
		{ // Re-enable swipe plugin
			jQuery( '#colorbox' ).swipe( 'enable' );
		}
	} );
} );