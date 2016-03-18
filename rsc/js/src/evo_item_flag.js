/**
 * This file is used to flag items by current user
 */

jQuery( document ).ready( function()
{
	jQuery( 'a.evo_post_flag_btn' ).click( function()
	{
		evo_rest_api_request( 'items/' + jQuery( this ).data( 'id' ) + '/flag', function( data )
		{	// 
			console.log( data );
		} );

		return false;
	} );
} );