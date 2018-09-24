/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 */

/** Init popover for help icon of plugins, widgets **/
// Note: This is not just backoffice, this is also used to display a bubbletip over help plugin icon e.g. on the edit post/comment pages.
// Also it may be used for other icons in future.

jQuery( document ).on( 'mouseover', '[data-popover]', function()
{
	if( jQuery( this ).data( 'tooltip-init' ) )
	{	// Tooltip is already initialized on this help icon
		return true;
	}

	jQuery( this ).attr( 'title', '' );
	jQuery( this ).find( 'span' ).removeAttr( 'title' );

	var tip_text = jQuery( this ).data( 'popover' );
	var placement = jQuery( this ).data( 'placement' );

	if( ! placement )
	{
		placement = 'right';
	}

	if( placement == 'right' && jQuery( 'body' ).width() - jQuery( this ).position().left < 220 )
	{ // Change position of bubbletip if we have no enough space at the right
		placement = 'left';
	}
	else if( placement == 'top' && jQuery( 'body' ).height() - jQuery( this ).position().top < 220 )
	{
		placement = 'bottom';
	}

	jQuery( this ).popover( {
			trigger: 'hover',
			placement: placement,
			html: true,
			content: tip_text,
			template: '<div class="popover popover-plugin"><div class="arrow"></div><div class="popover-content"></div></div>'
		} );
	jQuery( this ).popover( 'show' );

	// Add this data to avoid of the repeating of init tooltip:
	jQuery( this ).data( 'tooltip-init', 1 );
} );