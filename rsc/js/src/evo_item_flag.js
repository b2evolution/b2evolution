/**
 * This file is used to flag items by current user
 */

jQuery( document ).on( 'click', 'a.evo_post_flag_btn', function()
{
	var flag_link = jQuery( this );
	var item_ID = parseInt( flag_link.data( 'id' ) );

	if( item_ID > 0 )
	{	// If item ID is defined...
		// Set status to know the request is in progress now:
		flag_link.data( 'status', 'inprogress' );
		// Add class to display the request is in progress now:
		jQuery( 'span', jQuery( this ) ).addClass( 'fa-x--hover' );

		// Call request to flag:
		evo_rest_api_request( 'collections/' + flag_link.data( 'coll' ) + '/items/' + item_ID + '/flag',
		function( data )
		{	// Toggle icon to new flag status:
			if( data.flag )
			{	// If item is flagged
				flag_link.find( 'span:first' ).show();
				flag_link.find( 'span:last' ).hide();
			}
			else
			{	// If item is unflagged
				flag_link.find( 'span:last' ).show();
				flag_link.find( 'span:first' ).hide();
			}

			// Remove hover style in order to keep current updated status:
			jQuery( 'span', flag_link ).removeClass( 'fa-x--hover' );
			// Pause progress status to avoid mouseover event right after updating:
			setTimeout( function() { flag_link.removeData( 'status' ) }, 500 );
		}, 'PUT' );
	}

	return false;
} );

jQuery( document ).on( 'mouseover', 'a.evo_post_flag_btn', function()
{
	if( jQuery( this ).data( 'status' ) != 'inprogress' )
	{	// Revert hover style only after flag status has been updated:
		jQuery( 'span', jQuery( this ) ).addClass( 'fa-x--hover' );
	}
} );