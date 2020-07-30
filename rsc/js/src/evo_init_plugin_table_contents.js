/**
 * This file initialize plugin "Table Contents"
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
	if( typeof( evo_plugin_table_contents_settings ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	var evo_toolbar_height = jQuery( '#evo_toolbar' ).length ? jQuery( '#evo_toolbar' ).height() : 0;
	jQuery( '.evo_plugin__table_of_contents a' ).on( 'click', function()
		{
			var header_object = jQuery( '#' + jQuery( this ).data( 'anchor' ) );
			if( header_object.length == 0 ||
				! header_object.prop( 'tagName' ).match( /^h[1-6]$/i ) )
			{	// No header tag with required anchor:
				return true;
			}
			var link_href = jQuery( this ).attr( 'href' );
			jQuery( 'html,body' ).animate(
			{	// Scroll to anchor:
				scrollTop: header_object.offset().top - evo_toolbar_height - evo_plugin_table_contents_settings.offset_scroll
			},
			function()
			{	// Update URL with proper anchor in browser address bar:
				window.history.pushState( '', '', link_href );
			} );
			return false;
		} );
} );