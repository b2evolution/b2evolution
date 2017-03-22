/**
 * This file is used for widgets designer mode
 */

var evo_widget_designer_num = 1;

jQuery( document ).on( 'mouseover', '.evo_widget', function()
{	// Initialize and Show widget designer block:

	// To be sure all previous designer blocks are closed before open new one:
	jQuery( '.evo_widget__designer_block' ).hide();

	var current_widget_position = {
		'top': jQuery( this ).offset().top - 3,
		'left': jQuery( this ).offset().left - 3,
		'width': jQuery( this ).width() + 6,
		'height': jQuery( this ).height() + 6,
	};
	var current_designer_block_id = jQuery( this ).data( 'block-id' );
	var designer_block_id = 'evo_widget__designer_block_' + ( typeof( current_designer_block_id ) != 'undefined' ? current_designer_block_id : evo_widget_designer_num );
	if( jQuery( '#' + designer_block_id ).length )
	{	// Just display a designer block if it already has been initialized on previous time:
		jQuery( '#' + designer_block_id ).css( current_widget_position ).show();
		return;
	}

	if( jQuery( '.evo_widget__designer_blocks' ).length == 0 )
	{	// Create wrapper for all widget designer blocks:
		jQuery( 'body' ).append( '<div class="evo_widget__designer_blocks"></div>' );
	}

	// Initialize a designer block only first time:
	jQuery( this ).data( 'block-id', evo_widget_designer_num );
	var widget_data = jQuery( this ).next( '.evo_widget__data' );
	var widget_ID_attr = ( widget_data.length && widget_data.data( 'id' ) ) ? ' data-id="' + widget_data.data( 'id' ) + '"': '';
	jQuery( '.evo_widget__designer_blocks' ).append( '<div id="' + designer_block_id + '" class="evo_widget__designer_block"' + widget_ID_attr + '></div>' );
	jQuery( '#' + designer_block_id ).css( current_widget_position );

	// Increase a number for next block:
	evo_widget_designer_num++;
} );

jQuery( document ).on( 'click', '.evo_widget__designer_block', function()
{	// Link to edit widget:
	var widget_ID = jQuery( this ).data( 'id' );
	if( jQuery( '.evo_widget__data[data-id=' + widget_ID + ']' ).length )
	{
		var widget_edit_url = jQuery( '.evo_widget__data[data-id=' + widget_ID + ']' ).data( 'edit-url' );
		if( typeof( widget_edit_url ) != 'undefined' )
		{
			location.href = widget_edit_url;
		}
	}
} );

jQuery( document ).on( 'mouseout', '.evo_widget__designer_block', function()
{	// Hide widget designer block:
	jQuery( this ).hide();
} );