/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 * @version $Id: plugins.js 8789 2015-04-17 14:56:15Z yura $
 */

jQuery( document ).ready(function()
{
	/** Init bubbletip for help icon of plugins **/
	// fp>yura: is this backoffice only?

	var plugin_number = 1;
	jQuery( 'a.help_plugin_icon' ).mouseover( function()
	{
		if( jQuery( this ).hasClass( 'bubbleplugin' ) )
		{	// Bubbletip is already initialized on this help icon
			return true;
		}

		jQuery( this ).attr( 'title', '' );
		jQuery( this ).find( 'span' ).removeAttr( 'title' );

		var tip_text = jQuery( this ).attr( 'rel' );
		tip_text += '<p><strong>Click <span class="fa fa-question-circle"></span> to access full documentaion for this plugin</strong></p>';
		jQuery( 'body' ).append( '<div id="tip_plugin_' + plugin_number + '" style="display:none;max-width:200px;text-align:left">' + tip_text + '</div>' );

		var direction = 'right';
		if( jQuery( 'body' ).width() - jQuery( this ).position().left < 220 )
		{	// Change position of bubbletip if we have no enough space at the right
			direction = 'left';
		}

		var tip = jQuery( '#tip_plugin_' + plugin_number );
		jQuery( this ).bubbletip( tip, {
				showOnInit: true,
				deltaDirection: direction,
				deltaShift: 0,
			} );

		// Add this class to avoid of the repeating of init bubbletip
		jQuery( this ).addClass( 'bubbleplugin' );
		plugin_number++;
	} );

} );