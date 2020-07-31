/* 
 * This file contains general functions to work with files
 */


// Collapse/Expand sub-directories in Files Browser:
// (sub-directories are loaded by AJAX if their parent directory is not opened on page load)
jQuery( document ).on( 'click', '[data-dir-path]', function()
{
	// Change icon of collapsed/expanded dir status:
	var clickimg = jQuery( this );
	if( clickimg.hasClass( 'fa' ) || clickimg.hasClass( 'glyphicon' ) )
	{	// Fontawesome icon | Glyph bootstrap icon
		if( clickimg.data( 'toggle' ) != '' )
		{	// This icon has a class name to toggle
			var icon_prefix = ( clickimg.hasClass( 'fa' ) ? 'fa' : 'glyphicon' );
			if( clickimg.data( 'toggle-orig-class' ) == undefined )
			{	// Store original class name in data
				clickimg.data( 'toggle-orig-class', clickimg.attr( 'class' ).replace( new RegExp( '^'+icon_prefix+' (.+)$', 'g' ), '$1' ) );
			}
			if( clickimg.hasClass( clickimg.data( 'toggle-orig-class' ) ) )
			{	// Replace original class name with exnpanded
				clickimg.removeClass( clickimg.data( 'toggle-orig-class' ) )
					.addClass( icon_prefix + '-' + clickimg.data( 'toggle' ) );
			}
			else
			{	// Revert back original class
				clickimg.removeClass( icon_prefix + '-' + clickimg.data( 'toggle' ) )
					.addClass( clickimg.data( 'toggle-orig-class' ) );
			}
		}
	}
	else
	{	// Sprite icon
		var xy = clickimg.css( 'background-position' ).match( /-*\d+/g );
		// Shift background position to the right/left to the one icon in the sprite
		clickimg.css( 'background-position', ( parseInt( xy[0] ) + ( hide ? 16 : - 16 ) ) + 'px ' + parseInt( xy[1] ) + 'px' );
	}

	// Display/Load sub-directories:
	var dir = jQuery( this ).parent();
	var subdirs = dir.next( 'ul' );

	if( subdirs.length )
	{	// Collapse/Expand already loaded sub-directories:
		subdirs.toggle();
	}
	else
	{	// Load sub-directories by AJAX:
		dir.after( '<ul class="clicktree"><span class="loader_img"></span></ul>' );
		jQuery.ajax(
		{
			type: 'POST',
			url: htsrv_url + 'async.php',
			data:
			{
				'action': 'browse_subdirs',
				'path': jQuery( this ).data( 'dir-path' ),
				'b2evo_icons_type': b2evo_icons_type,
			},
			success: function( result )
			{	// Display sub-directories:
				dir.next( 'ul' ).html( ajax_debug_clear( result ) );
			},
			error: function( jqXHR )
			{	// Display error:
				dir.next( 'ul' ).html( jqXHR.responseText );
			}
		} );
	}
} );