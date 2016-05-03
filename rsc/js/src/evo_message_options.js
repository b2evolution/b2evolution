/**
 * This file is used to open modal window to display message options
 */


// Open modal window to display message options:
jQuery( document ).on( 'click', '#message_options_button', function()
{
	var message_options_block = jQuery( '<div>' ).html( jQuery( '#message_options_block' ).html() );
	console.log( message_options_block.html() );
	jQuery( '[id^=renderer_]', message_options_block ).each( function()
	{
		// Change id to allow update checkbox by click on label:
		jQuery( this ).attr( 'id', 'modal_' + jQuery( this ).attr( 'id' ) );
		// Set real checkbox status:
		if( jQuery( '#message_options_block #renderer_' + jQuery( this ).val() ).is( ':checked' ) )
		{
			jQuery( this ).attr( 'checked', 'checked' );
		}
		else
		{
			jQuery( this ).removeAttr( 'checked' );
		}
	} );
	jQuery( '[for^=renderer_]', message_options_block ).each( function()
	{	// Change "for" attribute to allow update checkbox by click on label:
		jQuery( this ).attr( 'for', 'modal_' + jQuery( this ).attr( 'for' ) );
	} );

	// Open modal window with message options:
	openModalWindow( message_options_block.html(),
		'', '', true,
		evo_js_lang_message_options, evo_js_lang_update_options, true );

	jQuery( '.modal-footer button[type=submit]' ).show();
} );

// Update message options:
jQuery( document ).on( 'click', '.modal-footer button[type=submit]', function()
{
	jQuery( '.modal-body [name="renderers[]"]' ).each( function()
	{
		var real_checkbox = jQuery( '#message_options_block #renderer_' + jQuery( this ).val() );
		if( real_checkbox.length == 0 )
		{	// No checkbox found on real form:
			return;
		}

		if( jQuery( this ).is( ':checked' ) != real_checkbox.is( ':checked' ) )
		{	// If option has been changed then update it on real form:
			real_checkbox.click();
		}
	} );

	// Close modal window after update options:
	closeModalWindow();
} );