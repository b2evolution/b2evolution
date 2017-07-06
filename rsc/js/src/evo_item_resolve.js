/**
 * This file is used to select a Comment as the best answer for the Item
 */

jQuery( document ).on( 'click', 'span.evo_post_resolve_btn a', function()
{
	var resolve_wrapper = jQuery( this ).parent();
	var item_ID = parseInt( resolve_wrapper.data( 'id' ) );
	var comment_ID = parseInt( resolve_wrapper.data( 'cmt-id' ) );

	if( item_ID > 0 )
	{	// If item ID is defined...
		// Set status to know the request is in progress now:
		resolve_wrapper.data( 'status', 'inprogress' );
		// Add class to display the request is in progress now:
		jQuery( 'a span', resolve_wrapper ).addClass( 'fa-x--hover' );

		// Call request to resolve:
		evo_rest_api_request( 'collections/' + resolve_wrapper.data( 'coll' ) + '/items/' + item_ID + '/resolve/' + comment_ID,
		function( data )
		{	// Toggle icon to new resolve status:
			if( data.resolved_cmt_ID == comment_ID )
			{	// If item is resolved
				jQuery( 'span.evo_post_resolve_btn[data-id=' + item_ID + '][data-cmt-id]' ).each( function()
				{	// Clear resolved status from previous selected comment:
					jQuery( this ).find( ' a:first' ).show().next().hide();
				} );
				resolve_wrapper.find( 'a:first' ).hide().next().show();
			}
			else
			{	// If item is unresolved
				resolve_wrapper.find( 'a:first' ).show().next().hide();
			}

			// Remove hover style in order to keep current updated status:
			jQuery( 'a span', resolve_wrapper ).removeClass( 'fa-x--hover' );
			// Pause progress status to avoid mouseover event right after updating:
			setTimeout( function() { resolve_wrapper.removeData( 'status' ) }, 500 );
		}, 'POST' );
	}

	return false;
} );

jQuery( document ).on( 'mouseover', 'span.evo_post_resolve_btn a', function()
{
	var resolve_wrapper = jQuery( this ).parent();
	if( resolve_wrapper.data( 'status' ) != 'inprogress' )
	{	// Revert hover style only after resolve status has been updated:
		jQuery( 'a span', resolve_wrapper ).addClass( 'fa-x--hover' );
	}
} );