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


/**
 * Get shortcut keys defined in the current window
 */
function get_shortcut_keys( keys, selector, data_attr )
{
	var shortcuts = jQuery( selector );
	
	if( shortcuts.length )
	{
		shortcuts.each( function( index ) {
				var s = $( this ).data( data_attr ).split( ',' ).map( function( n ) {
					return n.trim();
				} );

				for( var i = 0; i < s.length; i++ )
				{
					if( keys.indexOf( s[i] ) == -1 )
					{
						keys.push( s[i] );
					}
				}
			} );
	}
}

jQuery( document ).ready( function()
{
	// console.info( 'Init Hotkeys script...' );

	// Get available shortcut keys:
	var top_shortcut_keys = window.top_shortcut_keys || [];
	var shortcut_keys = window.shortcut_keys || [];
	
	// console.info( 'Hotkeys already defined: ', shortcut_keys, top_shortcut_keys );

	get_shortcut_keys( shortcut_keys, '[data-shortcut]', 'shortcut' );

	if( window.self !== window.top )
	{	// Get available top shortcut keys. Keys included in this list will be sent directly to the top window:
		if( window.top.top_shortcut_keys )
		{
			top_shortcut_keys = window.top.top_shortcut_keys;
		}

		// Get top shortcuts defined in this window:
		// Caution! The top window might not know how to handle the keys added below.
		get_shortcut_keys( top_shortcut_keys, '[data-shortcut-top]', 'shortcut-top' );

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
		get_shortcut_keys( top_shortcut_keys, '[data-shortcut-top]', 'shortcut-top' );
	}

	// console.info( 'Hotkeys found' + ( window.self != window.top ? ' in ' + window.name : '' ) + ':' , shortcut_keys, top_shortcut_keys );

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
