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

	var voting_bg_color = voting_layout.css( 'backgroundColor' );

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

		voting_layout.on( 'click', '#votingLike', function()
		{ // Action for "Like" button
			jQuery( this ).removeAttr( 'id' );
			votingFadeIn( voting_layout, '#bcffb5' );
			// Use action from hidden input form element
			var voting_action_obj = voting_layout.find( '#voting_action' );
			var ajax_action_url = voting_action_obj.length ? voting_action_obj.val() : action_url;
			jQuery.ajax(
			{ // Send AJAX request to vote
				type: "POST",
				url: ajax_action_url + '&vote_action=like&vote_ID=' + voting_layout.find( '#votingID' ).val(),
				success: function( result )
				{
					voting_layout.html( ajax_debug_clear( result ) );
					update_colorbox_position();
					votingFadeIn( voting_layout, voting_bg_color );
				}
			} );
		} );

		voting_layout.on( 'click', '#votingNoopinion', function()
		{ // Action for "No opinion" button
			jQuery( this ).removeAttr( 'id' )
			votingFadeIn( voting_layout, '#bbb' );
			// Use action from hidden input form element
			var voting_action_obj = voting_layout.find( '#voting_action' );
			var ajax_action_url = voting_action_obj.length ? voting_action_obj.val() : action_url;
			jQuery.ajax(
			{ // Send AJAX request to vote
				type: "POST",
				url: ajax_action_url + '&vote_action=noopinion&vote_ID=' + voting_layout.find( '#votingID' ).val(),
				success: function( result )
				{
					voting_layout.html( ajax_debug_clear( result ) );
					update_colorbox_position();
					votingFadeIn( voting_layout, voting_bg_color );
				}
			} );
		} );

		voting_layout.on( 'click', '#votingDontlike', function()
		{ // Action for "Don't like" button
			jQuery( this ).removeAttr( 'id' )
			votingFadeIn( voting_layout, '#ffc9c9' );
			// Use action from hidden input form element
			var voting_action_obj = voting_layout.find( '#voting_action' );
			var ajax_action_url = voting_action_obj.length ? voting_action_obj.val() : action_url;
			jQuery.ajax(
			{ // Send AJAX request to vote
				type: "POST",
				url: ajax_action_url + '&vote_action=dontlike&vote_ID=' + voting_layout.find( '#votingID' ).val(),
				success: function( result )
				{
					voting_layout.html( ajax_debug_clear( result ) );
					update_colorbox_position();
					votingFadeIn( voting_layout, voting_bg_color );
				}
			} );
		} );

		voting_layout.on( 'click', '#votingInappropriate', function()
		{ // Action for "Inappropriate" checkbox
			if( jQuery( this ).is( ':checked' ) )
			{ // Checked
				var checked = '1';
				votingFadeIn( voting_layout, '#dcc' );
			}
			else
			{	// Unchecked
				var checked = '0';
				votingFadeIn( voting_layout, '#bbb' );
			}
			// Use action from hidden input form element
			var voting_action_obj = voting_layout.find( '#voting_action' );
			var ajax_action_url = voting_action_obj.length ? voting_action_obj.val() : action_url;
			jQuery.ajax(
			{ // Send AJAX request to vote
				type: "POST",
				url: ajax_action_url + '&vote_action=inappropriate&checked=' + checked + '&vote_ID=' + voting_layout.find( '#votingID' ).val(),
				success: function( result )
				{
					votingFadeIn( voting_layout, voting_bg_color );
				}
			} );
		} );

		voting_layout.on( 'click', '#votingSpam', function()
		{ // Action for "Spam" checkbox
			if( jQuery( this ).is( ':checked' ) )
			{ // Checked
				var checked = '1';
				votingFadeIn( voting_layout, '#dcc' );
			}
			else
			{ // Unchecked
				var checked = '0';
				votingFadeIn( voting_layout, '#bbb' );
			}
			// Use action from hidden input form element
			var voting_action_obj = voting_layout.find( '#voting_action' );
			var ajax_action_url = voting_action_obj.length ? voting_action_obj.val() : action_url;
			jQuery.ajax(
			{ // Send AJAX request to vote
				type: "POST",
				url: ajax_action_url + '&vote_action=spam&checked=' + checked + '&vote_ID=' + voting_layout.find( '#votingID' ).val(),
				success: function( result )
				{
					votingFadeIn( voting_layout, voting_bg_color );
				}
			} );
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