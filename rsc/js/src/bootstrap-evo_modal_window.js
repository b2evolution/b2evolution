/**
 * This file contains functions to open/close modal windows
 * (ONLY for bootstrap skins)
 */


var modal_window_js_initialized = false;

/*
 * Build and open madal window
 *
 * @param string HTML content
 * @param string Width value in css format
 * @param boolean TRUE - to use transparent template
 * @param string Title of modal window (Used in bootstrap)
 * @param string|boolean Button to submit a form (Used in bootstrap), FALSE - to hide bottom panel with buttons
 * @param boolean FALSE by default, TRUE - to don't remove bootstrap panels
 * @param boolean TRUE - to clear all previous windows
 * @param string ID of iframe where all contents
 */
function openModalWindow( body_html, width, height, transparent, title, buttons, is_new_window, keep_panels, iframe_id )
{
	var style_width = ( typeof( width ) == 'undefined' || width == 'auto' ) ? '' : 'width:' + width + ';';
	var style_height = ( typeof( height ) == 'undefined' || height == 0 || height == '' ) ? '': 'height:' + height;
	var style_height_fixed = style_height.match( /%$/i ) ? ' style="height:100%;overflow:hidden;"' : '';
	var style_body_height = height.match( /px/i ) ? ' style="min-height:' + ( height.replace( 'px', '' ) - 157 ) + 'px"' : '';
	var use_buttons = ( typeof( buttons ) == 'undefined' || buttons != false );

	if( typeof( buttons ) != 'undefined' && buttons != '' )
	{
		if( typeof( buttons ) == 'object' )
		{ // Specific button with params
			var button_title = buttons[0];
			var button_class = buttons[1];
			var button_form = typeof( buttons[2] ) == 'undefined' ? 'form' : buttons[2];
		}
		else
		{ // Standard button to submit a single form
			var button_title = buttons;
			var button_class = 'btn-primary';
			var button_form = 'form';
		}
	}

	if( typeof( is_new_window ) != 'undefined' && is_new_window )
	{ // Clear previous opened window
		jQuery( '#modal_window' ).remove();
	}

	if( jQuery( '#modal_window' ).length == 0 )
	{ // Build modal window
		var modal_html = '<div id="modal_window" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog" style="' + style_width + style_height +'"><div class="modal-content"' + style_height_fixed + '>';
		if( typeof title != 'undefined' && title != '' )
		{
			modal_html += '<div class="modal-header">' +
					'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' +
					'<h4 class="modal-title">' + title + '</h4>' +
				'</div>';
		}
		modal_html += '<div class="modal-body"' + style_height_fixed + style_body_height + '>' + body_html;

		if( iframe_id )
		{
			var iframe = jQuery( '#' + iframe_id );
			modal_html += '<script>'
					+ 'jQuery( document ).ready( function() {'
					+ 'var iframe = jQuery( \'#' + iframe_id + '\' );'
					+ 'iframe.on( \'load\', function() {'
					+			'iframe.closest( \'.modal-body\' ).find( \'span.loader_img\' ).remove();'
					+			'setModalIFrameUnload( \'' + iframe_id + '\' );'
					+		'});'
					+ '});'
					+ '</script>';
		}

		modal_html += '</div>';

		if( use_buttons )
		{
			modal_html += '<div class="modal-footer">';
			if( typeof( buttons ) != 'undefined' && buttons != '' )
			{
				modal_html += '<button class="btn ' + button_class + '" type="submit" style="display:none">' + button_title + '</button>';
			}
			modal_html += '<button class="btn btn-default" data-dismiss="modal" aria-hidden="true">' + evo_js_lang_close + '</button></div>';
		}
		modal_html += '</div></div></div>';
		jQuery( 'body' ).append( modal_html );
	}
	else
	{ // Use existing modal window
		jQuery( '#modal_window .modal-body' ).html( body_html );
	}

	if( typeof( iframe_id ) != 'undefined' )
	{
		jQuery( '#' + iframe_id ).load( function()
		{	// Prepare modal window only after loading full content:
			prepareModalWindow( jQuery( this ).contents(), button_form, use_buttons, keep_panels );
			jQuery( '#modal_window .loader_img' ).remove();
			jQuery( '#' + iframe_id ).show();
		} );
	}
	else
	{
		prepareModalWindow( '#modal_window', button_form, use_buttons, keep_panels );
	}

	// Init modal window and show
	var options = {};
	if( modal_window_js_initialized )
	{
		options = 'show';
	}
	jQuery( '#modal_window' ).modal( options );
	if( style_width == '' )
	{
		jQuery( '#modal_window .modal-dialog' ).css( { 'display': 'table', 'width': 'auto' } );
		jQuery( '#modal_window .modal-dialog .modal-content' ).css( { 'display': 'table-cell' } );
	}

	jQuery( '#modal_window').on( 'hidden', function ()
	{ // Remove modal window on hide event to draw new window in next time with new title and button
		jQuery( this ).remove();
	} );

	modal_window_js_initialized = true;
}

function prepareModalWindow( modal_document, button_form, use_buttons, keep_panels )
{
	if( use_buttons )
	{
		if( typeof( keep_panels ) == 'undefined' || ! keep_panels )
		{ // Remove these elements, they are displayed as title and button of modal window
			jQuery( 'legend', modal_document ).remove();
			jQuery( '#close_button', modal_document ).remove();
			jQuery( '.panel, .panel-body', modal_document ).removeClass( 'panel panel-default panel-body' );
		}

		if( jQuery( button_form + ' input[type=submit]', modal_document ).length == 0 )
		{ // Hide a submit button in the footer if real submit input doesn't exist
			jQuery( '#modal_window .modal-footer button[type=submit]' ).hide();
		}
		else
		{
			jQuery( button_form + ' input[type=submit]', modal_document ).hide();
			jQuery( '#modal_window .modal-footer button[type=submit]' ).show();
		}

		jQuery( button_form, modal_document ).change( function()
		{ // Find the submit inputs when html is changed
			var input_submit = jQuery( this ).find( 'input[type=submit]' )
			if( input_submit.length > 0 )
			{ // Hide a real submit input and Show button of footer
				input_submit.hide();
				jQuery( '#modal_window .modal-footer button[type=submit]' ).show();
			}
			else
			{ // Hide button of footer if real submit input doesn't exist
				jQuery( '#modal_window .modal-footer button[type=submit]' ).hide();
			}
		} );

		jQuery( '#modal_window .modal-footer button[type=submit]' ).click( function()
		{ // Copy a click event from real submit input to button of footer
			jQuery( button_form + ' input[type=submit]', modal_document ).click();
		} );
	}

	jQuery( button_form + ' a.btn', modal_document ).each( function()
	{ // Move all buttons to the footer
		jQuery( '#modal_window .modal-footer' ).prepend( '<a href=' + jQuery( this ).attr( 'href' ) + '>' +
			'<button type="button" class="' + jQuery( this ).attr( 'class' ) + '">' +
			jQuery( this ).html() +
			'</button></a>' );
		jQuery( this ).remove();
	} );

	if( jQuery( button_form + ' #current_modal_title', modal_document ).length > 0 )
	{ // Change window title
		jQuery( '#modal_window .modal-title' ).html( jQuery( button_form + ' #current_modal_title', modal_document ).html() );
	}
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

	jQuery( '#modal_window', document_obj ).modal( 'hide' );

	return false;
}

function setModalIFrameUnload( iframe_id )
{
	var iframe = jQuery( '#' + iframe_id );
	iframe[0].contentWindow.onunload = function()
		{
			var modal_body = iframe.closest( '.modal-body' );
			var spinner = jQuery( '<span class="loader_img absolute_center" title="' + evo_js_lang_loading + '"></span>' );
			jQuery( modal_body ).prepend( spinner );
		}
}