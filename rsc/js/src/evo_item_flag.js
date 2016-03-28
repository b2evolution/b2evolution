/**
 * This file is used to flag items by current user
 */

jQuery( document ).ready( function()
{
	jQuery( 'a.evo_post_flag_btn' ).click( function()
	{
		var flag_link = jQuery( this );
		var item_ID = parseInt( flag_link.data( 'id' ) );
		if( item_ID > 0 )
		{	// Request to flag if item ID is defined:
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
					flag_link.find( 'span:first' ).hide();
					flag_link.find( 'span:last' ).show();
				}
			} );
		}

		return false;
	} );
} );