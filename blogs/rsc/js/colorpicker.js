/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 * @version $Id: colorpicker.js 7500 2014-10-23 09:08:43Z yura $
 */

jQuery(document).ready( function()
{
	var colorpicker_num = 1;
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
} );