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
	var $obj = jQuery( this_obj );

	if( $obj.hasClass( 'popovererror' ) )
	{ // Popover is already initialized on this form field
		return true;
	}

	var tip_text = jQuery( 'span.field_error[rel="' + $obj.attr( 'name' ) + '"]' ).html();

	if( tip_text == '' )
	{ // Skip field without error message:
		return true;
	}

	var tag_name = $obj.prop( 'tagName' );

	// Destroy it before to avoid issues when a field is already focused:
	$obj.popover( 'destroy' );

	if( $obj.prop( 'tagName' ) == 'INPUT' && $obj.hasClass( 'form_date_input' ) )
	{
		// This is a hack to force the datepicker to generate its content so we can get its height prior to its display
		// and properly determine its location
		try
		{
			$.datepicker._updateDatepicker( $.datepicker._getInst( this_obj ) );
		}
		catch( err )
		{
			console.error( 'Unable to generate datepicker content using private methods _updateDatepicker() and _getInst()' );
		}
	}

	// Initialize the popover:
	if( tip_text )
	{ // Only do this if there is actually something to display
		$obj.popover(
		{
			trigger: 'manual',
			placement: function() {
				var bottomCal; // calendar will be shown at the bottom of the input

				if( $obj.prop( 'tagName' ) == 'INPUT' && $obj.hasClass( 'form_date_input' ) )
				{
					var $window = jQuery( window ),
							$calDiv = jQuery( '#ui-datepicker-div' );

					if( $calDiv.is( ':visible' ) )
					{ // Calendar is visible, we will place the popover opposite that
						bottomCal = $calDiv.offset().top > $obj.offset().top;
					}
					else
					{ // Determine calendar position, calendar height should already be available by now
						var bottomSpace = $window.height() - ( ( $obj.offset().top - $window.scrollTop() ) +  $obj.outerHeight() );
						bottomCal = bottomSpace >= $calDiv.outerHeight();
					}
				}

				return ( tag_name == 'SELECT' || tag_name == 'TEXTAREA' || ( tag_name == 'INPUT' && bottomCal ) ) ? 'top' : 'bottom'
			},
			html: true,
			content: '<span class="field_error">' + tip_text + '</span>',
		} );
	}

	$obj.on( 'show.bs.popover', function()
	{ // Add this class to avoid of the repeating of init popover:
		$obj.addClass( 'popovererror' );
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