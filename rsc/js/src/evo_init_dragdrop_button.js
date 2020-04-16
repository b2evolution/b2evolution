/**
 * This file initializes the Drag and Drop Upload button
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 * 
 * Depends on: jQuery, FineUploader
 */
jQuery( document ).ready( function()
{
	window.dndb = {};
	window.init_uploader = function init_uploader( config )
		{
			if( 'draggable' in document.createElement('span') )
			{
				var button_text = config['draggable_button_text'];
				var file_uploader_note_text = config['draggable_note_text'];
			}
			else
			{
				var button_text = config['nondraggable_button_text'];
				var file_uploader_note_text = config['nondraggable_note_text'];
			}

			window.dndb[config.fieldset_prefix + 'url'] = config.quickupload_url;
			window.dndb[config.fieldset_prefix + 'root_and_path'] = config.root_and_path;

			jQuery( '#fm_dirtree input[type=radio]' ).click( function()
				{
					window.dndb[config.fieldset_prefix + 'url'] = config.quickupload_url + '&root_and_path=' + this.value + '&' + config.crumb_file;
					window.dndb[config.fieldset_prefix + 'root_and_path'] = this.value;
					window.dndb[config.fieldset_prefix  + 'uploader'].setParams( {
							root_and_path: window.dndb[config.fieldset_prefix + 'root_and_path']
						} );
				} );

			if( config.link_owner )
			{	// Add params to link a file right after uploading:
				window.dndb[config.fieldset_prefix + 'url'] += '&link_owner=' + config.link_owner;
			}
			if( config.fm_mode && config.fm_mode == 'file_select' )
			{
				window.dndb[config.fieldset_prefix + 'url'] += '&fm_mode=' + config.fm_mode;
			}

			window.dndb[config.fieldset_prefix + 'uploader'] = new qq.FineUploader(
				{
					debug: false,
					request: {
							endpoint: window.dndb[config.fieldset_prefix + 'url'],
							params: {
								root_and_path: window.dndb[config.fieldset_prefix + 'root_and_path'],
							}
						},
					template: document.getElementById( config.fieldset_prefix + 'qq-template' ),
					element: document.getElementById( config.fieldset_prefix + 'file-uploader' ),
					listElement: document.querySelector( config.list_element ),
					dragAndDrop: {
							extraDropzones: eval( config.extra_dropzones ), // TODO: this is not ideal, we do want to avoid using eval(). maybe we can find other ways to do this
						},
					list_style: config.list_style,
					action: window.dndb[config.fieldset_prefix + 'url'],
					sizeLimit: config.size_limit,
					messages: {
							typeError: config.msg_type_error,
							sizeError: config.msg_size_error,
							minSizeError: config.msg_min_size_error,
							emptyError: config.msg_empty_error,
							onLeave: config.msg_on_leave,
						},
					text: {
							formatProgress: config.msg_format_progress,
							sizeSymbols: [
									config.size_symbol_kb,
									config.size_symbol_mb,
									config.size_symbol_gb,
									config.size_symbol_tb,
									config.size_symbol_pb,
									config.size_symbol_eb,
								],
						},
					validation: {
						sizeLimit: config.validation_size_limit,
						allowedExtensions: config.allowed_extensions,
					},
					callbacks: {
						onSubmit: function( id, fileName, dropTarget )
							{
								var defaultParams = { root_and_path: window.dndb[config.fieldset_prefix + 'root_and_path'] },
									finalParams = defaultParams;

								if( jQuery( dropTarget ).hasClass( 'link_attachment_dropzone' ) )
								{	// File dropped over textarea, set link position to "inline"
									var newParams = { link_position: 'inline' };
									qq.extend( finalParams, newParams );
								}
								this.setParams( finalParams );

								var noresults_row = jQuery( '#' + config.fieldset_prefix + config.table_id + ' tr.noresults' );
								if( noresults_row.length )
								{	// Add table headers and remove "No results" row
									if( config.table_headers != '' )
									{	// Append table headers if they are defined
										noresults_row.parent().parent().prepend( config.table_headers );
									}
									noresults_row.remove();
								}

								setTimeout( function()
									{
										evo_link_fix_wrapper_height( config.fieldset_prefix );
										if( config.resize_frame )
										{	// Resize attachments fieldset after upload new image:
											window.dndb.update_iframe_height( config.fieldset_prefix );
											jQuery( document ).on( 'load', '#' + config.fieldset_prefix + config.table_id + ' img', function()
												{
													window.dndb.update_iframe_height( config.fieldset_prefix );
												} );
										}
									}, 10 );
							},
						onProgress: function( id, fileName, uploadedBytes, totalBytes )
							{
								var progressbar = jQuery( '#' + config.fieldset_prefix + config.table_id + ' tr[qq-file-id=' + id + '] .progress-bar' );
								var percentCompleted = Math.round( uploadedBytes / totalBytes * 100 ) + '%';

								progressbar.get(0).style.width = percentCompleted; // This should fix jQuery's .css() issue with some browsers

								progressbar.text( percentCompleted );
								if( config.resize_frame )
								{
									window.dndb.update_iframe_height( config.fieldset_prefix );
								}
							},
						onDropzoneDragOver: function( dropzone )
							{
								jQuery('.qq-upload-button').addClass('qq-upload-button-dragover');
							},
						onDropzoneDragOut: function( dropzone )
							{
								jQuery('.qq-upload-button').removeClass('qq-upload-button-dragover');
							},
						onDropzoneDragDrop: function( dropzone )
							{
								jQuery('.qq-upload-button').removeClass('qq-upload-button-dragover');
							},
						onComplete: function( id, fileName, responseJSON, request, dropTarget )
							{
								if( responseJSON != undefined )
								{
									var text;
									if( responseJSON.data.text )
									{
										if( responseJSON.specialchars == 1 )
										{
											text = htmlspecialchars_decode( responseJSON.data.text );
										}
										else
										{
											text = responseJSON.data.text;
										}
									}
									text = base64_decode( text );

									if( config.list_style == 'list' )
									{	// List view
										if( responseJSON.data.status != undefined && responseJSON.data.status == 'rename' )
										{
											jQuery( '#' + config.fieldset_prefix + config.table_id + ' #saveBtn' ).show();
										}
									}
								}
								
								if( config.list_style == 'table' )
								{	// Table view
									var this_row = jQuery( '#' + config.fieldset_prefix + config.table_id + ' tr[qq-file-id=' + id + ']' );

									if( responseJSON == undefined || responseJSON.data == undefined || responseJSON.data.status == 'error' || responseJSON.data.status == 'fatal' )
									{	// Failed
										this_row.find( '.qq-upload-status' ).html( '<span class="red">' + config.msg_upload_error + '</span>' );
										if( responseJSON.error )
										{
											text = responseJSON.error;
										}
										else if( typeof( text ) == 'undefined' || text == '' )
										{	// Message for unknown error
											text = config.msg_dropped_connection;
										}
										this_row.find( '.qq-upload-file-selector' ).append( ': <span class="text-danger result_error">' + text + '</span>' );
										this_row.find( '.qq-upload-image-selector, td.size' ).prepend( config.warning_icon );
									}
									else
									{	// Success/Conflict
										var table_view = typeof( responseJSON.data.link_ID ) != 'undefined' ? 'link' : 'file';

										var filename_before = config.filename_before;
										if( filename_before != '' )
										{
											filename_before = filename_before.replace( '$file_path$', responseJSON.data.path );
										}

										var select_file_template = '';
										if( responseJSON.data.select_link_button )
										{	// Add select file button:
											select_file_template = responseJSON.data.select_link_button;
										}

										var warning = '';
										if( responseJSON.data.warning != '' )
										{
											warning = '<div class="orange">' + responseJSON.data.warning + '</div>';
										}
										// File name or url to view file
										var file_name = ( typeof( responseJSON.data.link_url ) != 'undefined' ) ? responseJSON.data.link_url : responseJSON.data.formatted_name;

										this_row.find( '.qq-upload-checkbox' ).html( responseJSON.data.checkbox );

										if( responseJSON.data.status == 'success' )
										{	// Success upload
											if( config.display_status_success )
											{	// Display this message only if it is enabled
												this_row.find( '.qq-upload-status-text-selector' ).html( '<span class="green">' + config.msg_upload_ok + '</span>' );
											}
											else
											{
												this_row.find( '.qq-upload-status-text-selector' ).html( '' );
											}
											this_row.find( '.qq-upload-image' ).html( text );
											this_row.find( '.qq-upload-file-selector' ).html( filename_before
													+ select_file_template
													+ '<input type="hidden" value="' + responseJSON.data.newpath + '" />'
													+ '<span class="fname">' + file_name + '</span>' + warning );
											this_row.find( '.qq-upload-size-selector' ).html( responseJSON.data.filesize );

											if( responseJSON.data.filetype )
											{
												this_row.find( '.qq-upload-file-type' ).html( responseJSON.data.filetype );
											}

											if( responseJSON.data.creator )
											{
												this_row.find( '.qq-upload-file-creator' ).html( responseJSON.data.creator );
											}

											if( responseJSON.data.downloads != null )
											{
												this_row.find( '.qq-upload-downloads' ).html( responseJSON.data.downloads );
											}

											if( responseJSON.data.owner )
											{
												this_row.find( '.fsowner' ).html( responseJSON.data.owner );
											}

											if( responseJSON.data.group )
											{
												this_row.find( '.fsgroup' ).html( responseJSON.data.group );
											}

											if( responseJSON.data.file_date )
											{
												this_row.find( '.fsdate' ).html( responseJSON.data.file_date );
											}

											if( responseJSON.data.file_actions )
											{
												this_row.find( '.actions' ).html( responseJSON.data.file_actions );
											}

											if( jQuery( '#evo_multi_file_selector' ).length )
											{	// Show files selector for additional actions:
												jQuery( '#evo_multi_file_selector' ).show();
											}
										}
										else if( responseJSON.data.status == 'rename' )
										{	// Conflict on upload
											var status_conflict_message = '<span class="orange">' + config.msg_upload_confict + '</span>';
											if( config.status_conflict_place == 'default' )
											{	// Default place for a conflict message
												this_row.find( '.qq-upload-status-text-selector' ).html( status_conflict_message );
											}
											else
											{
												this_row.find( '.qq-upload-status-text-selector' ).html( '' );
											}
											this_row.find( '.qq-upload-image' ).html( responseJSON.data.file );
											this_row.find( '.qq-upload-image-selector' ).append( htmlspecialchars_decode( responseJSON.data.file ) );
											this_row.find( '.qq-upload-file-selector' ).html( filename_before
													+ select_file_template
													+ '<input type="hidden" value="' + responseJSON.data.newpath + '" />'
													+ '<span class="fname">' + file_name + '</span>'
													+ ( config.status_conflict_place == 'before_button'  ? ' - ' + status_conflict_message : '' )
													+ ' - <a href="#" '
													+ 'class="' + config.button_class + ' roundbutton_text_noicon qq-conflict-replace" '
													+ 'old="' + responseJSON.data.old_rootrelpath + '" '
													+ 'new="' + responseJSON.data.new_rootrelpath + '">'
													+ '<div>' + config.msg_replace_file + '</div>'
													+ '<div style="display:none">' + config.msg_revert + '</div>'
													+ '</a>'
													+ warning );
											var old_file_obj = jQuery( '#' + config.fieldset_prefix + config.table_id + ' input[type=hidden][value="' + responseJSON.data.oldpath + '"]' );
											if( old_file_obj.length > 0 )
											{
												old_file_obj.parent().append( ' <span class="orange">' + config.msg_old_file + '</span>' );
											}
										}
										if( table_view == 'link' )
										{	// Update the cells for link view, because these data exist in response
											this_row.find( '.qq-upload-link-id' ).html( '<span data-order="' + responseJSON.data.link_order
													+ '">' + responseJSON.data.link_ID + '</span>' );
											this_row.find( '.qq-upload-image' ).html( responseJSON.data.link_preview );
											this_row.find( '.qq-upload-link-actions' ).prepend( responseJSON.data.link_actions );
											if( typeof( responseJSON.data.link_position ) != 'undefined' )
											{
												this_row.find( '.qq-upload-link-position' ).html( responseJSON.data.link_position );
											}
										}
										init_colorbox( this_row.find( '.qq-upload-image a[rel^="lightbox"]' ) );
										evo_link_sort_list( config.fieldset_prefix );
									}
								}
								else
								{	// Simple list
									jQuery( window.dndb[config.fieldset_prefix + 'uploader'].getItemByFileId( id ) ).append( text );
									if( responseJSON.data == undefined && responseJSON != '' )
									{	// Display the fatal errors
										jQuery( window.dndb[config.fieldset_prefix + 'uploader'].getItemByFileId( id ) ).append( responseJSON );
									}
								}

								if( config.resize_frame )
								{	// Resize attachments fieldset after upload new image:
									window.dndb.update_iframe_height( config.fieldset_prefix );
									jQuery( document ).on( 'load', '#' + config.fieldset_prefix + config.table_id + ' img', function()
										{
											window.dndb.update_iframe_height( config.fieldset_preifx );
										} );
								}

								// Insert short tag if file was dropped in the textarea:
								if( jQuery( dropTarget ).hasClass( 'link_attachment_dropzone' ) )
								{	// Dropped file in edit content textarea:
									switch( responseJSON.data.filetype )
									{
										case 'image':
										case 'video':
										case 'audio':
											// Insert appropriate short tag:
											textarea_wrap_selection( dropTarget, '[' + responseJSON.data.filetype + ':' + responseJSON.data.link_ID + ']', '', 0 );
											break;
									}
								}
							},
						onCancel: function( id, fileName )
							{
								if( config.list_style == 'table' )
								{
									setTimeout( function()
									{	// allow some time to remove cancelled row first before determining the number of rows
										var container = jQuery( '#' + config.fieldset_prefix + config.table_id + ' .filelist_tbody' );
										var rows = container.find( 'tr' );
										if( !rows.length )
										{
											var noresult = config.no_results;
											container.append( noresult );
										}
									}, 10 );
								}
							}
					}
				} );

			// Update upload button text
			jQuery( 'div.qq-upload-button-selector > div, div.qq-upload-drop-area > div' ).html( button_text );

			if( config.resize_frame )
			{	// Resize attachments fieldset after upload new image:
				window.dndb.update_iframe_height = function update_iframe_height( fieldset_prefix )
				{
					var table_height = jQuery( '#' + fieldset_prefix + config.table_id ).height();
					jQuery( '#' + fieldset_prefix + 'attachments_fieldset_wrapper' ).css( { 'height': table_height, 'max-height': table_height } );
				}
			}

			if( config.list_style == 'table' )
			{	// A click event for button to replace old file with name
				jQuery( document ).on( 'click', '#' + config.fieldset_prefix + config.table_id + ' .qq-conflict-replace', function()
					{
						var this_obj = jQuery( this );
						var is_replace = this_obj.children( 'div:first' ).is( ':visible' );
						var old_file_name = this_obj.attr( 'old' );
						var old_file_obj = jQuery( '#' + config.fieldset_prefix + config.table_id + ' input[type=hidden][value="' + old_file_name + '"]' );
						
						// Element found with old file name on the page
						var old_file_exists = ( old_file_obj.length > 0 );
						this_obj.hide();

						// Highlight the rows with new and old files
						var tr_rows = this_obj.parent().parent().children( 'td' );
						if( old_file_exists )
						{
							tr_rows = tr_rows.add( old_file_obj.parent().parent().children( 'td' ) );
						}
						tr_rows.css( 'background', '#FFFF00' );

						// Remove previous errors
						tr_rows.find( 'span.error' ).remove();

						jQuery.ajax(
							{	// Replace old file name with new
								type: 'POST',
								url: htsrv_url + 'async.php',
								data: {
										action: 'conflict_files',
										fileroot_ID: config.fileroot_ID,
										path: config.path,
										oldfile: old_file_name.replace( /^(.+[\/:])?([^\/]+)$/, '$2' ),
										newfile: this_obj.attr( 'new' ).replace( /^(.+[\/:])?([^\/]+)$/, '$2' ),
										format: config.conflict_file_format,
										crumb_conflictfiles: config.crumb_conflictfiles,
									},
								success: function( result )
									{
										var data = jQuery.parseJSON( result );
										if( typeof data.error == 'undefined' )
										{	// Success
											this_obj.show();
											var new_filename_obj = this_obj.parent().find( 'span.fname' );
											
											if( is_replace )
											{	// The replacing was executed, Change data of html elements
												this_obj.children( 'div:first' ).hide();
												this_obj.children( 'div:last' ).show();
											}
											else
											{	// The replacing was reverting, Put back the data of html elements
												this_obj.children( 'div:first' ).show();
												this_obj.children( 'div:last' ).hide();
											}

											if( old_file_exists )
											{	// If old file element exists on the page, we can:
												// Swap old and new names
												var old_filename_obj = old_file_obj.parent().find( 'span.fname' );
												var old_filename_obj_html = old_filename_obj.html();
												old_filename_obj.html( new_filename_obj.html() );
												new_filename_obj.html( old_filename_obj_html );

												var old_icon_link = old_filename_obj.prev();
												if( old_icon_link.length == 0 || old_icon_link.get(0).tagName != 'A' )
												{
													old_icon_link = old_filename_obj.parent().prev();
												}
												if( old_icon_link.length > 0 && old_icon_link.get(0).tagName == 'A' )
												{	// The icons exist to link files, We should swap them
													var old_href = old_icon_link.attr( 'href' );
													old_icon_link.attr( 'href', new_filename_obj.prev().attr( 'href' ) );
													new_filename_obj.prev().attr( 'href', old_href );
												}
											}
											else
											{	// No old file element, Get data from request
												new_filename_obj.html( is_replace ? data.old : data.new );
											}
										}
										else
										{	// Failed
											this_obj.show();
											this_obj.parent().append( '<span class="error"> - ' + data.error + '</span>' );
										}
										tr_rows.css( 'background', '' );
									}
							} );

						return false;
					} );
			}

			if( config.display_support_msg )
			{	// Display a message about the dragdrop support of the current browser
				document.write( '<p class="note">' + file_uploader_note_text + '</p>' );
			}
		};

	if( typeof( evo_init_dragdrop_button_config ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	// Init
	var evo_init_dragdrop_button_config_keys = Object.keys( evo_init_dragdrop_button_config );
	for( var i = 0; i < evo_init_dragdrop_button_config_keys.length; i++ )
	{
		window.init_uploader( evo_init_dragdrop_button_config[evo_init_dragdrop_button_config_keys[i]] );
	}
} );
