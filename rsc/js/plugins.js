/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 * @version $Id: plugins.js 8962 2015-05-13 13:32:15Z fplanque $
 */

/** Init bubbletip for help icon of plugins **/
// Note: This is not just backoffice, this is also used to display a bubbletip over help plugin icon e.g. on the edit post/comment pages.

var plugin_number = 1;
jQuery( document ).on( 'mouseover', 'a.help_plugin_icon', function()
{
	if( jQuery( this ).hasClass( 'bubbleplugin' ) )
	{ // Bubbletip is already initialized on this help icon
		return true;
	}

	jQuery( this ).attr( 'title', '' );
	jQuery( this ).find( 'span' ).removeAttr( 'title' );

	var tip_text = jQuery( this ).attr( 'rel' );
	if( jQuery( '#help_plugin_info_suffix' ).length > 0 )
	{ // Append additional info
		tip_text += jQuery( '#help_plugin_info_suffix' ).html();
	}
	jQuery( 'body' ).append( '<div id="tip_plugin_' + plugin_number + '" style="display:none;max-width:200px;text-align:left">' + tip_text + '</div>' );

	var direction = 'right';
	if( jQuery( 'body' ).width() - jQuery( this ).position().left < 220 )
	{ // Change position of bubbletip if we have no enough space at the right
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