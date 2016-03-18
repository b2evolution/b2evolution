/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 * @version $Id: usernames.js 9783 2015-07-09 15:22:59Z yura $
 */

var originalLeave = jQuery.fn.popover.Constructor.prototype.leave;
jQuery.fn.popover.Constructor.prototype.leave = function( obj )
{
	var self = obj instanceof this.constructor ?
		obj : jQuery( obj.currentTarget )[this.type](this.getDelegateOptions()).data( 'bs.' + this.type )
	var container, timeout;

	originalLeave.call( this, obj );

	if( obj.currentTarget )
	{
		container = jQuery( obj.currentTarget ).siblings( '.popover' )
		timeout = self.timeout;
		container.one('mouseenter', function()
		{
			//We entered the actual popover – call off the dogs
			clearTimeout( timeout );
			//Let's monitor popover content instead
			container.one('mouseleave', function()
			{
				jQuery.fn.popover.Constructor.prototype.leave.call( self, self );
			} );
		} )
	}
};

jQuery( document ).ready(function()
{
	/** Init popover for User Avatar **/

	var link_number = 1;
	jQuery( document ).on( 'mouseover', '[rel^=bubbletip_]', function()
	{ // Prepare event mouseover for an element with popover effect
		var link = jQuery( this );
		var div_cache_ID = '';
		var request_param = '';

		if( link.attr( 'rel' ).match( 'bubbletip_user_' ) )
		{ // Set data for links with registered users
			var user_ID = link.attr( 'rel' ).replace( /bubbletip_user_(\d+).*/g, '$1' );
			div_cache_ID = 'popover_cache_user_' + user_ID;
			request_param = '&userid=' + user_ID;
		}
		else if( link.attr( 'rel' ).match( 'bubbletip_comment_' ) )
		{ // Set data for anonymous comments
			var comment_ID = link.attr( 'rel' ).replace( /bubbletip_comment_(\d+).*/, '$1' );
			div_cache_ID = 'popover_cache_comment_' + comment_ID;
			request_param = '&commentid=' + comment_ID;
		}

		if( div_cache_ID != '' )
		{ // Init popover for the first time event "mouseover"
			link.attr( 'id', 'popoverlink' + link_number );

			var popover_params = {
				trigger: 'hover',
				container: 'body',
				placement: 'top',
				html: true,
				delay: { 'hide': 400 },
				template: '<div class="popover"><div class="arrow"></div><div class="popover-content"></div></div>'
			};
			if( jQuery( '#' + div_cache_ID ).length == 0 )
			{ // Create a div for cache user data
				jQuery( 'body' ).append( '<div id="' + div_cache_ID + '" style="display:none;"></div>' );
				var cache = jQuery( '#' + div_cache_ID );

				jQuery.ajax(
				{ // Get user info
					type: 'POST',
					url: htsrv_url + 'anon_async.php',
					data: 'action=get_user_bubbletip' + '&blog=' + blog_id + request_param,
					success: function( result )
					{ // If success request - fill div with user data, save same data to the cache, init popover
						if( ajax_response_is_correct( result ) )
						{ // Init Popover only if ajax content is received
							result = ajax_debug_clear( result );
							cache.html( result );
							var show_on_init = true;
							if( link.hasClass( 'hide_popover' ) )
							{ // We use this class as flag to understand that when ajax was loading
								// the mouse pointer already left out this element
								// and we don't need to show a popover on init event
								show_on_init = false;
								link.removeClass( 'hide_popover' )
							}
							// Init popover
							popover_params.content = cache.html();
							link.popover( popover_params );
							if( show_on_init )
							{ // Show popover
								link.popover( 'show' );
							}
							// Remove this from attr 'rel' to avoid of the repeating of init popover
							link.attr( 'rel', link.attr( 'rel').replace( /bubbletip_(user_|comment_)[\d\s]+/g, '' ) );
							console.log( 'ajax' );
						}
					}
				});
			}
			else
			{ // Init popover from cached element
				if( jQuery( '#' + div_cache_ID ).html() != '' )
				{ // Ajax content is downloaded and we can show a popover
					// Remove a title temporary to don't display title on popover
					var link_title = link.attr( 'title' );
					link.removeAttr( 'title' );
					// Init popover
					popover_params.content = jQuery( '#' + div_cache_ID ).html();
					link.popover( popover_params );
					link.popover( 'show' );
					// Restore a title
					link.attr( 'title', link_title );
					// Remove this from attr 'rel' to avoid of the repeating of init popover
					link.attr( 'rel', link.attr( 'rel').replace( /bubbletip_(user_|comment_)[\d\s]+/g, '' ) );
				}
			}
			link_number++;
		}
	} );

	jQuery( document ).on( 'mouseleave', '[rel^=bubbletip_]', function()
	{ // This class-flag is used to know that mouse pointer is leaving this element
		jQuery( this ).addClass( 'hide_popover' );
	} );
} );