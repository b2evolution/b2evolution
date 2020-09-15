/**
 * This file initialize Affix Messages
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 * 
 * Depends on: jQuery
 */

jQuery( document ).ready( function()
{
	if( typeof( evo_init_image_editor_config ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	var saveInProgress = false;
	var ImageEditor = tui.ImageEditor;
	var instance = new ImageEditor('#image-editor', {
			includeUI: {
				loadImage: {
					path: evo_init_image_editor_config.file_url,
					name: evo_init_image_editor_config.file_name,
				},
				theme: whiteTheme,
				menuBarPosition: 'bottom',
				menu: ['crop', 'flip', 'rotate', 'draw', 'shape', 'text', 'mask', 'filter'],
			},
			cssMaxWidth: window.innerWidth - 100,
			cssMaxHeight: window.innerHeight - 200,
			selectionStyle: {
				cornerSize: 20,
				rotatingPointOffset: 70
			}
		});

	// This is a simple way to extend the interface and add a save button
	document.querySelector('.tui-image-editor-header-buttons .tui-image-editor-save-btn').addEventListener('click', this.saveImage);
} );

function reloadImage( mode )
{
	switch( mode )
	{
		case 'advanced':
			instance.loadImageFromURL( evo_init_image_editor_config.file_url, evo_init_image_editor_config.file_name );
			break;

		case 'quick':
			$request = jQuery.ajax( {
						type: 'GET',
						url: evo_init_image_editor_config.ajax_async_url,
						data: {
							action: 'reload_image',
							root: evo_init_image_editor_config.file_root,
							path: evo_init_image_editor_config.file_path,
							crumb_image: evo_init_image_editor_config.image_crumb,
						}
				} );

			$request.done( function( data ) {
				data = JSON.parse( data );
				if( data.status == 'ok' )
				{
					jQuery( '.img_wrapper' ).html( data.content );
				}
				else if( data.status == 'error' )
				{
					alert( data.error_msg );
				}
			} );
			break;
	}
}

function saveImage()
{
	if( !saveInProgress )
	{
		saveInProgress = true;

		// Set save button style to indicate we are processing the request:
		var saveButton = jQuery( '.tui-image-editor-save-btn' );
		saveButton.removeClass( 'tui-error' );
		saveButton.html( 'Saving...' ).addClass( 'tui-processing' );

		// Generate the image data
		var imgData = instance.toDataURL( { format: evo_init_image_editor_config.mimetype } );
		imgData = dataURItoBlob( imgData );

		var fd = new FormData();
		fd.append( 'action', 'save_image' );
		fd.append( 'crumb_image', evo_init_image_editor_config.image_crumb );
		fd.append( 'root', evo_init_image_editor_config.file_root );
		fd.append( 'path', evo_init_image_editor_config.file_path );
		fd.append( 'qquuid', evo_init_image_editor_config.file_qquuid ); // Just random stuff but we need this for the UploadHandler to work
		fd.append( 'image_data', imgData );

		// Send image data to Server
		$request = jQuery.ajax( {
					type: 'POST',
					url: evo_init_image_editor_config.ajax_async_url,
					data: fd,
					processData: false,
					contentType: false,
			} );

		// Response received
		$request.done( function( data ) {
				saveInProgress = false;
				data = JSON.parse( data );
				if( data.status == 'ok' )
				{
					saveButton.html( 'Save OK' );
					setTimeout( function() {
						saveButton.html( 'Save' );
						saveButton.removeClass( 'tui-processing' );
					}, 3000 );
				}
				else if( data.status == 'error' )
				{
					saveButton.removeClass( 'tui-processing' );
					saveButton.addClass( 'tui-error' );
					saveButton.html( 'Save Error' );
					setTimeout( function() {
						saveButton.html( 'Save' );
					}, 3000 );
					if( data.error_msg )
					{
						alert( data.error_msg );
					}
					else
					{
						alert( 'An unknown error has occurred while trying to upload the image.' );
					}
				}
			} );
	}
}

function toggle_editor( mode )
{
	var advanced_editor = jQuery( '#advanced_editor' ),
			advanced_button = jQuery( '#advanced_button' ),
			quick_editor = jQuery( '#quick_editor' ),
			quick_button = jQuery( '#quick_button' );

	switch( mode )
	{
		case 'advanced':
			advanced_editor.show();
			advanced_button.addClass( 'active' );
			quick_editor.hide();
			quick_button.removeClass( 'active' );
			reloadImage( 'advanced' );
			break;

		case 'quick':
			advanced_editor.hide();
			advanced_button.removeClass( 'active' );
			quick_editor.show();
			quick_button.addClass( 'active' );
			reloadImage( 'quick' );
			break;
	}
}