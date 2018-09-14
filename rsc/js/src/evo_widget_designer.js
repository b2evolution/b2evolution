/**
 * This file is used for widgets designer mode
 */

jQuery( document ).on( 'mouseover', '.evo_container[data-code]', function()
{	// Initialize and Show container designer block:
	var container = jQuery( this );

	// To be sure all previous designer blocks are hidden before show new one:
	jQuery( '.evo_designer__container' ).hide();

	// Initialize designer block for widget container:
	evo_widget_initialize_designer_container_block( container );
} );

/**
 * Initialize designer block for widget container
 *
 * @param object Widget
 */
function evo_widget_initialize_designer_container_block( container )
{
	if( jQuery( evo_widget_container_block_selector( container.data( 'code' ) ) ).length )
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
}

jQuery( document ).on( 'mouseover', '.evo_widget[data-id]', function()
{	// Initialize and Show widget designer block:
	var widget = jQuery( this );

	if( jQuery( '.evo_designer.evo_designer__status_process[data-container="' + widget.data( 'container' ) + '"]' ).length ||
	    jQuery( '.evo_designer.evo_designer__status_success[data-container="' + widget.data( 'container' ) + '"]' ).length )
	{	// Don't show other widget designer block from the same container while previous one in process:
		return;
	}

	// To be sure all previous designer blocks are hidden before show new one:
	jQuery( '.evo_designer' ).removeClass( 'evo_designer__subcontainer_active evo_designer__subcontainer_inactive' ).hide();
	// Unmark active subcontainer widget:
	jQuery( '.evo_widget.evo_widget__subcontainer_active' ).removeClass( 'evo_widget__subcontainer_active' );

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

	// Get all same widgets from the same container:
	var same_widgets = evo_widget_get_duplicates( widget );

	// Initialize a widget designer block only first time:
	var designer_block_start = '<div class="evo_designer evo_designer__widget" data-id="' + widget.data( 'id' ) + '" data-container="' + widget.data( 'container' ) + '">';
	var designer_block_end = '</div>';
	jQuery( 'body' ).append( designer_block_start + '<div><div class="evo_designer__title">' + widget.data( 'type' ) + '</div></div>' + designer_block_end );
	if( widget.data( 'can-edit' ) == '1' )
	{	// Display a panel with actions if current user has a permission to edit widget:
		jQuery( '>div', designer_block_selector ).append( '<div class="evo_designer__actions">' +
				b2evo_widget_icon_up +
				b2evo_widget_icon_down +
				b2evo_widget_icon_disable +
			'</div>' );
		if( same_widgets.eq( same_widgets.length - 1 ).next( '.evo_widget' ).length == 0 )
		{	// Hide action icon to move widget down if it is the last widget in container:
			jQuery( designer_block_selector ).find( '.evo_designer__action_order_down' ).hide();
		}
		if( same_widgets.eq( 0 ).prev( '.evo_widget' ).length == 0 )
		{	// Hide action icon to move widget up if it is the first widget in container:
			jQuery( designer_block_selector ).find( '.evo_designer__action_order_up' ).hide();
		}
	}
	for( var w = 2; w <= same_widgets.length; w++ )
	{	// Initialize additional designer blocks for blocks of the same widget, e.g. for items/posts list widget in Menu container:
		jQuery( 'body' ).append( designer_block_start + designer_block_end );
	}
	// Set correct position for new created designer block:
	evo_widget_update_designer_position( widget );

	if( widget.hasClass( 'widget_core_subcontainer' ) )
	{	// If widget is a subcontainer,
		// Set unique number for subcontainer because same subcontainer widget may be used several times on the same page
		var subcontainer_unique_num = jQuery( '.evo_widget.widget_core_subcontainer[data-unique-num]' ).length + 1;
		widget.attr( 'data-unique-num', subcontainer_unique_num );
		jQuery( designer_block_selector ).attr( 'data-unique-num', subcontainer_unique_num );
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
		var container = jQuery( evo_widget_container_selector( container_block ) );
		if( container.length && container.data( 'can-edit' ) == '1' )
		{	// Load widget adding list only if it is allowed for current user:
			var widget_action_url = jQuery( this ).hasClass( 'evo_designer__action_add' ) ? b2evo_widget_add_url : b2evo_widget_list_url;
			jQuery( '.evo_customizer__wrapper', window.parent.document ).removeClass( 'evo_customizer__collapsed' );
			jQuery( '#evo_customizer__backoffice', window.parent.document ).get( 0 ).contentWindow.location
				.href = widget_action_url.replace( '$container$', container.data( 'name' ) ).replace( '$container_code$', container.data( 'code' ) );
		}
	}
} );

jQuery( document ).on( 'click', '.evo_designer__container', function( e )
{	// Link to add new widget to empty container:
	if( jQuery( e.target ).is( '.evo_designer__action' ) )
	{	// Ignore if click is on action icons:
		return;
	}

	if( jQuery( evo_widget_container_selector( jQuery( this ) ) ).find( '.evo_widget' ).length > 0 )
	{	// Skip not empty container:
		return;
	}

	// Call event to add new widget:
	jQuery( this ).find( '.evo_designer__action_add' ).click();
} );

jQuery( document ).on( 'click', '.evo_designer__widget', function( e )
{	// Link to edit widget:
	if( jQuery( e.target ).is( '.evo_designer__action' ) )
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
	{	// If widget is a subcontainer:
		if( ! jQuery( this ).hasClass( 'evo_designer__subcontainer_active' ) )
		{
			// Mark currently active parent subcontainer to inactive:
			jQuery( '.evo_designer__subcontainer_active' ).addClass( 'evo_designer__subcontainer_inactive' );
			// Add subcontainer style to widget designer block to mark it with blue border style:
			jQuery( this ).addClass( 'evo_designer__subcontainer_active' );
			// Mark current subcontainer widget to active status by temp class:
			widget.addClass( 'evo_widget__subcontainer_active' );

			// Store coordinates of all child widgets(and sub-containers) to know when cursor over and out,
			// because z-index doesn't work properly with child element if parent already has z-index:
			evo_subcontainer_widgets = new Array();
			evo_subcontainer_containers = new Array();
			var subcontainer_wrapper = widget;
			if( widget.hasClass( 'widget_core_subcontainer_row' ) )
			{	// This widget contains several sub-containers:
				subcontainer_wrapper = widget.find( '.evo_container' );
				subcontainer_wrapper.each( function()
				{	// We need these data to display sub-containers without widgets:
					if( jQuery( this ).find( '.evo_widget' ).length > 0 )
					{	// Skip not empty containers, because they are visible by event of child widgets:
						return;
					}
					evo_subcontainer_containers.push( new Array(
						jQuery( this ).data( 'code' ),
						jQuery( this ).offset().top - 3,
						jQuery( this ).offset().left - 3,
						jQuery( this ).offset().top + jQuery( this ).height() + 3,
						jQuery( this ).offset().left + jQuery( this ).width() + 3 ) );
				} );
			}
			subcontainer_wrapper.children( '.evo_widget' ).each( function()
			{
				evo_subcontainer_widgets.push( new Array(
					jQuery( this ).data( 'id' ),
					jQuery( this ).offset().top - 3,
					jQuery( this ).offset().left - 3,
					jQuery( this ).offset().top + jQuery( this ).height() + 3,
					jQuery( this ).offset().left + jQuery( this ).width() + 3 ) );
			} );
		}
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
		jQuery( '.evo_designer' ).removeClass( 'evo_designer__subcontainer_active evo_designer__subcontainer_inactive' ).hide();
		// Unmark active subcontainer widget:
		jQuery( '.evo_widget.evo_widget__subcontainer_active' ).removeClass( 'evo_widget__subcontainer_active' );
	}

	if( jQuery( '.evo_designer__subcontainer_active' ).length > 0 &&
	    ( ( typeof( evo_subcontainer_widgets ) != 'undefined' && evo_subcontainer_widgets.length > 0 ) || 
	      ( typeof( evo_subcontainer_containers ) != 'undefined' && evo_subcontainer_containers.length > 0 ) ) )
	{	// If sub-container is selected to edit its widgets:
		var active_subcontainer_designer_block = jQuery( '.evo_designer__subcontainer_active' );
		var subcontainer_zindex = active_subcontainer_designer_block.css( 'z-index' );
		var active_subcontainer_widgets = jQuery( '.evo_widget.evo_widget__subcontainer_active' );
		var one_subcontainer_is_displayed = true; // Single sub-container widget doesn't contain other sub-containers, Set TRUE in order to don't try to search empty sub-containers below.
		if( active_subcontainer_widgets.hasClass( 'widget_core_subcontainer_row' ) )
		{	// This widget may contains sub-containers:
			active_subcontainer_widgets = active_subcontainer_widgets.find( '.evo_container' );
			one_subcontainer_is_displayed = false;
		}
		for( var i = 0; i < evo_subcontainer_widgets.length; i++ )
		{	// Detect what widget designer block should be visible depending on current cursor position:
			var widget = active_subcontainer_widgets.children( '.evo_widget[data-id="' + evo_subcontainer_widgets[i][0] + '"]' );
			if( widget.length == 0 ||
			    widget.data( 'id' ) == jQuery( e.target ).data( 'id' ) ||
			    jQuery( e.target ).closest( '.evo_designer__widget[data-id=' + widget.data( 'id' ) + ']' ).length > 0 )
			{	// Skip it because currently cursor is over displayed designer block of one of sub-container widgets:
				continue;
			}

			if( e.pageX >= evo_subcontainer_widgets[i][2] && e.pageY >= evo_subcontainer_widgets[i][1] && // top-left point
			    e.pageX <= evo_subcontainer_widgets[i][4] && e.pageY <= evo_subcontainer_widgets[i][3] ) // bottom-right point
			{	// Initialize and display widget designer block because cursor is over current widget:
				evo_widget_initialize_designer_block( widget );
				// Set z-index to make it over designer block of current sub-container:
				jQuery( evo_widget_designer_block_selector( widget ) ).css( 'z-index', subcontainer_zindex + 2  );
				if( widget.parent().hasClass( 'evo_container' ) )
				{	// Initialize and display also designer block for container:
					evo_widget_initialize_designer_container_block( widget.parent() );
					one_subcontainer_is_displayed = true;
					jQuery( evo_widget_container_block_selector( widget.data( 'container' ) ) ).css( 'z-index', subcontainer_zindex + 1 );
					// Hide designer block of another container from the same current active subcontainer:
					jQuery( '.evo_widget.evo_widget__subcontainer_active .evo_container' ).each( function()
					{
						if( jQuery( this ).data( 'code' ) != widget.data( 'container' ) )
						{
							jQuery( evo_widget_container_block_selector( jQuery( this ).data( 'code' ) ) ).hide();
						}
					} );
				}
			}
			else
			{	// Hide designer block:
				jQuery( evo_widget_designer_block_selector( widget ) ).hide();
			}
		}
		if( ! one_subcontainer_is_displayed )
		{	// This widget may contains sub-containers but currently it is empty so we should probably display it right now:
			for( var i = 0; i < evo_subcontainer_containers.length; i++ )
			{	// Detect what sub-container designer block should be visible depending on current cursor position:
				var subcontainer = active_subcontainer_widgets.filter( '.evo_container[data-code="' + evo_subcontainer_containers[i][0] + '"]' );
				if( subcontainer.length == 0 ||
				    subcontainer.data( 'code' ) == jQuery( e.target ).data( 'code' ) ||
				    jQuery( e.target ).closest( '.evo_designer__container[data-code=' + subcontainer.data( 'code' ) + ']' ).length > 0 )
				{	// Skip it because currently cursor is over displayed designer block of one of sub-containers:
					continue;
				}

				if( e.pageX >= evo_subcontainer_containers[i][2] && e.pageY >= evo_subcontainer_containers[i][1] && // top-left point
						e.pageX <= evo_subcontainer_containers[i][4] && e.pageY <= evo_subcontainer_containers[i][3] ) // bottom-right point
				{	// Initialize and display sub-container designer block because cursor is over it:
					evo_widget_initialize_designer_container_block( subcontainer );
					// Set z-index to make it over designer block of current sub-container:
					jQuery( evo_widget_container_block_selector( subcontainer.data( 'code' ) ) ).css( 'z-index', subcontainer_zindex + 1 );
					active_subcontainer_designer_block.show();
				}
				else
				{
					jQuery( evo_widget_container_block_selector( subcontainer.data( 'code' ) ) ).hide();
				}
			}
		}
	}
} );

jQuery( document ).on( 'click', '.evo_designer__action_order_up, .evo_designer__action_order_down', function()
{	// Change an order of widget:
	var designer_block = jQuery( this ).closest( '.evo_designer__widget' );
	var widget = jQuery( evo_widget_selector( designer_block ) );
	var order_type = jQuery( this ).hasClass( 'evo_designer__action_order_up' ) ? 'up' : 'down';

	// Re-select designer block because it may be several blocks for some widgets, e.g. for items/posts list widget in Menu container:
	designer_block = jQuery( evo_widget_designer_block_selector( widget ) );

	// Mark current widget with process class:
	designer_block.removeClass( 'evo_designer__status_failed' ).addClass( 'evo_designer__status_process' );

	// Change an order of the widget with near widget:
	evo_widget_reorder( widget, order_type );

	var widgets_ids = [];
	var container_wrapper = evo_widget_container_wrapper( widget );
	container_wrapper.children( '.evo_widget[data-id]' ).each( function()
	{
		if( widgets_ids.indexOf( jQuery( this ).data( 'id' ) ) == -1 )
		{	// Don't send ID of the same widget:
			widgets_ids.push( jQuery( this ).data( 'id' ) );
		}
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
 * Get all duplicates of the same widget in the container, e.g. for items/posts list widget in Menu container
 *
 * @param object Widget
 * @return object Widgets
 */
function evo_widget_get_duplicates( widget )
{
	return widget.closest( '.evo_container' ).find( '.evo_widget[data-id=' + widget.data( 'id' ) + ']' );
}


/**
 * Get jQuery selector for widget by designer block
 *
 * @param object Designer block
 * @returns string
 */
function evo_widget_selector( designer_block )
{
	var additional_select = '';
	if( designer_block.data( 'unique-num' ) > 0 )
	{	// Use additional select, e-g when same subcontainer widget is used in several
		// places on the same page, and it is not enough to select it only by widget ID:
		additional_select = '[data-unique-num=' + designer_block.data( 'unique-num' ) + ']';
	}

	return '.evo_widget[data-id=' + designer_block.data( 'id' ) + ']' + additional_select;
}


/**
 * Get jQuery selector for designer block by widget
 *
 * @param object Widget
 * @returns string
 */
function evo_widget_designer_block_selector( widget )
{
	var additional_select = '';
	if( widget.data( 'unique-num' ) > 0 )
	{	// Use additional select, e-g when same subcontainer widget is used in several
		// places on the same page, and it is not enough to select it only by widget ID:
		additional_select = '[data-unique-num=' + widget.data( 'unique-num' ) + ']';
	}

	return '.evo_designer__widget[data-id=' + widget.data( 'id' ) + ']' + additional_select;
}


/**
 * Get jQuery selector for widget container by containerdesigner block
 *
 * @param object Container designer block
 * @returns string
 */
function evo_widget_container_selector( container_block )
{
	return '.evo_container[data-code="' + container_block.data( 'code' ) + '"]';
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
	var curr_widget = widget.eq( 0 );

	if( direction == 'up' )
	{	// Move HTML of the widget before previous widget:
		var near_widget = evo_widget_get_duplicates( curr_widget.prev() );
		near_widget.eq( 0 ).before( widget );
	}
	else
	{	// Move HTML of the widget after next widget:
		var near_widget = evo_widget_get_duplicates( widget.eq( widget.length - 1 ).next() );
		near_widget.eq( near_widget.length - 1 ).after( widget );
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
	var prev_widget_ID = 0;
	container_widgets.each( function()
	{
		var designer_block = jQuery( evo_widget_designer_block_selector( jQuery( this ) ) ).eq( 0 );
		if( designer_block.length )
		{	// If designer block is initialized:
			if( prev_widget_ID != designer_block.data( 'id' ) )
			{	// If it is block for next different widget:
				designer_block.find( '.evo_designer__action_order_up, .evo_designer__action_order_down' ).show();
			}
			if( widget_num == 1 )
			{	// Hide action icon to move widget up for the first widget in container:
				designer_block.find( '.evo_designer__action_order_up' ).hide();
			}
			if( widget_num == container_widgets.length )
			{	// Hide action icon to move widget down for the last widget in container:
				designer_block.find( '.evo_designer__action_order_down' ).hide();
			}
			prev_widget_ID = designer_block.data( 'id' );
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
	var w = 0;
	var designer_blocks = jQuery( evo_widget_designer_block_selector( widget ) );
	evo_widget_get_duplicates( widget ).each( function()
	{	// We should display several designer blocks for widget blocks with same ID, e.g. for items/posts list widget in Menu container:
		var curr_widget = jQuery( this );
		var widget_class = '';
		var widget_left = curr_widget.offset().left - 3;
		var widget_width = curr_widget.outerWidth() + 5;
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

		var designer_block = designer_blocks.eq( w );
		designer_block.css( {
				'top': curr_widget.offset().top - 3,
				'left': widget_left,
				'width': widget_width,
				'height': curr_widget.outerHeight() + 5,
			} )
			.addClass( widget_class );
		if( typeof( show ) == 'undefined' || show )
		{	// Show widget desginer block:
			designer_block.show();
		}

		w++;
	} );

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
	    ! designer_block.hasClass( 'evo_designer__status_failed' ) &&
	    ! designer_block.hasClass( 'evo_designer__subcontainer_active' ) )
	{	// Hide only when widget is not in process:
		jQuery( evo_widget_designer_block_selector( jQuery( evo_widget_selector( designer_block ) ) ) ).hide();
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