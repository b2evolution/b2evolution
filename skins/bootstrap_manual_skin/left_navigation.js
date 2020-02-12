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
	function change_position_leftnav( $sidebar, $sidebarSpacer )
	{
		if( has_touch_event )
		{ // Don't fix the objects on touch devices
			return;
		}

		if( $sidebar.length )
		{	// Sidebar exists
			var column_top_point = $sidebar.offset().top;
			var sidebar_top_start_point = top_start_point;
			var sidebar_height = $sidebar.outerHeight();
			jQuery( sidebar_selectors ).each( function()
			{
				if( $sidebar.offset().left == jQuery( this ).offset().left )
				{	// This is the same column:
					if( column_top_point > jQuery( this ).offset().top )
					{	// Find the toppest point of the column:
						column_top_point = jQuery( this ).offset().top;
					}
					if( jQuery( this ).attr( 'id' ) != $sidebar.attr( 'id' ) )
					{	// This another sidebar but from the same column:
						sidebar_height += jQuery( this ).outerHeight();
						if( $sidebar.offset().top > jQuery( this ).offset().top )
						{
							sidebar_top_start_point += jQuery( this ).outerHeight();
						}
					}
				}
			} );

			$sidebar.css( 'width', $sidebar.parent().width() );
			if( ! $sidebar.hasClass( 'fixed' ) &&
					sidebar_height < jQuery( window ).height() &&
					jQuery( window ).scrollTop() > $sidebar.offset().top - sidebar_top_start_point )
			{	// Make sidebar as fixed if we scroll down:
				$sidebar.before( $sidebarSpacer );
				$sidebar.addClass( 'fixed' ).css( 'top', sidebar_top_start_point + 'px' );
			}
			else if( $sidebar.hasClass( 'fixed' ) &&
					jQuery( window ).scrollTop() < $sidebarSpacer.offset().top - sidebar_top_start_point )
			{	// Remove 'fixed' class from sidebar if we scroll to the top of page:
				$sidebar.removeClass( 'fixed' ).css( 'top', '' );
				$sidebarSpacer.remove();
			}

			if( $sidebar.hasClass( 'fixed' ) )
			{	// Check and fix an overlapping of footer with sidebar:
				$sidebar.css( 'top', sidebar_top_start_point + 'px' );
				var diff = parseInt( column_top_point + sidebar_height - jQuery( '#evo_site_footer' ).offset().top );
				if( diff >= 0 )
				{
					$sidebar.css( 'top', parseInt( sidebar_top_start_point - diff - 5 ) + 'px' );
				}
			}
		}
	}

	var top_start_point = ( jQuery( '#evo_toolbar' ).length > 0 ? 28 : 0 ) + 10;
	var site_header_obj = jQuery( '#evo_site_header' );
	if( site_header_obj.length && site_header_obj.css( 'position' ) == 'fixed' )
	{	// Shift fixed sidebar down on height of site header:
		top_start_point += site_header_obj.height();
	}

	var sidebar_selectors = '#evo_container__sidebar, #evo_container__sidebar_2, #evo_container__sidebar_single';
	jQuery( sidebar_selectors ).each( function()
	{
		var $sidebar = jQuery( this );
		if( $sidebar.outerHeight( true ) + 70/* footer height */ < jQuery( window ).height() )
		{
			var $sidebarSpacer = jQuery( '<div />', {
					"class" : "evo_container__sidebar fixed_spacer",
					"height": $sidebar.outerHeight( true )
				} );
			jQuery( window ).bind( 'scroll resize', function ()
			{
				change_position_leftnav( $sidebar, $sidebarSpacer );
			} );
			change_position_leftnav( $sidebar, $sidebarSpacer );
		}
	} );
} );