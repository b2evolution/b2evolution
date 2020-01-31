jQuery( document ).ready( function() {

	// console.log( 'Init Hotkeys script...' );

	// Get available shortcut keys:
	var shortcut_keys = window.shortcut_keys || [];
	var shortcuts = jQuery( '[data-shortcut]' );
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

	//console.info( 'Hotkeys found:', shortcut_keys );

	hotkeys( shortcut_keys.join( ',' ), function( event, handler ) {
			var shortcuts = jQuery( '[data-shortcut*="' + handler.key + '"]' );
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
		} );
} );
