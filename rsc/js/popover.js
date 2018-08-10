/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 */

/** Init bubbletip for help icon of plugins, widgets **/
// Note: This is not just backoffice, this is also used to display a bubbletip over help plugin icon e.g. on the edit post/comment pages.
// Also it may be used for other icons in future.

var evo_tooltip_number = 1;
jQuery( document ).on( 'mouseover', '[data-popover]', function()
{
	if( jQuery( this ).data( 'tooltip-init' ) )
	{	// Tooltip is already initialized on this help icon
		return true;
	}

	jQuery( this ).attr( 'title', '' );
	jQuery( this ).find( 'span' ).removeAttr( 'title' );

	var tip_text = jQuery( this ).data( 'popover' );
	jQuery( 'body' ).append( '<div id="evo_tooltip_box_' + evo_tooltip_number + '" style="display:none;max-width:200px;text-align:left">' + tip_text + '</div>' );

	var direction = jQuery( this ).data( 'placement' );
	if( ! direction )
	{
		direction = 'right';
	}
	if( jQuery( 'body' ).width() - jQuery( this ).position().left < 220 )
	{ // Change position of bubbletip if we have no enough space at the right
		direction = 'left';
	}

	var tip = jQuery( '#evo_tooltip_box_' + evo_tooltip_number );
	jQuery( this ).bubbletip( tip, {
			showOnInit: true,
			deltaDirection: direction,
			deltaShift: 0,
		} );

	// Add this data to avoid of the repeating of init tooltip:
	jQuery( this ).data( 'tooltip-init', 1 );
	evo_tooltip_number++;
} );