/**
 * This file implements the shortcut keys initialization
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 */


/**
 * Handles the shortcut key press
 * @param string key pressed 
 */
function shortcut_handler( key )
{
	var shortcuts = jQuery( '[data-shortcut^="' + key + '"],[data-shortcut*=",' + key + '"]' );
	if( shortcuts.length )
	{
		var el = shortcuts[0];
		switch( el.tagName )
		{
			case 'INPUT':
				switch( el.type )
				{
					case 'hidden':
						// do nothing
						break;
					
					case 'button':
					case 'reset':
					case 'search':
					case 'submit':
						el.click();
						break;
					
					default:
						el.focus();
				}
				break;

			case 'BUTTON':
			case 'A':
				el.click();
				break;

			default:
				// No applicable action - flash evo_toolbar!
				evobarFlash( [ '#ff0000' ] );
		}
	}
	else
	{
		// No applicable action - flash evo_toolbar!
		evobarFlash( [ '#ff0000' ] );
	}

	return false;
}


jQuery( document ).ready( function() {

	// console.info( 'Init Hotkeys script...' );

	// Get available shortcut keys:
	var top_shortcut_keys = window.top_shortcut_keys || [];
	var shortcut_keys = window.shortcut_keys || [];
	var shortcuts = jQuery( '[data-shortcut]' );
	if( shortcuts.length )
	{
		shortcuts.each( function( index ) {
				var s = $( this ).data( 'shortcut' ).split( ',' ).map( function( n ) {
					return n.trim();
				} );

				for( var i = 0; i < s.length; i++ )
				{
					if( shortcut_keys.indexOf( s[i] ) == -1 )
					{
						shortcut_keys.push( s[i] );
					}
				}
			} );
	}

	if( window.self !== window.top )
	{	// Get available top shortcut keys. Keys included in this list will be sent directly to the top window:
		var top_shortcut_keys = window.top['top_shortcut_keys'];

		// Add top shortcut keys to list of shortcut keys:
		for( var i = 0; i < top_shortcut_keys.length; i++ )
		{
			if( shortcut_keys.indexOf( top_shortcut_keys[i] ) == -1 )
			{
				shortcut_keys.push( top_shortcut_keys[i] );
			}
		}
	}
	else
	{
		var top_shortcuts = jQuery( '[data-shortcut-top]' );
		if( top_shortcuts.length )
		{
			top_shortcuts.each( function( index ) {
				var s = $( this ).data( 'shortcut-top' ).split( ',' ).map( function( n ) {
					return n.trim();
				} );

				for( var i = 0; i < s.length; i++ )
				{
					if( top_shortcut_keys.indexOf( s[i] ) == -1 )
					{
						top_shortcut_keys.push( s[i] );
					}
				}
			} );
		}
	}

	// console.info( 'Hotkeys found:', shortcut_keys, top_shortcut_keys );

	// Enable hotkeys even inside INPUT, SELECT, TEXTAREA elements:
	hotkeys.filter = function( event ) {
			return true;
		}

	hotkeys( shortcut_keys.join( ',' ), function( event, handler ) {
			
			// Check if key should be sent to the top window:
			if( window.self !== window.top )
			{
				if( top_shortcut_keys
					&& ( top_shortcut_keys.indexOf( handler.key ) != -1 )
					&& window.top['shortcut_handler']
					&& ( typeof( window.top['shortcut_handler'] ) == 'function' ) )
				{
					return window.top['shortcut_handler']( handler.key );
				}
			}

			return window['shortcut_handler']( handler.key );
		} );
} );
