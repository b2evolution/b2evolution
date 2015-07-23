/**
 * This file is used to initialize tooltips for error form fields
 */

/**
 * Prepare event focus for a form field with popover effect
 *
 * @param object This object
 * @param string Popover placement: 'top', 'right', 'bottom', 'left'
 * @param string JS trigger to show popover: 'focus', 'hover'
 */
function form_error_field_popover( this_obj, placement, trigger )
{
	if( jQuery( this_obj ).hasClass( 'popovererror' ) )
	{ // Popover is already initialized on this form field
		return true;
	}

	var tip_text = jQuery( 'span.field_error[rel="' + jQuery( this_obj ).attr( 'name' ) + '"]' ).html();

	// Add this class to avoid of the repeating of init popover:
	jQuery( this_obj ).addClass( 'popovererror' );

	if( tip_text == '' )
	{ // Skip field without error message:
		return true;
	}

	jQuery( this_obj ).popover(
	{
		trigger: trigger,
		placement: placement,
		html: true,
		content: '<span class="field_error">' + tip_text + '</span>',
	} );
	jQuery( this_obj ).popover( 'show' );
}

// Set what events use to display a tooltip depending on device type:
if( 'ontouchstart' in window )
{ // Touch devices (without mouse cursor)
	var form_error_field_event = 'focus';
	var form_error_field_trigger = 'focus';
}
else
{ // Normal devices (with mouse cursor)
	var form_error_field_event = 'mouseover';
	var form_error_field_trigger = 'hover';
}

// Prepare event focus for the form fields with popover effect:
jQuery( document ).on( form_error_field_event, 'input.field_error[type=text], input.field_error[type=radio], span.checkbox_error input[type=checkbox]', function()
{ // <input type="text|radio|checkbox">
	form_error_field_popover( this, 'bottom', form_error_field_trigger );
} );

jQuery( document ).on( form_error_field_event, 'select.field_error, textarea.field_error', function()
{ // <select> and <textarea>
	form_error_field_popover( this, 'top', form_error_field_trigger );
} );