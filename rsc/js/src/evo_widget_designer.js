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
} );

jQuery( document ).on( 'click', '.evo_widget__designer_block', function()
{	// Link to edit widget:
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

jQuery( document ).on( 'mouseout', '.evo_widget__designer_block', function()
{	// Hide widget designer block:
	jQuery( this ).hide();
} );