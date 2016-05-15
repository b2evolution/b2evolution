/* 
 * This file contains functions to open/close modal windows
 * (Only for NON bootstrap skins)
 */


/*
 * Build and open modal window
 *
 * @param string HTML content
 * @param string Width value in css format
 * @param string Height value in css format
 * @param boolean TRUE - to use transparent template
 * @param string Title of modal window (Used in bootstrap)
 * @param string|boolean Button to submit a form (Used in bootstrap), FALSE - to hide bottom panel with buttons
 */
function openModalWindow( body_html, width, height, transparent, title, button )
{
	var overlay_class = 'overlay_page_active';
	if( typeof transparent != 'undefined' && transparent == true )
	{
		overlay_class = 'overlay_page_active_transparent';
	}

	if( typeof width == 'undefined' )
	{
		width = '560px';
	}
	var style_height = '';
	if( typeof height != 'undefined' && ( height > 0 || height != '' ) )
	{
		style_height = ' style="height:' + height + '"';
	}
	if( jQuery( '#overlay_page' ).length > 0 )
	{ // placeholder already exist
		jQuery( '#overlay_page' ).html( body_html );
		return;
	}
	// add placeholder for form:
	jQuery( 'body' ).append( '<div id="screen_mask"></div><div id="overlay_wrap" style="width:' + width + '"><div id="overlay_layout"><div id="overlay_page"' + style_height + '></div></div></div>' );
	jQuery( '#screen_mask' ).fadeTo(1,0.5).fadeIn(200);
	jQuery( '#overlay_page' ).html( body_html ).addClass( overlay_class );
	jQuery( document ).on( 'click', '#close_button, #screen_mask, #overlay_page', function( e )
	{
		if( jQuery( this ).attr( 'id' ) == 'overlay_page' )
		{
			var form_obj = jQuery( '#overlay_page form' );
			if( form_obj.length )
			{
				var top = form_obj.position().top + jQuery( '#overlay_wrap' ).position().top;
				var bottom = top + form_obj.height();
				if( ! ( e.clientY > top && e.clientY < bottom ) )
				{
					closeModalWindow();
				}
			}
			return true;
		}
		closeModalWindow();
		return false;
	} );
}

/**
 * Close modal window
 *
 * @param object Document object
 */
function closeModalWindow( document_obj )
{
	if( typeof( document_obj ) == 'undefined' )
	{
		document_obj = window.document;
	}

	jQuery( '#overlay_page', document_obj ).hide();
	jQuery( '.action_messages', document_obj).remove();
	jQuery( '#server_messages', document_obj ).insertBefore( '.first_payload_block' );
	jQuery( '#overlay_wrap', document_obj ).remove();
	jQuery( '#screen_mask', document_obj ).remove();
	return false;
}

// Close ajax popup if Escape key is pressed:
jQuery( document ).keyup( function( e )
{
	if( e.keyCode == 27 )
	{
		closeModalWindow();
	}
} );