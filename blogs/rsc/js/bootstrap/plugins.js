/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 * @version $Id: plugins.js 3901 2013-06-03 13:08:29Z yura $
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
		if( tip_text != '' )
		{
			tip_text += '<br />';
		}
		tip_text += '<a href="' + jQuery( this ).attr( 'href' ) + '" target="_blank">Open help in new window</a>';

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