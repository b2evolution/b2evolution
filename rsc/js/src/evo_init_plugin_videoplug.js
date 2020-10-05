/**
 * This file initialize plugin "Video Plug"
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 * 
 * Depends on jQuery
 */

jQuery( document ).ready( function()
{
	if( typeof( evo_plugin_videoplug_config ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	// Initialize YouTube player for Lazy-Loading:
	jQuery( evo_plugin_videoplug_config.youtube_lazyload_selector ).each( function()
	{	// Load a preview image for the video:
		var this_player = jQuery( this );
		var image = new Image();
		image.src = 'https://img.youtube.com/vi/' + this_player.data( 'embed' ) + '/sddefault.jpg';
		image.addEventListener( 'load', function()
		{
			this_player.append( image );
		} );
	} );
	jQuery( evo_plugin_videoplug_config.youtube_lazyload_selector ).click( function()
	{	// Initialize iframe on click to the preview player area:
		var iframe = document.createElement( 'iframe' );
		iframe.setAttribute( 'frameborder', '0' );
		iframe.setAttribute( 'allowfullscreen', '' );
		iframe.setAttribute( 'allow', 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture' );
		iframe.setAttribute( 'src', 'https://www.youtube.com/embed/' + jQuery( this ).data( 'embed' ) + '?rel=0&showinfo=0&autoplay=1' );
		jQuery( this ).html( iframe );
	} );
} );