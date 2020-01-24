/**
 * This file initialize plugin "Info dots renderer"
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 */

jQuery( document ).ready( function()
{
	jQuery( ".infodots_dot" ).each( function()
	{ // Check what dot we can show on the page
		if( jQuery( "#" + jQuery( this ).attr( "rel" ) ).length )
		{ // Display dot if a content exists
			jQuery( this ).show();
		}
		else
		{ // Remove dot from the page, probably this dot appears after <more> separator
			jQuery( this ).remove();
		}
	} );

	jQuery( ".infodots_dot" ).mouseover( function()
	{
		var tooltip_obj = jQuery( "#" + jQuery( this ).attr( "rel" ) );
		if( tooltip_obj.length )
		{ // Init bubbletip for point once
			if( typeof( infodots_bubbletip_wrapperContainer ) == "undefined" ||
			    jQuery( infodots_bubbletip_wrapperContainer ).length == 0 )
			{ // Check for correct container
				infodots_bubbletip_wrapperContainer = "body";
			}

			jQuery( this ).bubbletip( tooltip_obj,
			{
				showOnInit: true,
				deltaShift: ( jQuery( this ).css( "box-sizing" ) == "border-box" ? -18 : -5 ),
				wrapperContainer: infodots_bubbletip_wrapperContainer,
				zIndex: 1001,
			} );
		}
		jQuery( this ).addClass( "hovered" );
	} )
	.bind( "click", function()
	{ // Duplicate this event for "touch" devices
		jQuery( this ).mouseover();
	} );
} );