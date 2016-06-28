/**
 * This file is used for REST API requests
 */


/**
 * Execute REST API request
 *
 * @param string URL
 * @param array|function Additional params for request OR Function on success request
 * @param function Function on success request
 * @param string Type method: 'GET', 'POST', 'DELETE', etc.
 */
function evo_rest_api_request( url, params_func, func_method, method )
{
	var params = params_func;
	var func = func_method;
	if( typeof( params_func ) == 'function' )
	{	// This is a request without additional params:
		func = params_func;
		params = {};
		method = func_method;
	}

	if( typeof( method ) == 'undefined' )
	{	// Use GET method by default:
		method = 'GET';
	}

	jQuery.ajax(
	{
		contentType: 'application/json; charset=utf-8',
		type: method,
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