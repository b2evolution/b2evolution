/**
 * This file is used for customizer mode
 */

jQuery( document ).on( 'ready', function()
{
	jQuery( '.evo_customizer__left iframe' ).on( 'load', function()
	{	// If iframe with settings has been loaded
		if( jQuery( this ).contents().find( '.alert.alert-success' ).length )
		{	// Reload iframe with collection preview if the settings iframe has a message about success updating:
			jQuery( '.evo_customizer__right iframe' ).get(0).contentDocument.location.reload();
		}
	} );

	jQuery( '.evo_customizer__right iframe' ).on( 'load', function()
	{	// If iframe with collection preview has been loaded
		jQuery( this ).contents().find( 'a' ).each( function()
		{
			var link_url = jQuery( this ).attr( 'href' );
			var collection_url = jQuery( '.evo_customizer__right iframe' ).data( 'coll-url' );
			if( typeof( link_url ) != 'undefined' && link_url.indexOf( collection_url ) === 0 )
			{	// Append param to hide evo toolbar and don't redirect for links of the current collection:
				jQuery( this ).attr( 'href', link_url + ( link_url.indexOf( '?' ) === -1 ? '?' : '&' ) + 'show_evo_toolbar=0&redir=no' );
			}
			else
			{	// Open all links of other collections and side sites on top window in order to update settings frame or close it:
				jQuery( this ).attr( 'target', '_top' );
			}
		} );
	} );
} );