/* 
 * This file contains general functions to work with images
 */


jQuery( document ).ready( function()
{
	// Remove css spinner from images that are already loaded:
	jQuery( 'img.loadimg' ).each( function()
	{
		if( jQuery( this ).prop( 'complete' ) )
		{	// If image is loaded from cache:
			jQuery( this ).addClass( 'loadimg_loaded' );
		}
		else
		{	// After image has been loaded:
			jQuery( this ).on( 'load', function()
			{
				jQuery( this ).addClass( 'loadimg_loaded' );
			} );
		}
	} );
} );