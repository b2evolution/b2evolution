/**
 * This file initializes the login JS handler
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 * 
 * Depends on jQuery
 */
jQuery( document ).ready( function()
{
	if( typeof( display_login_js_handler_config ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	var config = display_login_js_handler_config;
	var requestSent = false;
	var login = document.getElementById( config.dummy_field_login );

	if( login.value.length > 0 )
	{	// Focus on the password field:
		document.getElementById( config.dummy_field_pwd ).focus();
	}
	else
	{	// Focus on the login field:
		login.focus();
	}

	function processSubmit( e )
	{
		if( e.preventDefault )
		{
			e.preventDefault();
		}
		if( requestSent )
		{	// A submit request was already sent, do not send another
			return;
		}

		requestSent = true;
		var form = document.getElementById("login_form");
		var username = form[config.dummy_field_login].value;
		var get_widget_login_hidden_fields = config.get_widget_login_hidden_fields;
		var sessionid = config.session_ID;

		if( !form[config.dummy_field_pwd] || !form.pepper || typeof hex_sha1 == "undefined" && typeof hex_md5 == "undefined" )
		{
			return true;
		}

		var request_payload = {
				'action': 'get_user_salt',
				'get_widget_login_hidden_fields': get_widget_login_hidden_fields,
				'crumb_loginsalt': config.crumb_loginsalt
			}
		
		request_payload[config.dummy_field_login] = username;

		jQuery.ajax({
			type: 'POST',
			url: htsrv_url + 'anon_async.php',
			data: request_payload,
			success: function(result) {
				var pwd_container = jQuery('#pwd_hashed_container');
				var parsed_result;

				try
				{
					parsed_result = JSON.parse( result );
				}
				catch( e )
				{
					pwd_container.html( result );
					return;
				}

				var raw_password = form[config.dummy_field_pwd].value;
				var salts = parsed_result['salts'];
				var hash_algo = parsed_result['hash_algo'];

				if( get_widget_login_hidden_fields )
				{
					form.crumb_loginform.value = parsed_result['crumb'];
					form.pepper.value = parsed_result['pepper'];
					sessionid = parsed_result['session_id'];
				}

				for( var index in salts )
				{
					var pwd_hashed = eval( hash_algo[ index ] );
					pwd_hashed = hex_sha1( pwd_hashed + form.pepper.value );
					pwd_container.append( '<input type="hidden" value="' + pwd_hashed + '" name="pwd_hashed[]">' );
				}

				form[config.dummy_field_pwd].value = 'padding_padding_padding_padding_padding_padding_hashed_' + sessionid; /* to detect cookie problems */
				// (paddings to make it look like encryption on screen. When the string changes to just one more or one less *, it looks like the browser is changing the password on the fly)

				// Append the correct login action as hidden input field
				pwd_container.append( '<input type="hidden" value="1" name="login_action[login]">' );
				form.submit();
			},
			error: function( jqXHR, textStatus, errorThrown )
			{	// Display error text on error request:
				requestSent = false;
				var wrong_response_code = typeof( jqXHR.status ) != 'undefined' && jqXHR.status != 200 ? '\nHTTP Response code: ' + jqXHR.status : '';
				alert( 'Error: could not get hash Salt from server. Please contact the site admin and check the browser and server error logs. (' + textStatus + ': ' + errorThrown + ')'
					+ wrong_response_code );
			}
		});

		// You must return false to prevent the default form behavior
		return false;
	}

	if( config.params_transmit_hashed_password )
	{	// Hash the password onsubmit and clear the original pwd field
		// TODO: dh> it would be nice to disable the clicked/used submit button. That's how it has been when the submit was attached to the submit button(s)

		// Set login form submit handler
		jQuery( '#login_form' ).on( 'submit', processSubmit );
	}
} );