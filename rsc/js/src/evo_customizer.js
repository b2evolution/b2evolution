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
} );