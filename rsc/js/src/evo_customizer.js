/**
 * This file is used for customizer mode
 */

function evo_customizer_reload_frontoffice( additional_url_params )
{	// Reload iframe with front-office content:
	jQuery( '#evo_customizer__frontoffice_loader' ).show();
	if( typeof( additional_url_params ) == 'undefined' )
	{	// Reload with current url:
		jQuery( '#evo_customizer__frontoffice' ).get(0).contentWindow.location.reload();
	}
	else
	{	// Reload with additional params:
		jQuery( '#evo_customizer__frontoffice' ).get(0).contentWindow.location.href += additional_url_params;
	}
}

function evo_customizer_show_backoffice()
{	// Show back-office panel:
	jQuery( '.evo_customizer__wrapper' ).removeClass( 'evo_customizer__collapsed' );
	// Fix for Safari browser in order to make back-office panel scrollable again after collapse/expand action:
	setTimeout( function() { jQuery( '#evo_customizer__backoffice' ).css( 'height', '100%' ); }, 1 );
}

function evo_customizer_hide_backoffice()
{	// Hide back-office panel:
	jQuery( '.evo_customizer__wrapper' ).addClass( 'evo_customizer__collapsed' );
	// Fix for Safari browser in order to make back-office panel scrollable again after collapse/expand action:
	jQuery( '#evo_customizer__backoffice' ).css( 'height', '99.9%' );
}

function evo_customizer_update_style( setting_input )
{
	var skin_style = jQuery( '#evo_customizer__frontoffice' ).contents().find( 'style#evo_skin_styles' );
	if( skin_style.length == 0 )
	{	// Skip skin without customizable style sheet:
		return;
	}
	var skin_setting_name = setting_input.attr( 'name' ).replace( /^edit_skin_\d+_set_/, '' );

	var is_dynamic_style_updated = false;

	// Replace previous value with new updated:
	var regexp = new RegExp( '(\\/\\*customize:\\*\\/).*?(\\/\\*(([a-z_\\+]+\\+)?' + skin_setting_name + '(\\+[a-z_\\+]+)?)(\\/([a-z]+):([^\\*]+))?\\*\\/)', 'ig' );
	var new_value = setting_input.val();
	skin_style.text( skin_style.text().replace( regexp, function( m0, m1, m2, m3, m4, m5, m6, m7, m8 )
	{
		switch( m7 )
		{
			case 'options':
				// Get preset value:
				m8.split( '|' ).forEach( function( value_preset, i, arr )
				{	// Find preset by selected value:
					var value_preset = value_preset.split( '$' );
					if( value_preset[0] == new_value )
					{	// Use style code what is predefined for the value:
						new_value = value_preset[1];
					}
				} );
				break;
			case 'suffix':
				// Append suffix:
				new_value += m8;
				break;
			case 'type':
				// Special type:
				if( m8 == 'image_file' )
				{	// Special setting with image URL:
					var file_image_obj = setting_input.next().find( '.file_select_item' )
					if( setting_input.attr( 'type' ) == 'hidden' &&
					    setting_input.next().data( 'file-type' ) == 'image' &&
					    file_image_obj.length &&
					    file_image_obj.data( 'file-url' ) )
					{	// If image URL is defined:
						new_value = 'url("' + file_image_obj.data( 'file-url' ) + '")';
					}
					else
					{	// No image:
						new_value = 'none';
					}
				}
				break;
			case 'template':
				// Template, e.g. when style rule should not be applied when value  empty:
				new_value = ( new_value == '' || new_value == 0 ? '' : m8.replace( '#setting_value#', new_value ) );
				break;
		}

		// Mark this field updated
		is_dynamic_style_updated = true;

		return m1 + new_value + m2;
	} ) );

	// Set color styles for submit and cancel buttons depending on dynamic style:
	jQuery( '#evo_customizer__backoffice' ).contents()
		.find( '.evo_customizer__buttons input[type=' + ( is_dynamic_style_updated ? 'button' : 'submit' ) + ']' )
		.removeClass( 'btn-default' )
		.addClass( is_dynamic_style_updated ? 'btn-danger' : 'btn-primary' );
}

jQuery( document ).ready( function()
{
	jQuery( '#evo_customizer__backoffice' ).on( 'load', function()
	{	// If iframe with settings has been loaded
		var backoffice_content = jQuery( this ).contents();
		backoffice_content.find( 'form:not([target])' ).attr( 'target', 'evo_customizer__updater' );
		if( backoffice_content.find( '.evo_customizer__buttons' ).length )
		{	// Set proper bottom margin because buttons block has a fixed position at the bottom:
			backoffice_content.find( 'body' ).css( 'margin-bottom', backoffice_content.find( '.evo_customizer__buttons' ).outerHeight() - 1 );
		}

		if( backoffice_content.find( '.alert.alert-success' ).length )
		{	// Reload front-office iframe with collection preview if the back-office iframe has a message about success updating:
			evo_customizer_reload_frontoffice();
		}

		// Remove the message of successful action:
		var success_messages = backoffice_content.find( '.alert.alert-success' );
		var messages_wrapper = success_messages.parent();
		success_messages.remove();
		if( ! messages_wrapper.find( '.alert' ).length )
		{	// Remove messages wrapper completely if it had only successful messages:
			messages_wrapper.closest( '.action_messages' ).remove();
		}
		var error_accordion_toggler = backoffice_content.find( 'input.field_error' ).closest( '.panel-collapse' );
		if( error_accordion_toggler.length )
		{	// Expand accordion collapsed block if it has at least one field with error:
			error_accordion_toggler.collapse( 'show' );
		}

		// Set proper space before form after top tabs:
		var tabs_height = backoffice_content.find( '.evo_customizer__tabs' ).outerHeight();
		backoffice_content.find( '#customizer_wrapper' ).css( 'padding-top', tabs_height + 'px' );

		backoffice_content.find( '.evo_customizer__tabs a' ).click( function()
		{	// Check to enable/disable designer mode between switching skin and widgets menu entries:
			var designer_mode = ( jQuery( this ).attr( 'href' ).indexOf( 'view=coll_widgets' ) > -1 ) ? 'enable' : 'disable';
			if( designer_mode != jQuery( '#evo_customizer__frontoffice' ).data( 'designer-mode' ) )
			{	// Reload front office iframe only when designer mode was changed:
				evo_customizer_reload_frontoffice( '&designer_mode=' + designer_mode );
				// Save current state of designer mode:
				jQuery( '#evo_customizer__frontoffice' ).data( 'designer-mode', designer_mode );
			}
		} );

		backoffice_content.find( '#evo_customizer__collapser' ).click( function()
		{	// Collapse customizer iframe:
			evo_customizer_hide_backoffice();
		} );

		backoffice_content.find( '#evo_customizer__closer' ).click( function()
		{	// Close customizer iframe:
			window.parent.location.href = jQuery( '.evo_customizer__toggler', window.parent.document ).attr( 'href' );
		} );

		backoffice_content.find( 'form' ).submit( function()
		{	// Disable a submit button when form is starting to be submitted:
			if( jQuery( this ).data( 'orig-submit' ) != '1' )
			{
				var button = jQuery( this ).find( 'input[type=submit]' );
				button.prop( 'disabled', true );
				button.after( '<div id="evo_customizer__form_loader"></div>' );
			}
		} );

		backoffice_content.find( 'form#skin_settings_checkchanges' ).on( 'keypress', function( e )
		{	// Don't submit a form on press "Enter/Return" key:
			return e.target.nodeName == 'TEXTAREA' || e.keyCode != 13;
		} );

		// Open links from widget edit form on top window:
		backoffice_content.find( 'form#widget_checkchanges a:not([target])' ).attr( 'target', '_top' );

		// Update custom styles of the skin:
		backoffice_content.find( 'form#skin_settings_checkchanges' ).find( 'input, textarea' ).on( 'input', function()
		{	// Update style with new changed value:
			evo_customizer_update_style( jQuery( this ) );
		} );
		backoffice_content.find( 'form#skin_settings_checkchanges select' ).on( 'change', function()
		{	// Update style with new changed value:
			evo_customizer_update_style( jQuery( this ) );
		} );
	} );

	jQuery( '#evo_customizer__updater' ).on( 'load', function()
	{	// If iframe with settings has been loaded
		if( jQuery( this ).contents().find( '.alert.alert-success' ).length )
		{	// Reload iframe with collection preview if the updater iframe has a message about success updating:
			evo_customizer_reload_frontoffice();
		}

		// If the updater iframe has the messages about error or warning updating:
		if( jQuery( this ).contents().find( '.alert:not(.alert-success)' ).length || 
		// OR if the settings iframe has the error message from previous updating:
			jQuery( '#evo_customizer__backoffice' ).contents().find( '.alert' ).length )
		{	// Update settings/back-office iframe with new content what we have in updater iframe currently:
			var form = jQuery( '#evo_customizer__backoffice' ).contents().find( 'form' );
			form.removeAttr( 'target' ).data( 'orig-submit', '1' );
			form.find( 'input[type=submit]' ).prop( 'disabled', false ).removeAttr( 'disabled' ).next().remove();
			form.submit();
		}
	} );

	jQuery( '#evo_customizer__frontoffice' ).on( 'load', function()
	{	// If iframe with collection preview has been loaded
		jQuery( this ).contents().find( 'body[class*=coll_]' ).each( function()
		{	// Check if iframe really loads current collection:
			var backoffice_iframe = jQuery( '#evo_customizer__backoffice' );
			var body_class = jQuery( this ).attr( 'class' );
			var instance_name = body_class.match( /(^| )instance_([a-z\d]+)( |$)/i );
			instance_name = ( typeof( instance_name[2] ) == 'undefined' ? false : instance_name[2] );
			if( instance_name === false || backoffice_iframe.data( 'instance' ) != instance_name )
			{	// If page of other site is loaded in front-office iframe:
				alert( evo_js_lang_not_controlled_page );
				location.href = jQuery( '#evo_customizer__frontoffice' ).get( 0 ).contentWindow.location.href.replace( 'customizer_mode=enable&show_toolbar=hidden&redir=no', '' );
				return;
			}
			var coll_id = body_class.match( /(^| )coll_(\d+)( |$)/ );
			coll_id = ( typeof( coll_id[2] ) == 'undefined' ? 0 : coll_id[2] );
			if( coll_id && backoffice_iframe.data( 'coll-id' ) != coll_id )
			{	// Reload left/back-office iframe to customize current loaded collection if different collection has been loaded to the right/front-office iframe:
				backoffice_iframe.get( 0 ).contentWindow.location.href = backoffice_iframe.get( 0 ).contentWindow.location.href.replace( /([\?&]blog=)\d+(&|$)/, '$1' + coll_id + '$2' );
				backoffice_iframe.data( 'coll-id', coll_id );
			}
		} );

		jQuery( this ).contents().find( 'a' ).each( function()
		{	// Prepare links of new loaded content of front-office iframe:
			if( jQuery( this ).closest( '#evo_toolbar' ).length )
			{	// Skip links of evo toolbar:
				return;
			}
			var link_url = jQuery( this ).attr( 'href' );
			var collection_url = jQuery( '#evo_customizer__frontoffice' ).data( 'coll-url' );
			if( typeof( link_url ) != 'undefined' && link_url.indexOf( collection_url ) === 0 )
			{	// Append param to hide evo toolbar and don't redirect for links of the current collection:
				jQuery( this ).attr( 'href', link_url.replace( /^([^#]+)(#.+)?$/, '$1' + ( link_url.indexOf( '?' ) === -1 ? '?' : '&' ) + 'customizer_mode=enable&show_toolbar=hidden&redir=no' + '$2' ) );
			}
			else
			{	// Open all links of other collections and side sites on top window in order to update settings frame or close it:
				jQuery( this ).attr( 'target', '_top' );
			}
		} );

		var evo_toolbar = jQuery( this ).contents().find( '#evo_toolbar' );
		if( evo_toolbar.length )
		{	// Grab evo toolbar from front-office iframe with actual data for current loaded page:
			jQuery( '#evo_toolbar' ).html( evo_toolbar.html() );
		}

		// Revert all animation elements back to show that front-office iframe is refreshed completely:
		jQuery( '#evo_customizer__frontoffice_loader' ).hide();
		jQuery( '#evo_customizer__backoffice' ).contents().find( 'input[type=submit]' ).prop( 'disabled', false );
		jQuery( '#evo_customizer__backoffice' ).contents().find( '#evo_customizer__form_loader' ).remove();
	} );

	jQuery( document ).on( 'click', '.evo_customizer__toggler.active', function()
	{	// Expand customizer iframe if it is collapsed:
		if( jQuery( '.evo_customizer__wrapper' ).hasClass( 'evo_customizer__collapsed' ) )
		{
			evo_customizer_show_backoffice();
			// Prevent open link URL, because we need only to expand currently:
			return false;
		}
	} );

	// Expand/Collapse left customizer panel with vertical toggler line(separator between left and right customizer panels):
	jQuery( '#evo_customizer__vtoggler' )
	.on( 'mousedown', function( e )
	{
		jQuery( this )
			.addClass( 'evo_customizer__vtoggler_resizing' ) // Set class flag to know we are resizing
			.data( 'startX', e.pageX ) // Store x position to detect "click" event vs "resize" event
			// Create temp elements for visualization of resizing:
			.before( '<div id="evo_customizer__vtoggler_helper" class="evo_customizer__vtoggler" style="left:' + ( e.pageX - 3 )+ 'px"></div>' ) // moving bar
			.before( '<div id="evo_customizer__vtoggler_helper2" class="evo_customizer__vtoggler"></div>' ); // static bar
		// Prevent default event in order to don't change mousr cursor to test style while resizing:
		e.originalEvent.preventDefault();
	} )
	.on( 'mousemove', function( e )
	{
		if( jQuery( this ).hasClass( 'evo_customizer__vtoggler_resizing' ) )
		{	// Only in resizing mode:
			var is_collapsed = jQuery( '.evo_customizer__wrapper' ).hasClass( 'evo_customizer__collapsed' );
			var toggler_x = jQuery( this ).data( 'startX' ) > 120 ? 240 : 80;
			if( e.pageX < toggler_x )
			{	// Collapse left customizer panel if cursor is moved to the left:
				if( ! is_collapsed )
				{	// And if it is not collapsed yet:
					evo_customizer_hide_backoffice();
				}
			}
			else if( is_collapsed )
			{	// Expand left customizer panel if cursor is moved to the right and if it is really collapsed:
				evo_customizer_show_backoffice();
			}
			if( e.pageX != jQuery( this ).data( 'startX' ) )
			{	// Set flag if the resizing is detected for at least 1 pixel:
				jQuery( this ).data( 'resized', true );
			}
			// Move vtoggler hepler for mouse cursor:
			jQuery( '#evo_customizer__vtoggler_helper' ).css( 'left', e.pageX );
		}
	} )
	.on( 'mouseup', function()
	{
		if( ! jQuery( this ).data( 'resized' ) )
		{	// If it has not been resized then simulate "click" event to expand/collapse:
			if( jQuery( '.evo_customizer__wrapper' ).hasClass( 'evo_customizer__collapsed' ) )
			{
				evo_customizer_show_backoffice();
			}
			else
			{
				evo_customizer_hide_backoffice();
			}
		}
		jQuery( this )
			.removeClass( 'evo_customizer__vtoggler_resizing' ) // Remove class flag of resizing
			.data( 'resized', false ); // Reset flag of resized
		// Remove temp elements after end of resiging:
		jQuery( '#evo_customizer__vtoggler_helper, #evo_customizer__vtoggler_helper2' ).remove();
	} );
} );