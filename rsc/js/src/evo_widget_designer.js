/**
 * This file is used for widgets designer mode
 */

jQuery( document ).on( 'ready', function()
{	// Move container data from temp hidden elements to real container wrapper:
	jQuery( '.evo_designer__container_data' ).each( function()
	{
		var container = jQuery( this ).closest( '.evo_container' );
		if( container.length )
		{	// If container has a correct class
			// Copy all data from temp element to real container:
			container.attr( 'data-name', jQuery( this ).data( 'name' ) );
			if( jQuery( this ).data( 'can-edit' ) )
			{
				container.data( 'can-edit', '1' );
			}
			// Remove temp element:
			jQuery( this ).remove();
		}
	} );
} );

jQuery( document ).on( 'mouseover', '.evo_container', function()
{	// Initialize and Show container designer block:
	var container = jQuery( this );

	// To be sure all previous designer blocks are hidden before show new one:
	jQuery( '.evo_designer__container' ).hide();

	var container_block = jQuery( '.evo_designer__container[data-name="' + container.data( 'name' ) + '"]' );
	if( container_block.length )
	{	// Just display a designer block if it already has been initialized previous time:
		evo_widget_update_container_position( container );
		return;
	}

	// Initialize a container designer block only first time:
	var container_actions = '';
	if( container.data( 'can-edit' ) == '1' )
	{	// Display a panel with actions if current user has a permission to edit widgets:
		container_actions = '<div class="evo_designer__actions">' +
				b2evo_widget_icon_add +
			'</div>';
	}
	jQuery( 'body' ).append( '<div class="evo_designer evo_designer__container" data-name="' + container.data( 'name' ) + '">' +
			'<div><div class="evo_designer__title">Container: ' + container.data( 'name' ) + '</div>' + container_actions + '</div>' +
		'</div>' );
	evo_widget_update_container_position( container );
} );

jQuery( document ).on( 'mouseover', '.evo_widget[data-id]', function()
{	// Initialize and Show widget designer block:

	// To be sure all previous designer blocks are hidden before show new one except of processing widgets:
	jQuery( '.evo_designer__container, .evo_designer__widget:not(.evo_designer__status_process):not(.evo_designer__status_failed)' ).hide();

	var widget = jQuery( this );
	var designer_block_selector = evo_widget_designer_block_selector( widget );
	if( jQuery( designer_block_selector ).length )
	{	// Just display a designer block if it already has been initialized previous time:
		evo_widget_update_designer_position( widget );
		return;
	}

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
} );

jQuery( document ).on( 'mouseover', '.evo_designer__widget', function()
{	// Show container designer block:
	var widget = jQuery( evo_widget_selector( jQuery( this ) ) );
	var container_block = jQuery( evo_widget_container_block_selector( widget.data( 'container' ) ) );
	if( ! container_block.is( ':visible' ) )
	{
		container_block.show();
	}
} );


/**
 * Open modal window with iframe
 *
 * @param string Frame url
 * @param object Container
 */
function evo_widget_open_modal_iframe( iframe_url, iframe_title, container )
{
	openModalWindow( '<span class="loader_img loader_widget_designer absolute_center" title="' + evo_js_lang_loading + '"></span>' +
		'<iframe id="evo_designer__iframe" src="' + iframe_url + '&display_mode=iframe" width="100%" height="90%" frameborder="0"></iframe>',
		'90%', '90%', true, iframe_title, false, true );
	jQuery( '#evo_designer__iframe' ).closest( '#modal_window' ).addClass( 'evo_designer__modal_window' );
	jQuery( '#evo_designer__iframe' ).on( 'load', function()
	{	// Remove loader after iframe is loaded:
		jQuery( '.loader_widget_designer' ).remove();
		// Append div for display messages after save widget settings:
		container.prepend( '<div id="server_messages" class="evo_designer__messages"></div>' );
	} );
	jQuery( '#modal_window' ).on( 'hidden.bs.modal', function ()
	{	// Remove temp div of messages on hidding of modal window:
		jQuery( '.evo_designer__messages' ).remove();
		if( jQuery( '#evo_designer__iframe').contents().find( '.alert.alert-success' ).length && confirm( evo_js_lang_confirm_reload_new_widget_changes ) )
		{	// If widget has been updated in frame we should reload a page to view new widget changes:
			location.reload();
		}
	} );
}

jQuery( document ).on( 'click', '.evo_designer__action_add', function( e )
{	// Link to add widget:
	if( typeof( b2evo_widget_add_url ) != 'undefined' )
	{	// If global widget add form url is defined:
		var container_block = jQuery( this ).closest( '.evo_designer__container' );
		var container = jQuery( '.evo_container[data-name="' + container_block.data( 'name' ) + '"]' );
		if( container.length && container.data( 'can-edit' ) == '1' )
		{	// Open modal window with widget adding list only if it is allowed for current user:
			evo_widget_open_modal_iframe( b2evo_widget_add_url.replace( '$container$', container.data( 'name' ) ),
				evo_js_lang_title_available_widgets.replace( '$container_name$', container.data( 'name' ) ),
				container );
		}
	}
} );

jQuery( document ).on( 'click', '.evo_designer__widget', function( e )
{	// Link to edit widget:
	if( jQuery( e.target ).is( '.evo_designer__action' )  )
	{	// Ignore if click is on action icons:
		return;
	}
	if( typeof( b2evo_widget_edit_url ) != 'undefined' )
	{	// If global widget edit form url is defined:
		var widget_ID = jQuery( this ).data( 'id' );
		var widget = jQuery( evo_widget_selector( jQuery( this ) ) );
		if( widget.length && widget.data( 'can-edit' ) == '1' )
		{	// Open modal window with widget edit form only if it is allowed for current user:
			evo_widget_open_modal_iframe( b2evo_widget_edit_url.replace( '$wi_ID$', widget_ID ),
				evo_js_lang_title_edit_widget.replace( '$widget_name$', widget.data( 'type' ) ).replace( '$container_name$', widget.data( 'container' ) ),
				widget.closest( '.evo_container' ) );
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

jQuery( document ).on( 'click', '.evo_designer__action_order_up, .evo_designer__action_order_down', function()
{	// Change an order of widget:
	var designer_block = jQuery( this ).closest( '.evo_designer__widget' );
	var widget = jQuery( evo_widget_selector( designer_block ) );
	var order_type = jQuery( this ).hasClass( 'evo_widget__designer_move_up' ) ? 'up' : 'down';

	// Mark current widget with process class:
	designer_block.removeClass( 'evo_designer__status_failed' ).addClass( 'evo_designer__status_process' );

	// Change an order of the widget with near widget:
	evo_widget_reorder( widget, order_type );

	var widgets_ids = [];
	widget.parent().find( '.evo_widget[data-id]' ).each( function()
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
			evo_widget_display_error( 'There was an error communicating with the server. Please reload the page to be in sync with the server. (' + textStatus + ': ' + errorThrown + ')', widget, order_type );
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
			evo_widget_display_error( 'There was an error communicating with the server. Please reload the page to be in sync with the server. (' + textStatus + ': ' + errorThrown + ')', widget, 'disable' );
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
 * @param string Container name
 * @returns string
 */
function evo_widget_container_block_selector( container_name )
{
	return '.evo_designer__container[data-name="' + container_name + '"]';
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
	evo_widget_update_order_actions( widget.parent() );

	evo_widget_update_designer_position( widget );
	evo_widget_update_designer_position( near_widget );
}


/**
 * Update visibility of up/down action icons of first/last widgets:
 *
 * @param object Widget
 */
function evo_widget_update_order_actions( container )
{
	var container_widgets = container.find( '.evo_widget' );
	var widget_num = 1;
	container_widgets.each( function()
	{
		var designer_block = jQuery( '.evo_designer__widget[data-id=' + jQuery( this ).data( 'id' ) + ']' );
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
 */
function evo_widget_update_designer_position( widget )
{
	jQuery( evo_widget_designer_block_selector( widget ) )
		.css( {
			'top': widget.offset().top - 3,
			'left': widget.offset().left - 3,
			'width': widget.width() + 6,
			'height': widget.height() + 6,
		} )
		.show();

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
	jQuery( evo_widget_container_block_selector( container.data( 'name' ) ) )
		.css( {
			'top': container.offset().top - 3,
			'left': container.offset().left - 3,
			'width': container.width() + 6,
			'height': container.height() + 6,
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