/* 
 * This code is used to fix left navigation bar when page is scrolled down
 */

jQuery( document ).ready( function()
{
	var has_touch_event;
	window.addEventListener( 'touchstart', function set_has_touch_event ()
	{
		has_touch_event = true;
		// Remove event listener once fired, otherwise it'll kill scrolling
		window.removeEventListener( 'touchstart', set_has_touch_event );
	}, false );

	/**
	 * Change header position to fixed or revert to static
	 */
	function change_position_leftnav()
	{
		if( has_touch_event )
		{ // Don't fix the objects on touch devices
			return;
		}

		if( sidebar_size )
		{ // Sidebar exists
			if( !$sidebar.hasClass( 'fixed' ) && jQuery( window ).scrollTop() > $sidebar.offset().top - sidebar_top )
			{ // Make sidebar as fixed if we scroll down
				$sidebar.before( $sidebarSpacer );
				$sidebar.addClass( 'fixed' ).css( 'top', sidebar_top + 'px' );
			}
			else if( $sidebar.hasClass( 'fixed' )  && jQuery( window ).scrollTop() < $sidebarSpacer.offset().top - sidebar_top )
			{ // Remove 'fixed' class from sidebar if we scroll to the top of page
				$sidebar.removeClass( 'fixed' ).css( 'top', '' );
				$sidebarSpacer.remove();
			}

			if( $sidebar.hasClass( 'fixed' ) )
			{ // Check and fix an overlapping of footer with sidebar
				$sidebar.css( 'top', sidebar_top + 'px' );
				var diff = parseInt( $sidebar.offset().top + $sidebar.outerHeight() - jQuery( '.evo_container__footer' ).offset().top );
				if( diff >= 0 )
				{
					$sidebar.css( 'top', parseInt( sidebar_top - diff - 5 ) + 'px' );
				}
			}
		}
	}

	var sidebar_shift = jQuery( '.sitewide_header' ).length > 0 ? 54 : 0;
	var $sidebar = jQuery( '#evo_container__sidebar' );
	if( $sidebar.outerHeight( true ) + 70/* footer height */ < jQuery( window ).height() )
	{
		var sidebar_size = $sidebar.size();
		var sidebar_top = ( jQuery( '#evo_toolbar' ).length > 0 ? 28 : 0 ) + sidebar_shift;
		var $sidebarSpacer = $( '<div />', {
				"class" : "evo_container__sidebar fixed_spacer",
				"height": $sidebar.outerHeight()
			} );
		jQuery( window ).scroll( function ()
		{
			change_position_leftnav();
		} );
		jQuery( window ).resize( function()
		{
			change_position_leftnav();
		} );
	}

} );