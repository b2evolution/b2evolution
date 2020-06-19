/**
 * This file initializes the login validator JS
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 * 
 * Depends on: jQuery
 */
jQuery( document ).ready( function()
{
    if( typeof( evo_init_login_validator_config ) == 'undefined' )
    {	// Don't execute code below because no config var is found:
        return;
    }

    var config = evo_init_login_validator_config;

    var login_icon_load      = config.login_icon_load;
	var login_icon_available = config.login_icon_available;
	var login_icon_exists    = config.login_icon_exists;
	var login_icon_error     = config.login_icon_error;

	var login_text_empty     = config.login_text_empty;
	var login_text_available = config.login_text_available;
	var login_text_exists    = config.login_text_exists;

	var login_field = jQuery( '#register_form input#' + config.login_id );
	login_field.change( function()
        {	// Validate if username is available
            var note_Obj = jQuery( "#login_status_msg" );
            if( jQuery( this ).val() == "" )
            {	// Login is empty
                jQuery( "#login_status" ).html( "" );
                note_Obj.html( login_text_empty ).attr( "class", "notes" );
            }
            else
            {	// Validate login
                jQuery( "#login_status" ).html( login_icon_load );
                jQuery.ajax( {
                    type: "POST",
                    url: config.login_htsrv_url + 'anon_async.php',
                    data: "action=validate_login&login=" + jQuery( this ).val(),
                    success: function( result )
                    {
                        result = ajax_debug_clear( result );
                        if( result == "exists" )
                        {	// Login already exists
                            jQuery( "#login_status" ).html( "" );
                            note_Obj.html( login_icon_exists + " " + login_text_exists ).attr( "class", "red" );
                            login_field[0].setCustomValidity( login_text_exists );
                        }
                        else if( result == "available" )
                        {	// Login is available
                            jQuery( "#login_status" ).html( "" );
                            //note_Obj.html( login_icon_available + " " + login_text_available ).attr( "class", "green" );
                            note_Obj.html( "" );
                            login_field[0].setCustomValidity( "" );
                        }
                        else
                        {	// Errors
                            jQuery( "#login_status" ).html( "" );
                            note_Obj.html( login_icon_error.replace( "$error_msg$", result.replace( /(<([^>]+)>)/ig, "" ) ) + " " + result ).attr( "class", "red" );
                            login_field[0].setCustomValidity( result.replace( /(<([^>]+)>)/ig, "" ) );
                        }
                    }
                } );
            }
        } );
} );