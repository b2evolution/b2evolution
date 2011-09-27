/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 * @version $Id$
 */

/**
 * Init : adds required elements to the document tree
 *
 */
jQuery( document ).ready(function()
{
	if( $( '.userbubble' ).length > 0 )
	{ // If links with username exist on the page
		var link_number = 1;
		jQuery( '.userbubble' ).mouseover(function()
		{
			var link = jQuery( this );
			if( link.attr( 'id' ).match( 'username_' ) )
			{ // Init bubbletip  for the first time event "mouseove"
				var user_id = link.attr( 'id' ).replace( 'username_', '' );
				link.attr( 'id', 'bubblelink' + link_number );
				
				jQuery( 'body' ).append( '<div id="userbubble_info_' + link_number + '" style="display:none;"></div>' );
				
				if( jQuery( '#userbubble_cache_' + user_id ).length == 0 )
				{ // Create a div for cache user data
					jQuery( 'body' ).append( '<div id="userbubble_cache_' + user_id + '" style="display:none;"></div>' );
					var cache = jQuery( '#userbubble_cache_' + user_id );
					var tip = jQuery( '#userbubble_info_' + link_number );
					jQuery.ajax({ // Get user info
						type: 'POST',
						url: htsrv_url + 'anon_async.php',
						data: 'action=get_user_bubbletip&userid=' + user_id,
						success: function( result )
						{
							tip.html( result );
							cache.html( result );
							link.bubbletip( tip, { showOnInit: true } );
						}
					});
				}
				else
				{ // Init bubbletip from cache
					jQuery( '#userbubble_info_' + link_number ).html( jQuery( '#userbubble_cache_' + user_id ).html() );
					link.bubbletip( jQuery( '#userbubble_info_' + link_number ), { showOnInit: true } );
				}
				link_number++;
			}
		});
	}
});
