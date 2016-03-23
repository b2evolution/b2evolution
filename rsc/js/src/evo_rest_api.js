/**
 * This file is used for REST API requests
 */


/**
 * Execute REST API request
 *
 * @param string URL
 * @param array|function Additional params for request OR Function on success request
 * @param function Function on success request
 */
function evo_rest_api_request( url, params_func, func )
{
	var params = params_func;
	if( typeof( func ) == 'undefined' )
	{	// This is a request without additional params:
		func = params_func;
		params = {};
	}

	jQuery.ajax(
	{
		contentType: 'application/json; charset=utf-8',
		url: restapi_url + url,
		data: params
	} )
	.then( function( data, textStatus, jqXHR )
	{
		if( typeof( jqXHR.responseJSON ) == 'object' )
		{	// Call function only when we get correct JSON response:
			eval( func )( data, textStatus, jqXHR );
		}
	} );
}