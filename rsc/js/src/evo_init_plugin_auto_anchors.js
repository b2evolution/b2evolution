/**
 * This file initialize plugin "Auto Anchors"
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
	if( typeof( evo_plugin_auto_anchors_settings ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	jQuery( 'h1, h2, h3, h4, h5, h6' ).each( function()
		{	// Append anchor link to header:
			if( jQuery( this ).attr( 'id' ) && jQuery( this ).hasClass( 'evo_auto_anchor_header' ) )
			{	// Only if it has id attribute and it was genereated by this plugin
				var current_url = location.href.replace( /#.+$/, '' ) + '#' + jQuery( this ).attr( 'id' );
				jQuery( this ).append( ' <a href="' + current_url + '" class="evo_auto_anchor_link"><span class="fa fa-link"></span></a>' );
			}
		} );

		var evo_toolbar_height = jQuery( '#evo_toolbar' ).length ? jQuery( '#evo_toolbar' ).height() : 0;
		jQuery( '.evo_auto_anchor_link' ).on( 'click', function()
		{
			var link_href = jQuery( this ).attr( 'href' );
			jQuery( 'html,body' ).animate(
			{	// Scroll to anchor:
				scrollTop: jQuery( this ).offset().top - evo_toolbar_height - evo_plugin_auto_anchors_settings.offset_scroll
			},
			function()
			{	// Update URL with proper anchor in browser address bar:
				window.history.pushState( '', '', link_href );
			} );
			return false;
		} );
} );