/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 * @version $Id: voting.js 8373 2015-02-28 21:44:37Z fplanque $
 */

/**
 * Initialize the voting events
 *
 * @param object Voting html element
 * @param string Voting url to send AJAX request
 * @param string Element ID
 * @param boolean Load form
 */
function init_voting_bar( voting_layout, action_url, element_id, load_form )
{
	// Update the colorbox width and position when new voting panel is loaded
	function update_colorbox_position()
	{
		if( voting_layout.closest( '#colorbox' ).width() <= 480 )
		{
			jQuery( '#colorbox .vote_title_text' ).hide();
		}
		else
		{
			jQuery( '#colorbox .vote_title_text' ).show();
		}

		if( voting_layout.attr( 'id' ) == 'cboxVoting' )
		{
			var colorbox_width = jQuery( '#colorbox' ).width();
			var voting_layout_width = voting_layout.width();
			if( voting_layout_width > colorbox_width )
			{
				jQuery( '#colorbox' ).css( {
					'left' : jQuery( '#colorbox' ).position().left - ( Math.round( voting_layout_width - colorbox_width ) / 2 ),
					'width': voting_layout_width
				} );
			}
		}
	}

	if( load_form )
	{
		voting_layout.html( '<div class="loading">&nbsp;</div>' );
		jQuery.ajax(
		{ // Initialize a voting form
			type: "POST",
			url: action_url + '&vote_ID=' + element_id,
			success: function( result )
			{
				voting_layout.html( ajax_debug_clear( result ) );
				update_colorbox_position();
			}
		} );
	}

	if( typeof voting_layout.is_inited == 'undefined' )
	{ // Initialize the events 'onclick' only one time

		voting_layout.on( 'click', 'a.action_icon', function()
		{ // Stop a click event of each link in voting form (The links are used only when JavaScript is not enabled)
			return false;
		} );

		function send_voting_ajax_request( obj, vote_action, fadein_color, fadein_color2 )
		{
			// Use action from hidden input form element:
			var voting_action_obj = voting_layout.find( '#voting_action' );
			var ajax_action_url = voting_action_obj.length ? voting_action_obj.val() : action_url;

			if( voting_layout.find( '#votingID' ).length > 0 )
			{	// Add object ID to action URL:
				ajax_action_url += '&vote_ID=' + voting_layout.find( '#votingID' ).val();
			}
			if( voting_layout.find( '#widgetID' ).length > 0 )
			{	// Add widget ID to action URL:
				ajax_action_url += '&widget_ID=' + voting_layout.find( '#widgetID' ).val();
			}
			if( voting_layout.find( '#skinID' ).length > 0 )
			{	// Add skin ID to action URL:
				ajax_action_url += '&skin_ID=' + voting_layout.find( '#skinID' ).val();
			}

			var voting_bg_color = voting_layout.css( 'backgroundColor' );

			if( jQuery( obj ).is( ':checkbox' ) )
			{	// Checkbox:
				if( jQuery( obj ).is( ':checked' ) )
				{	// Checked
					ajax_action_url += '&checked=1';
					votingFadeIn( voting_layout, fadein_color );
				}
				else
				{	// Unchecked
					ajax_action_url += '&checked=0';
					votingFadeIn( voting_layout, fadein_color2 );
				}
			}
			else
			{	// "Like" button:
				jQuery( obj ).removeAttr( 'id' );
				votingFadeIn( voting_layout, fadein_color );
			}

			jQuery.ajax(
			{ // Send AJAX request to vote
				type: "POST",
				url: ajax_action_url + '&vote_action=' + vote_action,
				success: function( result )
				{
					if( ! jQuery( obj ).is( ':checkbox' ) )
					{	// "Like" button:
						voting_layout.html( ajax_debug_clear( result ) );
						update_colorbox_position();
					}
					votingFadeIn( voting_layout, voting_bg_color );
				}
			} );
		}

		voting_layout.on( 'click', '#votingLike', function()
		{	// Action for "Like" button:
			send_voting_ajax_request( this, 'like', '#bcffb5' );
		} );

		voting_layout.on( 'click', '#votingNoopinion', function()
		{	// Action for "No opinion" button:
			send_voting_ajax_request( this, 'noopinion', '#bbb' );
		} );

		voting_layout.on( 'click', '#votingDontlike', function()
		{	// Action for "Don't like" button:
			send_voting_ajax_request( this, 'dontlike', '#ffc9c9' );
		} );

		voting_layout.on( 'click', '#votingInappropriate', function()
		{	// Action for "Inappropriate" checkbox:
			send_voting_ajax_request( this, 'inappropriate', '#dcc', '#bbb' );
		} );

		voting_layout.on( 'click', '#votingSpam', function()
		{	// Action for "Spam" checkbox:
			send_voting_ajax_request( this, 'spam', '#dcc', '#bbb' );
		} );

		voting_layout.is_inited = true;
	}
}


/*
 * Fade in background color
 *
 * @param object Layout
 * @param string Color
 * 
 */
function votingFadeIn( obj, color )
{
	if( color == 'transparent' || color == 'rgba(0, 0, 0, 0)' )
	{	// Animation doesn't work with transparent color, it converts to white color
		// To fix this get bg color of the parent
		var obj_parent = obj.parent();
		var color_parent = color;
		while( obj_parent && ( color_parent == 'transparent' || color_parent == 'rgba(0, 0, 0, 0)' ) )
		{	// Find bg color of parent
			color_parent = obj_parent.css( 'backgroundColor' );
			obj_parent = obj_parent.parent();
		}
		if( obj_parent[0].tagName != 'HTML' )
		{
			color = color_parent;
		}
	}
	obj.animate( { backgroundColor: color }, 200 );
}