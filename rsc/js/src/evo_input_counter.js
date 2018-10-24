/* 
 * This file contains general functions to display a characters counter on the input/textarea fields
 */

jQuery( document ).ready( function()
{
	var evo_input_maxlength_counter_selector = 'input[type=text][data-maxlength], textarea[data-maxlength]';

	jQuery( evo_input_maxlength_counter_selector ).each( function()
	{	// Initialize counter element for each input where it is required:
		jQuery( this ).after( '<span class="evo_input_maxlength_counter">' + ( jQuery( this ).data( 'maxlength' ) - jQuery( this ).val().length ) + '</span>' );
		// Set relative position of the parent element for proper position of the counter elements:
		jQuery( this ).parent().css( 'position', 'relative' );
		// Store original right padding because it may be changed depending on counter width:
		jQuery( this ).data( 'padding-right', parseInt( jQuery( this ).css( 'padding-right' ) ) );
		// Set counter position:
		evo_input_maxlength_counter_update_position( jQuery( this ) );
	} );

	jQuery( evo_input_maxlength_counter_selector ).keyup( function()
	{	// Update counter value on key up:
		var counter_obj = jQuery( this ).next( '.evo_input_maxlength_counter' );
		if( counter_obj.length == 0 )
		{	// Skip wrong input without counter element:
			return;
		}
		var prev_counter_length = counter_obj.html().length;
		counter_obj.html( jQuery( this ).data( 'maxlength' ) - jQuery( this ).val().length );
		if( prev_counter_length != counter_obj.html().length )
		{	// Update counter position when its width was changed:
			evo_input_maxlength_counter_update_position( jQuery( this ) );
		}
	} );

	jQuery( window ).resize( function()
	{	// Update positions of all initialized counters on window resize:
		jQuery( evo_input_maxlength_counter_selector ).each( function()
		{
			evo_input_maxlength_counter_update_position( jQuery( this ) );
		} );
	} );

	function evo_input_maxlength_counter_update_position( input_obj )
	{
		var counter_obj = input_obj.next( '.evo_input_maxlength_counter' );
		if( counter_obj.length == 0 )
		{	// Skip wrong input without counter element:
			return;
		}

		// Calculate top position for counter element:
		// - for <input> use middle vertical alignemnt,
		// - for <textarea> use bottom vertical alignemnt.
		var valign_size = ( input_obj.outerHeight( true ) - parseInt( counter_obj.css( 'line-height' ) ) ) / 2;
		var top = input_obj.position().top + valign_size;
		if( input_obj.prop( 'tagName' ) == 'TEXTAREA' )
		{
			top += valign_size - parseInt( input_obj.css( 'padding-bottom' ) );
		}

		// Set position for counter element:
		counter_obj.css( {
			'top': top,
			'left': input_obj.position().left + input_obj.outerWidth( true ) - counter_obj.outerWidth( true ) - ( input_obj.data( 'padding-right' ) / 2 ),
		} );

		// Update right padding of the input element depending on counter width and source right padding size:
		input_obj.css( 'padding-right', input_obj.data( 'padding-right' ) + counter_obj.outerWidth( true ) );
	}
} );