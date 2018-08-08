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


/**
 * Print an error of fail request
 *
 * @param string Object selector
 * @param object Error data: 'message', 'code', 'data.status'
 * @param boolean TRUE to print additional debug info
 */
function evo_rest_api_print_error( obj_selector, error, debug )
{
	if( typeof( error ) != 'string' && typeof( error.code ) == 'undefined' )
	{
		error = typeof( error.responseJSON ) == 'undefined' ? error.statusText : error.responseJSON;
	}

	if( typeof( error.code ) == 'undefined' )
	{	// Unknown non-json response:
		var error_text = '<h4 class="text-danger">Unknown error: ' + error + '</h4>';
	}
	else
	{	// JSON error data accepted:
		var error_text ='<h4 class="text-danger">' + error.message + '</h4>';
		if( debug )
		{	// Display additional error info in debug mode only:
			error_text += '<div><b>Code:</b> ' + error.code + '</div>'
				+ '<div><b>Status:</b> ' + error.data.status + '</div>';
		}
	}

	evo_rest_api_end_loading( obj_selector, error_text );
}


/**
 * Set style during loading new REST API content
 *
 * @param string Object selector
 */
function evo_rest_api_start_loading( obj_selector )
{
	jQuery( obj_selector ).addClass( 'evo_rest_api_loading' )
		.append( '<div class="evo_rest_api_loader">loading...</div>' );
}


/**
 * Remove style after loading new content
 *
 * @param string Object selector
 * @param string New content
 */
function evo_rest_api_end_loading( obj_selector, content )
{
	jQuery( obj_selector ).removeClass( 'evo_rest_api_loading' )
		.html( content )
		.find( '.evo_rest_api_loader' ).remove();
}