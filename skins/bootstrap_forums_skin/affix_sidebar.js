/* 
 * This code is used to fix position of sidebars on top when page is scrolled down
 */

jQuery( document ).ready( function()
{
	var affix_obj = jQuery( ".evo_container", ".evo_sidebar_col" );

	if( !affix_obj.length )
	{	// Nothing to affix:
		return;
	}

	var affix_obj_top = affix_obj.offset().top;

	// Wrap sidebar containers:
	affix_obj.wrapAll( '<div class="sidebar_wrapper"></div>' );

	var wrapper       = affix_obj.parent(),
		wrapper_width = wrapper.outerWidth();

	wrapper.affix( {
			offset: {
				top: function() {
					return affix_obj_top - get_affix_offset() - parseInt( wrapper.css( "margin-top" ) );
				}
			}
		} );

	// This is needed when we refresh the page that was already scrolled and the sidebar is already affixed.
	// The affix.bs.affix event does not get trigger in this case!
	if( wrapper.hasClass( 'affix' ) && ! wrapper.attr( 'style' ) && ! jQuery( 'div.sidebar_placeholder' ).length )
	{
		affix_sidebar();
		check_sidebar_overflow();
	}

	wrapper.on( "affix.bs.affix", function() {
			affix_sidebar();
			check_sidebar_overflow();
		} );

	wrapper.on( "affixed-top.bs.affix", function() {
			// Remove the placeholder:
			if( jQuery( 'div.sidebar_placeholder' ).length )
			{
				wrapper.unwrap();
			}

			// Reset wrapper style:
			wrapper.css( { "width": "", "top": "", "z-index": "" } );
		} );

	function get_affix_offset()
	{
		var evobar             = jQuery( '#evo_toolbar'),
			site_header        = jQuery( '#evo_site_header' ),
			evobar_height      = evobar.length ? evobar.height() : 0,
			site_header_height = site_header.length ? site_header.height() : 0;

		return evobar_height + site_header_height + 20;
	}

	function affix_sidebar()
	{
		// Create a placeholder for the affix obj berfore we fix it into position:
		wrapper.wrap( '<div class="sidebar_placeholder"></div>' );

		var placeholder = wrapper.parent();
		placeholder.css( 'width', '100%' );

		// Fix wrapper into position:
		wrapper.css( { "width": wrapper_width, "top": get_affix_offset(), "z-index": 1050 } );
	}

	function check_sidebar_overflow()
	{
		var content_col     = jQuery( '.evo_content_col' ),
			exceed_viewport = window.innerHeight < ( wrapper.height() + get_affix_offset() ),
			exceed_content  = wrapper.height() > content_col.height();

		if( exceed_viewport || exceed_content )
		{
			wrapper.addClass( 'affix-forced-top' );
		}
		else
		{
			wrapper.removeClass( 'affix-forced-top' );
		}
	}

	jQuery( window ).on( "resize", function()
		{
			var placeholder = jQuery( '.sidebar_placeholder' );

			if( placeholder.length )
			{	// Adapt same width as placeholder:
				wrapper.css( { 'width': placeholder.outerWidth() } );
				wrapper_width = placeholder.outerWidth();
			}
			else
			{   // Reset wrapper style:
				wrapper.css( { "width": "", "top": "", "z-index": "" } );
			}
			affix_sidebar();
			check_sidebar_overflow();
		} );

} );
