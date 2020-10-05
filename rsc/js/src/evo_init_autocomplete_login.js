/**
 * This file initialize autocomplete login input fields
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 */

jQuery( document ).ready( function()
{
	if( typeof( evo_autocomplete_login_config ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	jQuery( "input.autocomplete_login" ).on( "added",function()
	{
		jQuery( "input.autocomplete_login" ).each( function()
		{
			if( jQuery( this ).hasClass( "tt-input" ) || jQuery( this ).hasClass( "tt-hint" ) )
			{	// Skip this field because typeahead is initialized before:
				return;
			}
			var ajax_url = "";
			if( jQuery( this ).hasClass( "only_assignees" ) )
			{
				ajax_url = restapi_url + evo_autocomplete_login_config.url;
			}
			else
			{
				ajax_url = restapi_url + "users/logins";
			}
			if( jQuery( this ).data( "status" ) )
			{
				ajax_url += "&status=" + jQuery( this ).data( "status" );
			}
			jQuery( this ).typeahead( null,
			{
				displayKey: "login",
				source: function ( query, cb )
				{
					jQuery.ajax(
					{
						type: "GET",
						dataType: "JSON",
						url: ajax_url,
						data: { q: query },
						success: function( data )
						{
							var json = new Array();
							for( var l in data.list )
							{
								json.push( { login: data.list[ l ] } );
							}
							cb( json );
						}
					} );
				}
			} );
		} );
	} );

	jQuery( "input.autocomplete_login" ).trigger( "added" );

	// Don't submit a form by Enter when user is editing the owner fields:
	evo_prevent_key_enter( evo_autocomplete_login_config.selector );
} );