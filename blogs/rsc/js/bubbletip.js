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
	if( $( '.userbubble' ).length >0 )
	{ // If links with username exist on the page
		var link_number = 1;
		jQuery( '.userbubble' ).each(function()
		{ // Prepare each link with username to add bubbletip
			var link = jQuery( this );
			var user_id = jQuery( this ).attr( 'id' ).replace( 'username_', '' );
			link.attr( 'id', 'bubblelink' + link_number );
			jQuery( 'body' ).append( '<div id="userbubbleinfo_' + link_number + '" style="display:none;">' + link.html() + '</div>' );

			var tip = jQuery( '#userbubbleinfo_' + link_number );
			jQuery.ajax({ // Get user info and Set bubbletip action
				type: 'POST',
				url: htsrv_url + 'anon_async.php',
				data: 'action=get_user_bubbletip&userid=' + user_id,
				success: function( result )
				{
					tip.html( result )
					link.bubbletip( tip );
				}
			});

			link_number++;
		});
	}
});
