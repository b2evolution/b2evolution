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

		if( $sidebar.data( 'prevent-fixing' ) )
		{	// Prevent fixing, e.g. when single sidebar is located under main sidebar,
			// because main sidebar is copied into single sidebar so it is fixed automatically:
			return;
		}

		if( $sidebar.size() )
		{ // Sidebar exists
			if( $sidebar.attr( 'id' ) == 'evo_container__sidebar_single' )
			{
				if( jQuery( window ).width() < 1500 )
				{	// If single sidebar is loaceted under main sidebar:
					if( ! jQuery( '#evo_container__sidebar' ).data( 'prevent-fixing' ) )
					{	// Move all widgets from main sidebar under single sidebar:
						if( ! jQuery( '#evo_container__sidebar_moved' ).size() )
						{	// Create temp div onve:
							$sidebar.append( '<div id="evo_container__sidebar_moved"></div>' );
						}
						jQuery( '#evo_container__sidebar' ).children().each( function()
						{
							jQuery( '#evo_container__sidebar_moved' ).append( jQuery( this ) );
						} );
						jQuery( '#evo_container__sidebar' ).data( 'prevent-fixing', true );
					}
				}
				else if( jQuery( '#evo_container__sidebar' ).data( 'prevent-fixing' ) )
				{	// Extra large screen where single sidebar is located on the right:
					jQuery( '#evo_container__sidebar_moved' ).children().each( function()
					{	// Move back main sidebar widgets to the orignal container:
						jQuery( '#evo_container__sidebar' ).append( jQuery( this ) );
					} );
					jQuery( '#evo_container__sidebar' ).removeData( 'prevent-fixing' );
				}
			}

			$sidebar.css( 'width', $sidebar.parent().width() );
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
				var diff = parseInt( $sidebar.offset().top + $sidebar.outerHeight() - jQuery( '.evo_container__site_footer' ).offset().top );
				if( diff >= 0 )
				{
					$sidebar.css( 'top', parseInt( sidebar_top - diff - 5 ) + 'px' );
				}
			}
		}
	}

	var sidebar_top = ( jQuery( '#evo_toolbar' ).length > 0 ? 28 : 0 ) + ( jQuery( '.sitewide_header, .evo_container__site_header' ).length > 0 ? 54 : 0 );
	jQuery( '#evo_container__sidebar, #evo_container__sidebar_single' ).each( function()
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