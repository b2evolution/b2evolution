/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 * @version $Id: plugins.js 8373 2015-02-28 21:44:37Z fplanque $
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
		if( tip_text != '' )
		{
			tip_text += '<br />';
		}
		tip_text += '<a href="' + jQuery( this ).attr( 'href' ) + '" target="_blank">Open help in new window</a>';
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