/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 * @version $Id: colorpicker.js 8373 2015-02-28 21:44:37Z fplanque $
 */

var colorpicker_num = 1;
jQuery( document ).ready( function()
{
	evo_initialize_colorpicker_inputs();
} );


/**
 * Initialize color picker for all inputs on the loaded page
 */
function evo_initialize_colorpicker_inputs()
{
	jQuery( '.form_color_input' ).each( function ()
	{
		var colorpicker_ID = 'farbtastic_colorpicker_' + colorpicker_num;
		jQuery( 'body' ).append( '<div id="' + colorpicker_ID + '"></div>' );
		var farbtastic_colorpicker = jQuery.farbtastic( '#' + colorpicker_ID );
		farbtastic_colorpicker.linkTo( this );

		// Initialize bubbletip to display colorpicker inside
		jQuery( this ).bubbletip( jQuery( '#' + colorpicker_ID ), {
				bindShow: 'focus click',
				bindHide: 'blur',
				bindClose: 'blur',
				calculateOnShow: true,
				deltaDirection: 'right',
				deltaShift: 0,
			} );

		colorpicker_num++;
	} );
}