/**
 * This file is used to initialize tooltips for error form fields
 */

/**
 * Prepare event focus for a form field with popover effect
 *
 * @param object This object
 */
function form_error_field_popover( this_obj )
{
	if( jQuery( this_obj ).hasClass( 'popovererror' ) )
	{ // Popover is already initialized on this form field
		return true;
	}

	var tip_text = jQuery( 'span.field_error[rel="' + jQuery( this_obj ).attr( 'name' ) + '"]' ).html();

	if( tip_text == '' )
	{ // Skip field without error message:
		return true;
	}

	var tag_name = jQuery( this_obj ).prop( 'tagName' );

	// Destroy it before to avoid issues when a field is already focused:
	jQuery( this_obj ).popover( 'destroy' );

	// Initialize the popover:
	if( tip_text )
	{ // Only do this if there is actually something to display
		jQuery( this_obj ).popover(
		{
			trigger: 'manual',
			placement: ( tag_name == 'SELECT' || tag_name == 'TEXTAREA' ) ? 'top' : 'bottom',
			html: true,
			content: '<span class="field_error">' + tip_text + '</span>',
		} );
	}

	jQuery( this_obj ).on( 'show.bs.popover', function()
	{ // Add this class to avoid of the repeating of init popover:
		jQuery( this_obj ).addClass( 'popovererror' );
	} );
}

// Prepare event focus for the form fields with popover effect:
var form_error_fields_selector = 'input.field_error[type=text], input.field_error[type=radio], span.checkbox_error input[type=checkbox], select.field_error, textarea.field_error, input.field_error[type=file]';
jQuery( document ).on( 'mouseover focus', form_error_fields_selector, function()
{ // Initialize popover only on first event calling:
	form_error_field_popover( this );
	if( jQuery( this ).next( 'div.popover:visible' ).length == 0 )
	{ // Show popover only if is not visible yet:
		jQuery( this ).popover( 'show' );
	}
} )
.on( 'mouseout mouseleave blur', form_error_fields_selector, function()
{
	if( ! jQuery( this ).is( ':focus' ) )
	{ // Hide popover only if this field is not focused now:
		jQuery( this ).popover( 'hide' );
	}
} );