/**
 * This file initializes the password edit JS
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
	if( typeof( evo_init_password_edit_config ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	var config = evo_init_password_edit_config;

	/**
	 * Hide/Show error style of input depending on visibility of the error messages
	 *
	 * @param string jQuery selector
	 */
	window.user_pass_clear_style = function user_pass_clear_style( obj_selector )
		{
			jQuery( obj_selector ).each( function()
			{
				if( jQuery( this ).parent().find( "span.field_error span:visible" ).length )
				{
					jQuery( this ).addClass( "field_error" );
				}
				else
				{
					jQuery( this ).removeClass( "field_error" );
				}
			} );
		};

	jQuery( "#current_user_pass" ).keyup( function()
		{
			var error_obj = jQuery( this ).parent().find( "span.field_error" );
			if( error_obj.length )
			{
				if( jQuery( this ).val() == "" )
				{
					error_obj.show();
				}
				else
				{
					error_obj.hide();
				}
			}

			user_pass_clear_style( "#current_user_pass" );
		} );

	jQuery( "#edited_user_pass1, #edited_user_pass2" ).keyup( function()
		{
			var minpass_obj = jQuery( this ).parent().find( ".pass_check_min" );
			if( minpass_obj.length )
			{	// Hide/Show a message about min pass length
				if( jQuery.trim( jQuery( this ).val() ).length >= config.user_minpwdlen )
				{
					minpass_obj.hide();
				}
				else
				{
					minpass_obj.show();
				}
			}

			var diff_obj = jQuery( ".pass_check_diff" );
			if( diff_obj.length && jQuery( "#edited_user_pass1" ).val() == jQuery( " #edited_user_pass2" ).val() )
			{	// Hide message about different passwords
				diff_obj.hide();
			}

			// Hide message about that new password must be entered
			var new_obj = jQuery( this ).parent().find( ".pass_check_new" );
			if( new_obj.length )
			{
				if( jQuery( this ).val() == "" )
				{
					new_obj.show();
				}
				else
				{
					new_obj.hide();
				}
			}

			// Hide message about that new password must be entered twice
			var twice_obj = jQuery( this ).parent().find( ".pass_check_twice" );
			if( twice_obj.length )
			{
				if( jQuery( this ).val() == "" )
				{
					twice_obj.show();
				}
				else
				{
					twice_obj.hide();
				}
			}

			var warning_obj = jQuery( this ).parent().find( ".pass_check_warning" );
			if( jQuery.trim( jQuery( this ).val() ) != jQuery( this ).val() )
			{	// Password contains the leading and trailing spaces
				if( ! warning_obj.length )
				{
					jQuery( this ).parent().append( '<span class="pass_check_warning notes field_error">' + config.msg_pwd_trim_warning + '</span>' );
				}
			}
			else if( warning_obj.length )
			{	// No spaces, Remove warning
				warning_obj.remove();
			}

			user_pass_clear_style( "#edited_user_pass1, #edited_user_pass2" );
		} );
} );