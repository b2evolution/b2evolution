/**
 * This file is used for widgets designer mode
 */

jQuery( document ).on( 'mouseover', '.evo_container[data-code]', function()
{	// Initialize and Show container designer block:
	var container = jQuery( this );

	// To be sure all previous designer blocks are hidden before show new one:
	jQuery( '.evo_designer__container' ).hide();

	var container_block = jQuery( '.evo_designer__container[data-code="' + container.data( 'code' ) + '"]' );
	if( container_block.length )
	{	// Just display a designer block if it already has been initialized previous time:
		evo_widget_update_container_position( container );
		return;
	}

	// Fix z-index issue:
	evo_widget_fix_parent_zindex( container );

	// Initialize a container designer block only first time:
	var container_actions = '';
	if( container.data( 'can-edit' ) == '1' )
	{	// Display a panel with actions if current user has a permission to edit widgets:
		container_actions = '<div class="evo_designer__actions">' +
				b2evo_widget_icon_list +
				b2evo_widget_icon_add +
			'</div>';
	}
	jQuery( 'body' ).append( '<div class="evo_designer evo_designer__container" data-code="' + container.data( 'code' ) + '">' +
			'<div><div class="evo_designer__title">Container: ' + container.data( 'name' ) + '</div>' + container_actions + '</div>' +
		'</div>' );
	evo_widget_update_container_position( container );
} );

jQuery( document ).on( 'mouseover', '.evo_widget[data-id]', function()
{	// Initialize and Show widget designer block:
	var widget = jQuery( this );

	if( jQuery( '.evo_designer.evo_designer__status_process[data-container="' + widget.data( 'container' ) + '"]' ).length ||
	    jQuery( '.evo_designer.evo_designer__status_success[data-container="' + widget.data( 'container' ) + '"]' ).length )
	{	// Don't show other widget designer block from the same container while previous one in process:
		return;
	}

	// To be sure all previous designer blocks are hidden before show new one:
	jQuery( '.evo_designer' ).removeClass( 'evo_designer__subcontainer' ).hide();

	// Initialize designer block for widget:
	evo_widget_initialize_designer_block( widget );
} );


/**
 * Initialize designer block for widget
 *
 * @param object Widget
 */
function evo_widget_initialize_designer_block( widget )
{
	var designer_block_selector = evo_widget_designer_block_selector( widget );
	if( jQuery( designer_block_selector ).length )
	{	// Just display a designer block if it already has been initialized previous time:
		evo_widget_update_designer_position( widget );
		return;
	}

	// Fix z-index issue:
	evo_widget_fix_parent_zindex( widget );

	// Initialize a widget designer block only first time:
	jQuery( 'body' ).append( '<div class="evo_designer evo_designer__widget" data-id="' + widget.data( 'id' ) + '" data-container="' + widget.data( 'container' ) + '">' +
			'<div><div class="evo_designer__title">' + widget.data( 'type' ) + '</div></div>' +
		'</div>' );
	evo_widget_update_designer_position( widget );
	if( widget.data( 'can-edit' ) == '1' )
	{	// Display a panel with actions if current user has a permission to edit widget:
		jQuery( '>div', designer_block_selector ).append( '<div class="evo_designer__actions">' +
				b2evo_widget_icon_up +
				b2evo_widget_icon_down +
				b2evo_widget_icon_disable +
			'</div>' );
		if( widget.next( '.evo_widget' ).length == 0 )
		{	// Hide action icon to move widget down if it is the last widget in container:
			jQuery( designer_block_selector ).find( '.evo_designer__action_order_down' ).hide();
		}
		if( widget.prev( '.evo_widget' ).length == 0 )
		{	// Hide action icon to move widget up if it is the first widget in container:
			jQuery( designer_block_selector ).find( '.evo_designer__action_order_up' ).hide();
		}
	}
}

jQuery( document ).on( 'mouseover', '.evo_designer__widget', function()
{	// Show container designer block:
	var widget = jQuery( evo_widget_selector( jQuery( this ) ) );
	var container_block = jQuery( evo_widget_container_block_selector( widget.data( 'container' ) ) );
	if( ! container_block.is( ':visible' ) )
	{
		container_block.show();
	}
} );

jQuery( document ).on( 'click', '.evo_designer__action_add, .evo_designer__action_list', function( e )
{	// Link to add widget or to manage widgets of the container:
	if( typeof( b2evo_widget_add_url ) != 'undefined' )
	{	// If global widget add form url is defined:
		var container_block = jQuery( this ).closest( '.evo_designer__container' );
		var container = jQuery( '.evo_container[data-code="' + container_block.data( 'code' ) + '"]' );
		if( container.length && container.data( 'can-edit' ) == '1' )
		{	// Load widget adding list only if it is allowed for current user:
			var widget_action_url = jQuery( this ).hasClass( 'evo_designer__action_add' ) ? b2evo_widget_add_url : b2evo_widget_list_url;
			jQuery( '.evo_customizer__wrapper', window.parent.document ).removeClass( 'evo_customizer__collapsed' );
			jQuery( '#evo_customizer__backoffice', window.parent.document ).get( 0 ).contentWindow.location
				.href = widget_action_url.replace( '$container$', container.data( 'name' ) ).replace( '$container_code$', container.data( 'code' ) );
		}
	}
} );

jQuery( document ).on( 'click', '.evo_designer__widget', function( e )
{	// Link to edit widget:
	if( jQuery( e.target ).is( '.evo_designer__action' )  )
	{	// Ignore if click is on action icons:
		return;
	}

	var widget = jQuery( evo_widget_selector( jQuery( this ) ) );

	if( typeof( b2evo_widget_edit_url ) != 'undefined' )
	{	// If global widget edit form url is defined:
		var widget_ID = jQuery( this ).data( 'id' );
		if( widget.length && widget.data( 'can-edit' ) == '1' )
		{	// Load widget edit form only if it is allowed for current user:
			jQuery( '.evo_customizer__wrapper', window.parent.document ).removeClass( 'evo_customizer__collapsed' );
			jQuery( '#evo_customizer__backoffice', window.parent.document ).get( 0 ).contentWindow.location
				.href = b2evo_widget_edit_url.replace( '$wi_ID$', widget_ID );
		}
	}

	if( widget.hasClass( 'widget_core_subcontainer' ) || widget.hasClass( 'widget_core_subcontainer_row' ) )
	{	// Add subcontainer style to widget designer block to mark it with blue border style:
		jQuery( this ).addClass( 'evo_designer__subcontainer' );
		evo_subcontainer_widgets = new Array();
		widget.children( '.evo_widget' ).each( function()
		{	// Store coordinates of all children:
			evo_subcontainer_widgets.push( new Array( jQuery( this ),
				jQuery( this ).offset().top - 3,
				jQuery( this ).offset().left - 3,
				jQuery( this ).offset().top + jQuery( this ).height() + 3,
				jQuery( this ).offset().left + jQuery( this ).width() + 3 ) );
		} );
	}
} );

jQuery( document ).on( 'mouseout', '.evo_designer__widget', function( e )
{	// Hide widget designer block:
	if( ! jQuery( e.toElement ).closest( '.evo_designer__widget' ).length )
	{	// Hide it only when cursor is really out of designer block:
		evo_widget_hide_designer_block( jQuery( this ) );
	}
} );

jQuery( document ).on( 'mouseout', '.evo_designer__container', function( e )
{	// Hide container designer block:
	if( ! jQuery( e.toElement ).closest( '.evo_designer__container' ).length )
	{	// Hide it only when cursor is really out of designer block:
		jQuery( this ).hide();
	}
} );

jQuery( document ).on( 'mousemove', function( e )
{	// Detect if mouse cursor was out designer blocks:
	if( jQuery( '.evo_designer' ).is( ':visible' ) &&
	    ! jQuery( e.target ).hasClass( 'evo_designer' ) &&
	    ! jQuery( e.target ).closest( '.evo_designer' ).length &&
	    ! jQuery( e.target ).closest( '.evo_container[data-code]' ).length &&
	    ! jQuery( e.target ).closest( '.evo_widget[data-id]' ).length )
	{	// Hide all designer blocks if mouse cursor was out them but they were not hidden by some wrong reason:
		jQuery( '.evo_designer' ).removeClass( 'evo_designer__subcontainer' ).hide();
	}

	if( jQuery( '.evo_designer__subcontainer' ).length > 0 &&
	    typeof( evo_subcontainer_widgets ) != 'undefined' &&
			evo_subcontainer_widgets.length > 0 )
	{	// If sub-container is selected to edit its widgets:
		for( var i = 0; i < evo_subcontainer_widgets.length; i++ )
		{	// Detect what widget designer block should be visible depending on current cursor position:
			var widget = evo_subcontainer_widgets[i][0];
			if( widget.data( 'id' ) == jQuery( e.target ).data( 'id' ) ||
			    jQuery( e.target ).closest( '.evo_designer__widget[data-id=' + widget.data( 'id' ) + ']' ).length > 0 )
			{	// Skip it because currently cursor is over displayed dsigner block of one of sub-container widgets:
				continue;
			}

			var designer_block = jQuery( evo_widget_designer_block_selector( widget ) );
			if( e.pageX >= evo_subcontainer_widgets[i][2] && e.pageY >= evo_subcontainer_widgets[i][1] && // top-left point
			    e.pageX <= evo_subcontainer_widgets[i][4] && e.pageY <= evo_subcontainer_widgets[i][3] ) // bottom-right point
			{	// Initialize nad display designer block because cursor is over current widget:
				evo_widget_initialize_designer_block( widget );
				// Set z-index to make it over designer block of current sub-container:
				designer_block.css( 'z-index', '10006' );
			}
			else
			{	// Hide designer block:
				designer_block.hide();
			}
		}
	}
} );

jQuery( document ).on( 'click', '.evo_designer__action_order_up, .evo_designer__action_order_down', function()
{	// Change an order of widget:
	var designer_block = jQuery( this ).closest( '.evo_designer__widget' );
	var widget = jQuery( evo_widget_selector( designer_block ) );
	var order_type = jQuery( this ).hasClass( 'evo_designer__action_order_up' ) ? 'up' : 'down';

	// Mark current widget with process class:
	designer_block.removeClass( 'evo_designer__status_failed' ).addClass( 'evo_designer__status_process' );

	// Change an order of the widget with near widget:
	evo_widget_reorder( widget, order_type );

	var widgets_ids = [];
	var container_wrapper = evo_widget_container_wrapper( widget );
	container_wrapper.children( '.evo_widget[data-id]' ).each( function()
	{
		widgets_ids.push( jQuery( this ).data( 'id' ) );
	} );

	jQuery.ajax(
	{
		type: 'POST',
		url: htsrv_url + 'anon_async.php',
		data: {
			'blog': b2evo_widget_blog,
			'crumb_widget': b2evo_widget_crumb,
			'action': 'reorder_widgets',
			'container': widget.data( 'container' ),
			'widgets': widgets_ids,
		},
		success: function( result )
		{	// If order has been updated successfully:
			result = ajax_debug_clear( result );
			if( result != '' )
			{	// Error:
				evo_widget_display_error( result, widget, order_type );
			}
			else
			{	// Success:
				designer_block.removeClass( 'evo_designer__status_process evo_designer__status_failed' ).addClass( 'evo_designer__status_success' );
				evo_widget_update_designer_position( widget );
				setTimeout( function()
				{
					evo_widget_hide_designer_block( designer_block );
					designer_block.removeClass( 'evo_designer__status_success' );
				}, 500 );
			}
		},
		error: function( jqXHR, textStatus, errorThrown )
		{	// Display error text on error request:
			evo_widget_display_error( evo_js_lang_error_communicating + ' (' + textStatus + ': ' + errorThrown + ')', widget, order_type );
		}
	} );
} );

jQuery( document ).on( 'click', '.evo_designer__action_disable', function()
{	// Disable widget:
	var designer_block = jQuery( this ).closest( '.evo_designer__widget' );
	var widget = jQuery( evo_widget_selector( designer_block ) );

	// Mark current widget with process class:
	designer_block.removeClass( 'evo_designer__status_failed' ).addClass( 'evo_designer__status_process' );

	jQuery.ajax(
	{
		type: 'POST',
		url: htsrv_url + 'anon_async.php',
		data: {
			'blog': b2evo_widget_blog,
			'crumb_widget': b2evo_widget_crumb,
			'action': 'disable_widget',
			'wi_ID': widget.data( 'id' ),
		},
		success: function( result )
		{	// If order has been updated successfully:
			result = ajax_debug_clear( result );
			if( result != '' )
			{	// Error:
				evo_widget_display_error( result, widget, 'disable' );
			}
			else
			{	// Success:
				designer_block.removeClass( 'evo_designer__status_process evo_designer__status_failed' ).addClass( 'evo_designer__status_success' );
				setTimeout( function()
				{
					var container = widget.parent();
					evo_widget_hide_designer_block( designer_block );
					widget.slideUp( 400, function()
					{	// Remove widget and designer block from page completely after animation:
						widget.remove();
						designer_block.remove();
						// Update visibility of up/down action icons of first/last widgets:
						evo_widget_update_order_actions( container );
					} );
				}, 500 );
			}
		},
		error: function( jqXHR, textStatus, errorThrown )
		{	// Display error text on error request:
			evo_widget_display_error( evo_js_lang_error_communicating + ' (' + textStatus + ': ' + errorThrown + ')', widget, 'disable' );
		}
	} );
} );


jQuery( document ).on( 'ready', function()
{
	jQuery( 'div' ).scroll( function()
	{	// Update position of all visible designer blocks on scroll all divs with enabled css overflow scroll property:
		jQuery( '.evo_designer__widget[data-id]:visible' ).each( function()
		{
			evo_widget_update_designer_position( jQuery( evo_widget_selector( jQuery( this ) ) ) );
		} );
	} );
} );


/**
 * Get jQuery selector for widget by designer block
 *
 * @param object Designer block
 * @returns string
 */
function evo_widget_selector( designer_block )
{
	return '.evo_widget[data-id=' + designer_block.data( 'id' ) + ']';
}


/**
 * Get jQuery selector for designer block by widget
 *
 * @param object Widget
 * @returns string
 */
function evo_widget_designer_block_selector( widget )
{
	return '.evo_designer__widget[data-id=' + widget.data( 'id' ) + ']';
}


/**
 * Get jQuery selector for container designer block by widget
 *
 * @param string Container code
 * @returns string
 */
function evo_widget_container_block_selector( container_code )
{
	return '.evo_designer__container[data-code="' + container_code + '"]';
}


/**
 * Display an error after failed widget action
 * 
 * @param string Error message
 * @param object Widget
 * @param string Action: 'up', 'down', 'disable'
 */
function evo_widget_display_error( error, widget, action )
{
	jQuery( evo_widget_designer_block_selector( widget ) ).removeClass( 'evo_designer__status_process' ).addClass( 'evo_designer__status_failed' );
	alert( error );
	if( action == 'up' || action == 'down' )
	{	// Revert widget order back:
		evo_widget_reorder( widget, action == 'up' ? 'down' : 'up' );
	}
}


/**
 * Change an order of the widget with near widget in same container
 *
 * @param object Widget
 * @param string Order direction: 'up', 'down'
 */
function evo_widget_reorder( widget, direction )
{
	if( widget.length > 1 )
	{	// Delete duplicted object if it was created e-g on sub-container:
		delete( widget[1] );
	}
	if( direction == 'up' )
	{	// Move HTML of the widget before previous widget:
		var near_widget = widget.prev();
		near_widget.before( widget );
	}
	else
	{	// Move HTML of the widget after next widget:
		var near_widget = widget.next();
		near_widget.after( widget );
	}

	// Update visibility of up/down action icons of first/last widgets:
	evo_widget_update_order_actions( evo_widget_container_wrapper( widget ) );

	evo_widget_update_designer_position( widget );
	evo_widget_update_designer_position( near_widget, false );
}


/**
 * Update visibility of up/down action icons of first/last widgets:
 *
 * @param object Widget
 */
function evo_widget_update_order_actions( container )
{
	var container_widgets = container.children( '.evo_widget' );
	var widget_num = 1;
	container_widgets.each( function()
	{
		var designer_block = jQuery( evo_widget_designer_block_selector( jQuery( this ) ) );
		if( designer_block.length )
		{	// If designer block is initialized:
			designer_block.find( '.evo_designer__action_order_up, .evo_designer__action_order_down' ).show();
			if( widget_num == 1 )
			{	// Hide action icon to move widget up for the first widget in container:
				designer_block.find( '.evo_designer__action_order_up' ).hide();
			}
			if( widget_num == container_widgets.length )
			{	// Hide action icon to move widget up for the last widget in container:
				designer_block.find( '.evo_designer__action_order_down' ).hide();
			}
		}
		widget_num++;
	} );
}


/**
 * Update position of widget designer block
 *
 * @param object Widget
 * @param boolean Show widget desginer block, TRUE by default
 */
function evo_widget_update_designer_position( widget, show )
{
	var widget_class = '';
	var widget_left = widget.offset().left - 3;
	var widget_width = widget.outerWidth() + 5;
	var window_width = jQuery( window ).width();
	if( widget_left < 0 )
	{	// Limit container designer block left podition to left window border;
		widget_left = 0;
	}
	if( widget_width > window_width - widget_left - 27 )
	{	// Limit container designer block width to right window border:
		widget_width = window_width - widget_left - 27;
		// Additional class to fix style for outside container designer block:
		widget_class = 'evo_widget__outside';
	}

	var designer_block = jQuery( evo_widget_designer_block_selector( widget ) );
	designer_block.css( {
			'top': widget.offset().top - 3,
			'left': widget_left,
			'width': widget_width,
			'height': widget.outerHeight() + 5,
		} )
		.addClass( widget_class );
	if( typeof( show ) == 'undefined' || show )
	{	// Show widget desginer block:
		designer_block.show();
	}

	// Also update container position:
	evo_widget_update_container_position( widget.parent() );
}


/**
 * Update position of container designer block
 *
 * @param object Container
 */
function evo_widget_update_container_position( container )
{
	var container_left = container.offset().left - 3;
	var container_width = container.outerWidth() + 5;
	var window_width = jQuery( window ).width();
	if( container_left < 0 )
	{	// Limit container designer block left podition to left window border:
		container_left = 0;
	}
	if( container_width > window_width - container_left )
	{	// Limit container designer block width to right window border:
		container_width = window_width - container_left - 27;
	}

	jQuery( evo_widget_container_block_selector( container.data( 'code' ) ) )
		.css( {
			'top': container.offset().top - 3,
			'left': container_left,
			'width': container_width,
			'height': container.outerHeight() + 5,
		} )
		.show();
}


/**
 * Hide widget designer block
 *
 * @param object Designer block
 */
function evo_widget_hide_designer_block( designer_block )
{
	if( ! designer_block.hasClass( 'evo_designer__status_process' ) &&
	    ! designer_block.hasClass( 'evo_designer__status_failed' ) )
	{	// Hide only when widget is not in process:
		designer_block.hide();
	}
}


/**
 * Fix issue of parent z-index to make designer mode work properly
 *
 * @param object Source
 */
function evo_widget_fix_parent_zindex( parent )
{
	while( parent )
	{
		if( ! parent.hasClass( 'evo_widget' ) &&
			parent.css( 'z-index' ) != 'auto' &&
			parent.css( 'z-index' ) != 0 )
		{	// Change not default z-index to auto, because it breaks designer mode structure:
			parent.css( 'z-index', 'auto' );
		}
		if( parent.prop( 'nodeName' ) == 'BODY' )
		{	// Stop on body html element and don't up:
			break;
		}
		// Switch to next parent:
		parent = parent.parent();
	}
}


/**
 * Get container or sub-container wrapper
 *
 * @param object Widget
 * @return object Container/Sub-container
 */
function evo_widget_container_wrapper( widget )
{
	// Check if the requested widget is located in sub-container:
	var container_wrapper = widget.closest( '.widget_core_subcontainer[data-id!=' + widget.data( 'id' ) + ']' );

	if( container_wrapper.length == 0 )
	{	// If widget is in normal container and not in sub-container:
		container_wrapper = widget.parent();
	}

	return container_wrapper;
}