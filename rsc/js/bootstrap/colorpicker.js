/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 * @version $Id: colorpicker.js 8373 2015-02-28 21:44:37Z fplanque $
 */

jQuery(document).ready( function()
{
	var colorpicker_num = 1;
	jQuery( '.form_color_input' ).each( function ()
	{
		var colorpicker_ID = 'farbtastic_colorpicker_' + colorpicker_num;
		jQuery( 'body' ).append( '<div id="' + colorpicker_ID + '" style="display:none"></div>' );
		var farbtastic_colorpicker = jQuery.farbtastic( '#' + colorpicker_ID );
		farbtastic_colorpicker.linkTo( this );

		// Initialize popover to display colorpicker inside
		jQuery( this ).popover( {
				trigger: 'manual',
				placement: 'right',
				html: true,
				content: jQuery( '#' + colorpicker_ID ),
			} );
		jQuery( this ).focus( function()
		{
			var popover_obj = jQuery( this ).parent().find( '.popover' );
			if( popover_obj.length == 0 )
			{ // Initialize popover only first time
				jQuery( '#' + colorpicker_ID ).show();
				jQuery( this ).popover( 'show' );
			}
			else
			{ // Show popover
				popover_obj.show();
			}
		} ).blur( function()
		{
			var popover_obj = jQuery( this ).parent().find( '.popover' );
			if( popover_obj.length > 0 )
			{ // Hide popover
				popover_obj.hide();
			}
		} );

		colorpicker_num++;
	} );
} );