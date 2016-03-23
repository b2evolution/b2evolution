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
			evo_rest_api_request( 'items/' + item_ID + '/flag',
			{
				'b2evo_icons_type': b2evo_icons_type,
				'get_icon': 1,
			},
			function( data )
			{	// Replace icon with new flag status:
				flag_link.html( data.icon );
			} );
		}

		return false;
	} );
} );