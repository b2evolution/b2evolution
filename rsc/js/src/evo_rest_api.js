/**
 * This file is used for REST API requests
 */


/**
 * Execute REST API request
 *
 * @param string URL
 * @param function Function on success request
 */
function evo_rest_api_request( url, func )
{
	jQuery.ajax(
	{
		contentType: 'application/json; charset=utf-8',
		url: restapi_url + url
	} )
	.then( function( data, textStatus, jqXHR )
	{
		if( typeof( jqXHR.responseJSON ) == 'object' )
		{	// Call function only when we get correct JSON response:
			eval( func )( data, textStatus, jqXHR );
		}
	} );
}