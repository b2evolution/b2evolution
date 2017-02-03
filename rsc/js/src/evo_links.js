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
	if( jQuery( '#attachments_fieldset_table' ).length > 0 )
	{	// Only if the attachments block exists on the loading page:
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
	}
} );


/**
 * Fix height of attachments wrapper
 * Used after content changing by AJAX loading
 */
function evo_link_fix_wrapper_height()
{
	var table_height = jQuery( '#attachments_fieldset_table' ).height();
	var wrapper_height = jQuery( '#attachments_fieldset_wrapper' ).height();
	if( wrapper_height != table_height )
	{
		jQuery( '#attachments_fieldset_wrapper' ).height( jQuery( '#attachments_fieldset_table' ).height() );
	}
}


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
	jQuery.get( url + 'anon_async.php?action=set_object_link_position&link_ID=' + selectInput.id.substr(17) + '&link_position=' + new_position + '&crumb_link=' + crumb, {
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
function evo_link_insert_inline( type, link_ID, option )
{
	if( typeof( b2evoCanvas ) != 'undefined' )
	{ // Canvas exists
		var insert_tag = '[' + type + ':' + link_ID;

		if( option.length )
		{
			insert_tag += ':' + option;
		}

		insert_tag += ']';

		// Insert an image tag
		textarea_wrap_selection( b2evoCanvas, insert_tag, '', 0, window.document );

		var $position_selector = jQuery( '#display_position_' + link_ID );
		if( $position_selector.length != 0 )
		{ // Change the position to 'Inline'
			if( $position_selector.val() != 'inline' )
			{
				deferInlineReminder = true;
				$position_selector.val( 'inline' ).change();
				deferInlineReminder = false;
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
				var regexp = new RegExp( '\\\[(image|file|inline|video|audio|thumbnail):' + link_ID + ':?[^\\\]]*\\\]', 'ig' );
				textarea_str_replace( b2evoCanvas, regexp, '', window.document );
			}
		}

		// Remove attachment row from table:
		jQuery( event_object ).closest( 'tr' ).remove();

		// Update the attachment block height after deleting row:
		evo_link_fix_wrapper_height();
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
		evoFadeSuccess( row );
	},
	'POST' );

	return false;
}


/**
 * Attach a file to Item/Comment
 *
 * @param string Type: 'item', 'comment'
 * @param integer ID of Item or Comment
 * @param string Root (example: 'collection_1')
 * @param string Path to the file relative to root
 */
function evo_link_attach( type, object_ID, root, path )
{
	// Call REST API request to attach a file to Item/Comment:
	evo_rest_api_request( 'links',
	{
		'action':    'attach',
		'type':      type,
		'object_ID': object_ID,
		'root':      root,
		'path':      path
	},
	function( data )
	{
		var table_obj = jQuery( '#attachments_fieldset_table table', window.parent.document );
		var table_parent = table_obj.parent;
		var results_obj = jQuery( data.list_content );
		table_obj.replaceWith( jQuery( 'table', results_obj ) ).promise().done( function( e ) {
			// Delay for a few milleseconds after content is loaded to get the correct height
			setTimeout( function() {
				window.parent.evo_link_fix_wrapper_height();
			}, 10 );
		});
	} );

	return false;
}


/**
 * Set temporary content during ajax is loading
 *
 * @return object Overlay indicator of ajax loading
 */
function evo_link_ajax_loading_overlay()
{
	var table = jQuery( '#attachments_fieldset_table' );

	var ajax_loading = false;

	if( table.find( '.results_ajax_loading' ).length == 0 )
	{	// Allow to add new overlay only when previous request is finished:
		ajax_loading = jQuery( '<div class="results_ajax_loading"><div>&nbsp;</div></div>' );
		table.css( 'position', 'relative' );
		ajax_loading.css( {
				'width':  table.width(),
				'height': table.height(),
			} );
		table.append( ajax_loading );
	}

	return ajax_loading;
}


/**
 * Refresh/Sort a list of Item/Comment attachments
 *
 * @param string Type: 'item', 'comment'
 * @param integer ID of Item or Comment
 * @param string Action: 'refresh', 'sort'
 */
function evo_link_refresh_list( type, object_ID, action )
{
	var ajax_loading = evo_link_ajax_loading_overlay();

	if( ajax_loading )
	{	// If new request is allowed in current time:

		// Call REST API request to attach a file to Item/Comment:
		evo_rest_api_request( 'links',
		{
			'action':    typeof( action ) == 'undefined' ? 'refresh' : 'sort',
			'type':      type,
			'object_ID': object_ID,
		},
		function( data )
		{
			// Refresh a content of the links list:
			jQuery( '#attachments_fieldset_table' ).html( data.html );

			// Remove temporary content of ajax loading indicator:
			ajax_loading.remove();

			// Update the attachment block height after refreshing:
			evo_link_fix_wrapper_height();
		} );
	}

	return false;
}