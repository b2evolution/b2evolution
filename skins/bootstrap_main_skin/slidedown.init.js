/**
 * This file initialize Slide Down
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 */

// Smooth scroll to top:
jQuery( '#slide_button' ).on( 'click', function( event )
{
	event.preventDefault();
	jQuery( 'body, html, #skin_wrapper' ).animate( {
		scrollTop: jQuery( '#slide_destination' ).offset().top + 26
	}, 1000 );
} );

jQuery( document ).ready( function()
{
	// Check if .slide-top div exists (used to name back-to-top button)
	if( jQuery( '.slide-top' ).length ) {
		// Scroll to Top
		// This skin needs to override the default scroll-top script because the `height: 100%` and `overflow: hidden` both exist on disp=front
		// ======================================================================== /
		// hide or show the "scroll to top" link
		jQuery( 'body, html, #skin_wrapper' ).scroll( function()
		{
			( jQuery( this ).scrollTop() > offset ) ? jQuery( '.slide-top' ).addClass( 'slide-top-visible' ) : jQuery( '.slide-top' ).removeClass( 'slide-top-visible' );
		} );

		// Smooth scroll to top
		jQuery( '.slide-top' ).on( 'click', function( event )
		{
			event.preventDefault();
			jQuery( "body, html, #skin_wrapper" ).animate( {
				scrollTop: 0,
			}, scroll_top_duration );
		} );
	}
} );