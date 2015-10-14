/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * Used only to initialize colorbox for the links with attribute "rel" ^= "lightbox"
 * Don't load this file directly, It is appended to "/build/jquery.colorbox.b2evo.min.js" by Grunt, because we load colorbox plugin asynchronously.
 */

/**
 * Initialize colorbox for a selected link
 *
 * @param object jQuery object
 */
function init_colorbox( this_obj )
{
	if( typeof( this_obj ) != 'object' || this_obj.length == 0 )
	{ // Nothing to initialize
		return;
	}

	var type = this_obj.attr( 'rel' ).match( /lightbox\[([a-z]+)/i );
	type = type ? type[1] : '';
	switch( type[1] )
	{
		case 'p': // post
			this_obj.colorbox( b2evo_colorbox_params_post );
			break;
		case 'c': // comment
			this_obj.colorbox( b2evo_colorbox_params_cmnt );
			break;
		case 'user': // user
			this_obj.colorbox( b2evo_colorbox_params_user );
			break;
		default: // all others
			this_obj.colorbox( b2evo_colorbox_params );
	}
}
jQuery( document ).ready( function()
{
	jQuery( 'a[rel^="lightbox"]' ).each( function()
	{
		init_colorbox( jQuery( this ) );
	} );

	jQuery( '#colorbox' ).swipe(
	{ // Use swipe plugin for touch devices
		swipeLeft: function( event, direction, distance, duration, fingerCount )
		{
			if( typeof( colorbox_is_zoomed ) == 'undefined' || ! colorbox_is_zoomed )
			{	// Don't switch to next image when current is zoomed:
				jQuery.colorbox.next();
			}
		},
		swipeRight: function( event, direction, distance, duration, fingerCount )
		{
			if( typeof( colorbox_is_zoomed ) == 'undefined' || ! colorbox_is_zoomed )
			{	// Don't switch to previous image when current is zoomed:
				jQuery.colorbox.prev();
			}
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
