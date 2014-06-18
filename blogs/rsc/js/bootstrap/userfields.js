/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 * @version $Id: userfields.js 3901 2013-06-03 13:08:29Z yura $
 */

jQuery( document ).ready(function()
{
	/** Init popover for User Fields with Multiple Values **/

	var field_number = 1;
	jQuery( document ).on( 'focus', '[rel^=ufdf_]', function()
	{ // Prepare event focus for an element with popover effect
		var field = jQuery( this );
		var div_cache_ID = '';

		if( !field.hasClass( 'popoverfield' ) )
		{
			var field_ID = field.attr( 'rel' ).replace( 'ufdf_', '' );
			var div_cache_ID = 'popover_cache_field_' + field_ID;
		}

		if( div_cache_ID != '' )
		{ // Init popover for the first time event "focus"
			var popover_params = {
				trigger: 'focus',
				placement: 'right',
				html: true,
				template: '<div class="popover popover-userfield"><div class="arrow"></div><div class="popover-content"></div></div>'
			};
			if( jQuery( '#' + div_cache_ID ).length == 0 )
			{ // Create a div for cache user data
				jQuery( 'body' ).append( '<div id="' + div_cache_ID + '" style="display:none"></div>' );
				var cache = jQuery( '#' + div_cache_ID );

				jQuery.ajax(
				{ // Get field info
					type: 'POST',
					url: htsrv_url + 'anon_async.php',
					data: 'action=get_field_bubbletip' + '&field_ID=' + field_ID + '&use_glyphicons=1',
					success: function( result )
					{ // If success request - fill div with field data, save same data to the cache, init popover
						if( ajax_response_is_correct( result ) )
						{ // Init Popover only if ajax content is received
							result = ajax_debug_clear( result );
							cache.html( result );
							var show_on_init = true;
							if( field.hasClass( 'popoverfield' ) )
							{	// We use this class as flag to understand that when ajax was loading
								// the cursor pointer already left out this element
								// and we don't need to show a popover on init event
								show_on_init = false;
								field.removeClass( 'popoverfield' );
							}
							popover_params.content = cache.html();
							field.popover( popover_params );
							if( show_on_init )
							{ // Show popover
								field.popover( 'show' );
							}
						}
						field.addClass( 'popoverfield' );	// Add this class to avoid of the repeating of init popover
					}
				});
			}
			else
			{ // Init popover from cached element
				if( jQuery( '#' + div_cache_ID ).html() != '' )
				{ // Ajax content is downloaded and we can show a popover
					popover_params.content = jQuery( '#' + div_cache_ID ).html();
					field.popover( popover_params );
					field.popover( 'show' );
					// Add this class to avoid of the repeating of init popover
					field.addClass( 'popoverfield' );
				}
			}
			field_number++;
		}
	} );

	jQuery( document ).on( 'blur', '[rel^=ufdf_]', function()
	{ // This class-flag is used to know that cursor pointer is leaving this element
		jQuery( this ).addClass( 'popoverfield' );
	} );

	jQuery( document ).on( 'click', '.popover-userfield', function()
	{ // Copy a click event from 'plus' button to popover window
		jQuery( this ).next().find( 'span[class*="icon"]:first' ).click();
	} );
} );