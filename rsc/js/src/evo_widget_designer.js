/**
 * This file is used for widgets designer mode
 */

jQuery( document ).on( 'mouseover', '.evo_widget', function()
{	// Initialize and Show widget designer block:

	// To be sure all previous designer blocks are hidden before show new one:
	jQuery( '.evo_widget__designer_block' ).hide();

	var widget = jQuery( this );
	var current_widget_position = {
		'top': widget.offset().top - 3,
		'left': widget.offset().left - 3,
		'width': widget.width() + 6,
		'height': widget.height() + 6,
	};
	var designer_block_selector = '.evo_widget__designer_block[data-id=' + widget.data( 'id' ) + ']';
	if( jQuery( designer_block_selector ).length )
	{	// Just display a designer block if it already has been initialized on previous time:
		jQuery( designer_block_selector ).css( current_widget_position ).show();
		return;
	}

	if( jQuery( '.evo_widget__designer_blocks' ).length == 0 )
	{	// Create wrapper for all widget designer blocks:
		jQuery( 'body' ).append( '<div class="evo_widget__designer_blocks"></div>' );
	}

	// Initialize a designer block only first time:
	jQuery( '.evo_widget__designer_blocks' ).append( '<div class="evo_widget__designer_block" data-id="' + widget.data( 'id' ) + '"></div>' );
	jQuery( designer_block_selector )
		.html( '<div><div class="evo_widget__designer_type">' + widget.data( 'type' ) + '</div></div>' )
		.css( current_widget_position );
	if( widget.data( 'can-edit' ) == '1' &&
	    ( widget.next( '.evo_widget' ).length || widget.prev( '.evo_widget' ).length ) )
	{	// Display a panel with actions if current user has a permission to edit widget:
		jQuery( '>div', designer_block_selector ).append( '<div class="evo_widget__designer_actions">' +
				b2evo_widget_icon_up +
				b2evo_widget_icon_down +
			'</div>' );
		if( widget.next( '.evo_widget' ).length == 0 )
		{	// Hide action icon to move widget down if it is the last widget in container:
			jQuery( designer_block_selector ).find( '.evo_widget__designer_move_down' ).hide();
		}
		if( widget.prev( '.evo_widget' ).length == 0 )
		{	// Hide action icon to move widget up if it is the first widget in container:
			jQuery( designer_block_selector ).find( '.evo_widget__designer_move_up' ).hide();
		}
	}
} );

jQuery( document ).on( 'click', '.evo_widget__designer_block', function( e )
{	// Link to edit widget:
	if( jQuery( e.target ).hasClass( 'evo_widget__designer_move_up' ) ||
	    jQuery( e.target ).hasClass( 'evo_widget__designer_move_down' ) )
	{	// Ignore if click is on order action icons:
		return;
	}
	if( typeof( b2evo_widget_edit_url ) != 'undefined' )
	{	// If global widget edit form url is defined:
		var widget_ID = jQuery( this ).data( 'id' );
		var widget = jQuery( '.evo_widget[data-id=' + widget_ID + ']' );
		if( widget.length && widget.data( 'can-edit' ) == '1' )
		{	// Redirect to widget edit form only if it is allowed for current user:
			location.href = b2evo_widget_edit_url.replace( '$wi_ID$', widget_ID );
		}
	}
} );

jQuery( document ).on( 'mouseout', '.evo_widget__designer_block', function( e )
{	// Hide widget designer block:
	if( ! jQuery( e.toElement ).closest( '.evo_widget__designer_block' ) )
	{	// Hide it only when cursor is realy out of designer block:
		jQuery( this ).hide();
	}
} );

jQuery( document ).on( 'click', '.evo_widget__designer_move_up, .evo_widget__designer_move_down', function()
{	// Change an order of widget:
	var designer_block = jQuery( this ).closest( '.evo_widget__designer_block' );
	var widget_ID = designer_block.data( 'id' );
	var widget = jQuery( '.evo_widget[data-id=' + widget_ID + ']' );
	var order_type = jQuery( this ).hasClass( 'evo_widget__designer_move_up' ) ? 'up' : 'down';

	designer_block.addClass( 'wdb_process' );

	jQuery.ajax(
	{
		type: 'POST',
		url: htsrv_url + 'async.php',
		data: {
			'blog': b2evo_widget_blog,
			'crumb_widget': b2evo_widget_crumb,
			'action': 'widget_order',
			'order_type': order_type,
			'wi_ID': widget_ID,
		},
		success: function()
		{	// If order has been updated successfully:
			if( order_type == 'up' )
			{	// Move HTML of the widget before previous widget:
				widget.prev().before( widget );
			}
			else
			{	// Move HTML of the widget after next widget:
				widget.next().after( widget );
			}
			// Update visibility of up/down action icons of first/last widgets:
			var container_widgets = widget.parent().find( '.evo_widget' );
			var widget_num = 1;
			container_widgets.each( function()
			{
				var designer_block = jQuery( '.evo_widget__designer_block[data-id=' + jQuery( this ).data( 'id' ) + ']' );
				if( designer_block.length )
				{	// If designer block is initialized:
					designer_block.find( '.evo_widget__designer_move_up, .evo_widget__designer_move_down' ).show();
					if( widget_num == 1 )
					{	// Hide action icon to move widget up for the first widget in container:
						designer_block.find( '.evo_widget__designer_move_up' ).hide();
					}
					else if( widget_num == container_widgets.length )
					{	// Hide action icon to move widget up for the last widget in container:
						designer_block.find( '.evo_widget__designer_move_down' ).hide();
					}
				}
				widget_num++;
			} );
			designer_block.removeClass( 'wdb_process wdb_failed' ).addClass( 'wdb_success' ).css( {
					'top': widget.offset().top - 3,
					'left': widget.offset().left - 3,
					'width': widget.width() + 6,
					'height': widget.height() + 6,
				} );
			setTimeout( function()
			{
				jQuery( '.evo_widget__designer_block' ).hide();
				designer_block.removeClass( 'wdb_success' );
			}, 500 );
		},
		error: function( jqXHR, textStatus, errorThrown )
		{	// Display error text on error request:
			designer_block.removeClass( 'wdb_process' ).addClass( 'wdb_failed' );
			alert( 'Error: could not change order of the widget. Please contact the site admin and check the browser and server error logs. (' + textStatus + ': ' + errorThrown + ')' );
		}
	} );
} );