/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 * @version $Id: voting.js 674 2012-09-18 07:08:29Z yura $
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
	var voting_bg_color = voting_layout.css( 'backgroundColor' );

	if( load_form )
	{
		jQuery.ajax(
		{	// Initialize a voting form
			type: "POST",
			url: action_url + '&vote_ID=' + element_id,
			success: function( result )
			{
				voting_layout.html( ajax_debug_clear( result ) );
			}
		} );
	}

	if( typeof voting_layout.is_inited == 'undefined' )
	{	// Initialize the events 'onclick' only one time

		voting_layout.on( 'click', 'a.action_icon', function()
		{	// Stop a click event of each link in voting form (The links are used only when JavaScript is not enabled)
			return false;
		} );

		voting_layout.on( 'click', '#votingLike', function()
		{	// Action for "Like" button
			jQuery( this ).removeAttr( 'id' );
			votingFadeIn( voting_layout, '#bcffb5' );
			jQuery.ajax(
			{	// Send AJAX request to vote
				type: "POST",
				url: action_url + '&vote_action=like&vote_ID=' + voting_layout.find( '#votingID' ).val(),
				success: function( result )
				{
					voting_layout.html( ajax_debug_clear( result ) );
					votingFadeIn( voting_layout, voting_bg_color );
				}
			} );
		} );

		voting_layout.on( 'click', '#votingNoopinion', function()
		{	// Action for "No opinion" button
			jQuery( this ).removeAttr( 'id' )
			votingFadeIn( voting_layout, '#bbb' );
			jQuery.ajax(
			{	// Send AJAX request to vote
				type: "POST",
				url: action_url + '&vote_action=noopinion&vote_ID=' + voting_layout.find( '#votingID' ).val(),
				success: function( result )
				{
					voting_layout.html( ajax_debug_clear( result ) );
					votingFadeIn( voting_layout, voting_bg_color );
				}
			} );
		} );

		voting_layout.on( 'click', '#votingDontlike', function()
		{	// Action for "Don't like" button
			jQuery( this ).removeAttr( 'id' )
			votingFadeIn( voting_layout, '#ffc9c9' );
			jQuery.ajax(
			{	// Send AJAX request to vote
				type: "POST",
				url: action_url + '&vote_action=dontlike&vote_ID=' + voting_layout.find( '#votingID' ).val(),
				success: function( result )
				{
					voting_layout.html( ajax_debug_clear( result ) );
					votingFadeIn( voting_layout, voting_bg_color );
				}
			} );
		} );

		voting_layout.on( 'click', '#votingInappropriate', function()
		{	// Action for "Inappropriate" checkbox
			if( jQuery( this ).is( ':checked' ) )
			{	// Checked
				var checked = '1';
				votingFadeIn( voting_layout, '#dcc' );
			}
			else
			{	// Unchecked
				var checked = '0';
				votingFadeIn( voting_layout, '#bbb' );
			}
			jQuery.ajax(
			{	// Send AJAX request to vote
				type: "POST",
				url: action_url + '&vote_action=inappropriate&checked=' + checked + '&vote_ID=' + voting_layout.find( '#votingID' ).val(),
				success: function( result )
				{
					votingFadeIn( voting_layout, voting_bg_color );
				}
			} );
		} );

		voting_layout.on( 'click', '#votingSpam', function()
		{	// Action for "Spam" checkbox
			if( jQuery( this ).is( ':checked' ) )
			{	// Checked
				var checked = '1';
				votingFadeIn( voting_layout, '#dcc' );
			}
			else
			{	// Unchecked
				var checked = '0';
				votingFadeIn( voting_layout, '#bbb' );
			}
			jQuery.ajax(
			{	// Send AJAX request to vote
				type: "POST",
				url: action_url + '&vote_action=spam&checked=' + checked + '&vote_ID=' + voting_layout.find( '#votingID' ).val(),
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