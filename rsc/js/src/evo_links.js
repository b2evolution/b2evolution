/**
 * This file implements links specific Javascript functions.
 * (Used only in back-office)
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois PLANQUE - {@link http://fplanque.com/}
 *
 * @package admin
 */


// Initialize attachments block:
jQuery( document ).ready( function()
{
	var height = jQuery( '#attachments_fieldset_table' ).height();
	height = ( height > 320 ) ? 320 : ( height < 97 ? 97 : height );
	jQuery( '#attachments_fieldset_wrapper' ).height( height );

	jQuery( '#attachments_fieldset_wrapper' ).resizable(
	{	// Make the attachments fieldset wrapper resizable:
		minHeight: 80,
		handles: 's',
		resize: function( e, ui )
		{	// Limit max height by table of attachments:
			jQuery( '#attachments_fieldset_wrapper' ).resizable( 'option', 'maxHeight', jQuery( '#attachments_fieldset_table' ).height() );
		}
	} );
	jQuery( document ).on( 'click', '#attachments_fieldset_wrapper .ui-resizable-handle', function()
	{	// Increase attachments fieldset height on click to resizable handler:
		var max_height = jQuery( '#attachments_fieldset_table' ).height();
		var height = jQuery( '#attachments_fieldset_wrapper' ).height() + 80;
		jQuery( '#attachments_fieldset_wrapper' ).css( 'height', height > max_height ? max_height : height );
	} );
} );


/**
 * Change link position
 *
 * @param object Select element
 * @param string URL
 * @param string Crumb
 */
function evo_link_change_position( selectInput, url, crumb )
{
	var oThis = selectInput;
	var new_position = selectInput.value;
	jQuery.get( url + 'async.php?action=set_object_link_position&link_ID=' + selectInput.id.substr(17) + '&link_position=' + new_position + '&crumb_link=' + crumb, {
	}, function(r, status) {
		r = ajax_debug_clear( r );
		if( r == "OK" ) {
			evoFadeSuccess( jQuery(oThis).closest('tr') );
			jQuery(oThis).closest('td').removeClass('error');
			if( new_position == 'cover' )
			{ // Position "Cover" can be used only by one link
				jQuery( 'select[name=link_position][id!=' + selectInput.id + '] option[value=cover]:selected' ).each( function()
				{ // Replace previous position with "Inline"
					jQuery( this ).parent().val( 'aftermore' );
					evoFadeSuccess( jQuery( this ).closest('tr') );
				} );
			}
		} else {
			jQuery(oThis).val(r);
			evoFadeFailure( jQuery(oThis).closest('tr') );
			jQuery(oThis.form).closest('td').addClass('error');
		}
	} );
	return false;
}


/**
 * Insert inline tag into the post ( example: [image:123:caption text] | [file:123:caption text] )
 *
 * @param string Type: 'image', 'file', 'video'
 * @param integer File ID
 * @param string Caption text
 */
function evo_link_insert_inline( type, link_ID, caption )
{
	var b2evoCanvas = window.document.getElementById('itemform_post_content');
	if( b2evoCanvas != null )
	{ // Canvas exists
		var insert_tag = '[' + type + ':' + link_ID;

		if( caption.length )
		{
			console.log( caption.length );
			insert_tag += ':' + caption;
		}

		insert_tag += ']';

		// Insert an image tag
		textarea_wrap_selection( b2evoCanvas, insert_tag, '', 0, window.document );

		var $position_selector = jQuery( '#display_position_' + link_ID );
		if( $position_selector.length != 0 )
		{ // Change the position to 'Inline'
			if( $position_selector.val() != 'inline' )
			{
				$position_selector.val( 'inline' ).change();
			}
		}
	}
}


/**
 * Unlink/Delete an attachment from Item or Comment
 *
 * @param object Event object
 * @param string Type: 'item', 'comment'
 * @param integer Link ID
 * @param string Action: 'unlink', 'delete'
 */
function evo_link_delete( event_object, type, link_ID, action )
{
	// Call REST API request to unlink/delete the attachment:
	evo_rest_api_request( 'links/' + link_ID,
	{
		'action': action
	},
	function( data )
	{
		if( type == 'item' )
		{	// Replace the inline image placeholders when file is unlinked from Item:
			var b2evoCanvas = window.document.getElementById( 'itemform_post_content' );
			if( b2evoCanvas != null )
			{ // Canvas exists
				var regexp = new RegExp( '\\\[(image|file|inline):' + link_ID + ':?[^\\\]]*\\\]', 'ig' );
				textarea_str_replace( b2evoCanvas, regexp, '', window.document );
			}
		}

		// Remove attachment row from table:
		jQuery( event_object ).closest( 'tr' ).remove();

		// Update the attachment block height after deleting row:
		var table_height = jQuery( '#attachments_fieldset_table' ).height();
		var wrapper_height = jQuery( '#attachments_fieldset_wrapper' ).height();
		if( wrapper_height > table_height )
		{
			jQuery( '#attachments_fieldset_wrapper' ).height( jQuery( '#attachments_fieldset_table' ).height() );
		}
	},
	'DELETE' );

	return false;
}


/**
 * Change an order of the Item/Comment attachment
 *
 * @param object Event object
 * @param integer Link ID
 * @param string Action: 'move_up', 'move_down'
 */
function evo_link_change_order( event_object, link_ID, action )
{
	// Call REST API request to change order of the attachment:
	evo_rest_api_request( 'links/' + link_ID + '/' + action,
	function( data )
	{
		// Change an order in the attachments table
		var row = jQuery( event_object ).closest( 'tr' );
		if( action == 'move_up' )
		{	// Move up:
			row.prev().before( row );
		}
		else
		{	// Move down:
			row.next().after( row );
		}
	},
	'POST' );

	return false;
}