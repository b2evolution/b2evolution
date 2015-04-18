/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 * @version $Id: plugins.js 8789 2015-04-17 14:56:15Z yura $
 */

jQuery( document ).ready(function()
{
	/** Init popover for help icon of plugins **/

	var plugin_number = 1;
	jQuery( 'a.help_plugin_icon' ).mouseover( function()
	{
		if( jQuery( this ).hasClass( 'popoverplugin' ) )
		{ // Popover is already initialized on this help icon
			return true;
		}

		jQuery( this ).attr( 'title', '' );
		jQuery( this ).find( 'span' ).removeAttr( 'title' );

		var tip_text = jQuery( this ).attr( 'rel' );
		tip_text += '<p><strong>Click <span class="fa fa-question-circle"></span> to access full documentaion for this plugin</strong></p>';

		var placement = 'right';
		if( jQuery( 'body' ).width() - jQuery( this ).position().left < 220 )
		{ // Change position of bubbletip if we have no enough space at the right
			placement = 'left';
		}

		jQuery( this ).popover( {
				trigger: 'hover',
				placement: placement,
				html: true,
				content: tip_text,
				template: '<div class="popover popover-plugin"><div class="arrow"></div><div class="popover-content"></div></div>'
			} );
		jQuery( this ).popover( 'show' );

		// Add this class to avoid of the repeating of init bubbletip
		jQuery( this ).addClass( 'popoverplugin' );
		plugin_number++;
	} );

} );