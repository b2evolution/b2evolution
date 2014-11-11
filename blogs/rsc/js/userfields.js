/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 * @version $Id: bubbletip.js 2362 2012-11-07 06:11:13Z yura $
 */

jQuery( document ).ready(function()
{
	/** Init bubbletip for User Fields with Multiple Values **/

	var field_number = 1;
	jQuery( document ).on( 'focus', '[rel^=ufdf_]', function()
	{	// Prepare event focus for an element with bubbletip effect
		var field = jQuery( this );
		var div_cache_ID = '';

		if( !field.hasClass( 'bubblefield' ) )
		{
			var field_ID = field.attr( 'rel' ).replace( 'ufdf_', '' );
			var div_cache_ID = 'bubble_cache_field_' + field_ID;
		}

		if( div_cache_ID != '' )
		{	// Init bubbletip for the first time event "focus"
			var div_bubbletip_ID = 'bubbletip_field_' + field_number;

			jQuery( 'body' ).append( '<div id="' + div_bubbletip_ID + '" style="display:none;"></div>' );

			var bubbletip_params = {
					bindShow: 'focus',
					bindHide: 'blur',
					calculateOnShow: true,
					showOnInit: true,
					deltaDirection: 'right',
					deltaShift: -16,
				};

			if( jQuery( '#' + div_cache_ID ).length == 0 )
			{	// Create a div for cache user data
				jQuery( 'body' ).append( '<div id="' + div_cache_ID + '" style="display:none"></div>' );
				var cache = jQuery( '#' + div_cache_ID );
				var tip = jQuery( '#' + div_bubbletip_ID );

				jQuery.ajax(
				{	// Get field info
					type: 'POST',
					url: htsrv_url + 'anon_async.php',
					data: 'action=get_field_bubbletip' + '&field_ID=' + field_ID,
					success: function( result )
					{	// If success request - fill div with field data, save same data to the cache, init bubble tip
						if( ajax_response_is_correct( result ) )
						{	// Init Bubbletip only if ajax content is received
							result = ajax_debug_clear( result );
							tip.html( result );
							cache.html( result );
							if( field.hasClass( 'hide_bubbletip' ) )
							{	// We use this class as flag to understand that when ajax was loading
								// the cursor pointer already left out this element
								// and we don't need to show a bubbletip on init event
								bubbletip_params.showOnInit = false;
								field.removeClass( 'hide_bubbletip' );
							}
							field.bubbletip( tip, bubbletip_params );
							tip.attr( 'style', 'cursor:pointer' );
							tip.click( function()
							{
								field.next().find( 'span.icon:first' ).click();
							} );
						}
						field.addClass( 'bubblefield' );	// Add this class to avoid of the repeating of init bubbletip
					}
				});
			}
			else
			{	// Init bubbletip from cached element
				if( jQuery( '#' + div_cache_ID ).html() != '' )
				{	// Ajax content is downloaded and we can show a bubbletip
					jQuery( '#' + div_bubbletip_ID ).html( jQuery( '#' + div_cache_ID ).html() );
					field.bubbletip( jQuery( '#' + div_bubbletip_ID ), bubbletip_params );
					jQuery( '#' + div_bubbletip_ID ).attr( 'style', 'cursor:pointer' );
					jQuery( '#' + div_bubbletip_ID ).click( function()
					{
						field.next().find( 'span.icon:first' ).click();
					} );
					field.addClass( 'bubblefield' );	// Add this class to avoid of the repeating of init bubbletip
				}
				else
				{	// Div cache is empty when ajax content didn't still download (it is downloading now)
					// We should wait a next focus event to init bubbletip
					jQuery( '#' + div_bubbletip_ID ).remove();
				}
			}
			field_number++;
		}
	} );

	jQuery( document ).on( 'blur', '[rel^=ufdf_]', function()
	{	// This class-flag is used to know that cursor pointer is leaving this element
		jQuery( this ).addClass( 'bubblefield' );
	} );
} );