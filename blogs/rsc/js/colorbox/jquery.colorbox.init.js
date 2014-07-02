/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * Used only to initialize colorbox for the links with attribute "rel" ^= "lightbox"
 * Don't load this file directly, It is appended to "/build/jquery.colorbox.b2evo.min.js" by Grunt, because we load colorbox plugin asynchronously.
 *
 * @version $Id: jquery.colorbox.init.js 7043 2014-07-02 08:35:45Z yura $
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
} );