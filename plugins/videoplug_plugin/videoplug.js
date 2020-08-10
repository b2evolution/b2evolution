/* 
 * JavaSript for plugin Video Plug
 */

jQuery( document ).ready( function()
{
	// Initialize YouTube player for Lazy-Loading:
	jQuery( '.evo_youtube[data-embed]' ).each( function()
	{	// Load a preview image for the video:
		var this_player = jQuery( this );
		var image = new Image();
		image.src = 'https://img.youtube.com/vi/' + this_player.data( 'embed' ) + '/sddefault.jpg';
		image.addEventListener( 'load', function()
		{
			this_player.append( image );
		} );
	} );
	jQuery( '.evo_youtube[data-embed]' ).click( function()
	{	// Initialize iframe on click to the preview player area:
		var iframe = document.createElement( 'iframe' );
		iframe.setAttribute( 'frameborder', '0' );
		iframe.setAttribute( 'allowfullscreen', '' );
		iframe.setAttribute( 'allow', 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture' );
		iframe.setAttribute( 'src', 'https://www.youtube.com/embed/' + jQuery( this ).data( 'embed' ) + '?rel=0&showinfo=0&autoplay=1' );
		jQuery( this ).html( iframe );
	} );
} );