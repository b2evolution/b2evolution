/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 * @version $Id: bubbletip.js 6989 2014-06-26 05:10:11Z yura $
 */

jQuery( document ).ready(function()
{
	/** Init bubbletip for User Avatar **/

	var link_number = 1;
	jQuery( document ).on( 'mouseover', '[rel^=bubbletip_]', function()
	{	// Prepare event mouseover for an element with bubbletip effect
		var link = jQuery( this );
		var div_cache_ID = '';
		var request_param = '';

		if( link.attr( 'rel' ).match( 'bubbletip_user_' ) )
		{	// Set data for links with registred users
			var user_ID = link.attr( 'rel' ).replace( /bubbletip_user_(\d+).*/g, '$1' );
			div_cache_ID = 'bubble_cache_user_' + user_ID;
			request_param = '&userid=' + user_ID;
		}
		else if( link.attr( 'rel' ).match( 'bubbletip_comment_' ) )
		{	// Set data for anonymous comments
			var comment_ID = link.attr( 'rel' ).replace( /bubbletip_comment_(\d+).*/, '$1' );
			div_cache_ID = 'bubble_cache_comment_' + comment_ID;
			request_param = '&commentid=' + comment_ID;
		}

		if( div_cache_ID != '' )
		{	// Init bubbletip for the first time event "mouseover"
			link.attr( 'id', 'bubblelink' + link_number );
			var div_bubbletip_ID = 'bubbletip_info_' + link_number;

			jQuery( 'body' ).append( '<div id="' + div_bubbletip_ID + '" style="display:none;"></div>' );

			var bubbletip_params = { showOnInit: true, deltaShift: 0, delayHide: 1 };
			if( jQuery( '#' + div_cache_ID ).length == 0 )
			{	// Create a div for cache user data
				jQuery( 'body' ).append( '<div id="' + div_cache_ID + '" style="display:none;"></div>' );
				var cache = jQuery( '#' + div_cache_ID );
				var tip = jQuery( '#' + div_bubbletip_ID );

				jQuery.ajax(
				{	// Get user info
					type: 'POST',
					url: htsrv_url + 'anon_async.php',
					data: 'action=get_user_bubbletip' + '&blog=' + blog_id + request_param,
					success: function( result )
					{	// If success request - fill div with user data, save same data to the cache, init bubble tip
						if( ajax_response_is_correct( result ) )
						{	// Init Bubbletip only if ajax content is received
							result = ajax_debug_clear( result );
							tip.html( result );
							cache.html( result );
							if( tip.find( 'img' ).width() == 0 )
							{	// Fix bubbletip size in first time downloading
								var div = tip.find( 'div.center' );
								var width = div.attr( 'w' );
								var height = parseInt( div.attr( 'h' ) ) + 9;
								div.attr( 'style', 'width:' + width + 'px;height:' + height + 'px;' );
							}
							if( link.hasClass( 'hide_bubbletip' ) )
							{	// We use this class as flag to understand that when ajax was loading
								// the mouse pointer already left out this element
								// and we don't need to show a bubbletip on init event
								bubbletip_params.showOnInit = false;
								link.removeClass( 'hide_bubbletip' )
							}
							link.bubbletip( tip, bubbletip_params );
							// Remove this from attr 'rel' to avoid of the repeating of init bubbletip
							link.attr( 'rel', link.attr( 'rel').replace( /bubbletip_(user_|comment_)[\d\s]+/g, '' ) );
						}
					}
				});
			}
			else
			{	// Init bubbletip from cached element
				if( jQuery( '#' + div_cache_ID ).html() != '' )
				{	// Ajax content is downloaded and we can show a bubbletip
					jQuery( '#' + div_bubbletip_ID ).html( jQuery( '#' + div_cache_ID ).html() );
					link.bubbletip( jQuery( '#' + div_bubbletip_ID ), bubbletip_params );
					// Remove this from attr 'rel' to avoid of the repeating of init bubbletip
					link.attr( 'rel', link.attr( 'rel').replace( /bubbletip_(user_|comment_)[\d\s]+/g, '' ) );
				}
				else
				{	// Div cache is empty when ajax content didn't still download (it is downloading now)
					// We should wait a next mouseover event to init bubbletip
					jQuery( '#' + div_bubbletip_ID ).remove();
				}
			}
			link_number++;
		}
	} );

	jQuery( document ).on( 'mouseleave', '[rel^=bubbletip_]', function()
	{	// This class-flag is used to know that mouse pointer is leaving this element
		jQuery( this ).addClass( 'hide_bubbletip' );
	} );
} );