/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 */

jQuery( document ).ready( function()
{
	jQuery( '[id^=fadeout-]' ).each( function()
	{ // Highlight each element that requires this
		evoFadeBg( this, new Array( '#FFFF33' ), { speed: 3000 } );
	} );
} );


// Event for styled button to browse files
jQuery( document ).on( 'change', '.btn-file :file', function()
{
	var label = jQuery( this ).val().replace( /\\/g, '/' ).replace( /.*\//, '' );
	jQuery( this ).parent().next().html( label );
} );


/**
 * Open or close a clickopen area (by use of CSS style).
 *
 * You have to define a div with id clickdiv_<ID> and a img with clickimg_<ID>,
 * where <ID> is the first param to the function.
 *
 * Used to expand/collapse in BACK-office:
 *  - _file.funcs.php: to toggle the subfolders in directory list
 *  - _backup_options.form.php: to toggle the backup options on upgrade action
 *  - _plugin_settings.form.php: to toggle the plugin event settings on edit plugin page
 *
 * @param string html id of the element to toggle
 * @param string CSS display property to use when visible ('inline', 'block')
 * @return false
 */
function toggle_clickopen( id, hide, displayVisible )
{
	if( !( clickdiv = document.getElementById( 'clickdiv_'+id ) )
			|| !( clickimg = document.getElementById( 'clickimg_'+id ) ) )
	{
		alert( 'ID '+id+' not found!' );
		return false;
	}

	if( typeof(hide) == 'undefined' )
	{
		hide = clickdiv.style.display != 'none';
	}

	if( typeof(displayVisible) == 'undefined' )
	{
		displayVisible = ''; // setting it to "empty" is the default for an element's display CSS attribute
	}

	clickimg = jQuery( clickimg );
	if( clickimg.hasClass( 'fa' ) || clickimg.hasClass( 'glyphicon' ) )
	{ // Fontawesome icon | Glyph bootstrap icon
		if( clickimg.data( 'toggle' ) != '' )
		{ // This icon has a class name to toggle
			var icon_prefix = ( clickimg.hasClass( 'fa' ) ? 'fa' : 'glyphicon' );
			if( clickimg.data( 'toggle-orig-class' ) == undefined )
			{ // Store original class name in data
				clickimg.data( 'toggle-orig-class', clickimg.attr( 'class' ).replace( new RegExp( '^'+icon_prefix+' (.+)$', 'g' ), '$1' ) );
			}
			if( clickimg.hasClass( clickimg.data( 'toggle-orig-class' ) ) )
			{ // Replace original class name with exnpanded
				clickimg.removeClass( clickimg.data( 'toggle-orig-class' ) )
					.addClass( icon_prefix + '-' + clickimg.data( 'toggle' ) );
			}
			else
			{ // Revert back original class
				clickimg.removeClass( icon_prefix + '-' + clickimg.data( 'toggle' ) )
					.addClass( clickimg.data( 'toggle-orig-class' ) );
			}
		}
	}
	else
	{ // Sprite icon
		var xy = clickimg.css( 'background-position' ).match( /-*\d+/g );
		// Shift background position to the right/left to the one icon in the sprite
		clickimg.css( 'background-position', ( parseInt( xy[0] ) + ( hide ? 16 : - 16 ) ) + 'px ' + parseInt( xy[1] ) + 'px' );
	}

	// Hide/Show content block
	clickdiv.style.display = hide ? 'none' : displayVisible;

	return false;
}


/**
 * Fades the relevant object to provide feedback, in case of success.
 *
 * Used only on BACK-office in the following files:
 *  - _misc_js.funcs.php
 *  - blog_widgets.js
 *  - src/evo_links.js
 *
 * @param jQuery selector
 */
function evoFadeSuccess( selector )
{
	evoFadeBg(selector, new Array("#ddff00", "#bbff00"));
}


/**
 * Fades the relevant object to provide feedback, in case of failure.
 *
 * Used only in BACK-office in the following files:
 *  - _misc_js.funcs.php
 *  - src/evo_links.js
 *
 * @param jQuery selector
 */
function evoFadeFailure( selector )
{
	evoFadeBg(selector, new Array("#9300ff", "#ff000a", "#ff0000"));
}


/**
 * Fades the relevant object to provide feedback, in case of highlighting
 * e.g. for items the file manager get called for ("#fm_highlighted").
 *
 * Used only on BACK-office in the following file:
 *  - _file_list.inc.php
 *
 * @param jQuery selector
 */
function evoFadeHighlight( selector )
{
	evoFadeBg(selector, new Array("#ffbf00", "#ffe79f"));
}


/**
 * Fade jQuery selector via backgrounds colors (bgs), back to original background
 * color and then remove any styles (from animations and others)
 *
 * Used only on BACK-office in the following files:
 *  - _misc_js.funcs.php
 *  - blog_widgets.js
 *  - src/evo_links.js
 *  - _file_list.inc.php
 *
 * @param string|jQuery
 * @param Array
 * @param object Options ("speed")
 */
function evoFadeBg( selector, bgs, options )
{
	var origBg = jQuery(selector).css("backgroundColor");
	var speed = options && options.speed || '"slow"';

	var toEval = 'jQuery(selector).animate({ backgroundColor: ';
	for( e in bgs )
	{
		if( typeof( bgs[e] ) != 'string' )
		{ // Skip wrong color value
			continue;
		}
		toEval += '"'+bgs[e]+'"'+'}, '+speed+' ).animate({ backgroundColor: ';
	}
	toEval += 'origBg }, '+speed+', "", function(){jQuery( this ).css( "backgroundColor", "" );});';

	eval(toEval);
}


/**
 * Set the action attribute on a form, including a Safari fix.
 *
 * This is so complicated, because the form also can have a (hidden) action value.
 *
 * @return boolean
 */
function set_new_form_action( form, newaction )
{
	// Stupid thing: having a field called action !
	var saved_action = form.attributes.getNamedItem('action').value;
	form.attributes.getNamedItem('action').value = newaction;

	// requested host+directory, used for Opera workaround below
	var reqdir = location.href.replace(/(\/)[^\/]*$/, "$1");

	// FIX for Safari (2.0.2, OS X 10.4.3) - (Konqueror does not fail here)
	if( form.attributes.getNamedItem('action').value != newaction
		&& form.attributes.getNamedItem('action').value != reqdir+newaction /* Opera 9.25: action holds the complete URL, not just the given filename */
	)
	{ // Setting form.action failed! (This is the case for Safari)
		// NOTE: checking "form.action == saved_action" (or through document.getElementById()) does not work - Safari uses the input element then
		{ // _Setting_ form.action however sets the form's action attribute (not the input element) on Safari
			form.action = newaction;
		}

		if( form.attributes.getNamedItem('action').value != newaction )
		{ // Still old value, did not work.
			alert('set_new_form_action: Cannot set new form action (Safari workaround).');
			return false;
		}
	}
	// END FIX for Safari

	return true;
}


/**
 * Update iframe to preview the item, by changing
 * the form's action attribute and target temporarily.
 *
 * fp> This is gonna die...
 */
function b2edit_update_item_preview( obj, form_action, submit_action )
{
	var form = jQuery( obj ).closest( 'form' );

	if( form.attr( 'target' ) == 'iframe_item_preview' )
	{	// To avoid a double-click on the Preview/Save button:
		return false;
	}

	if( typeof( form_action ) != 'undefined' && form_action !== false )
	{	// Change form action url to new:
		var saved_form_action = form.attr( 'action' );
		if( ! set_new_form_action( form.get( 0 ), form_action ) )
		{
			alert( "Preview not supported. Sorry. (Could not set form.action for preview)" );
			return false;
		}
	}

	if( typeof( submit_action ) != 'undefined' )
	{	// Save form action field value to new:
		var saved_submit_action = form.find( 'input[name=action]' ).val();
		if( form.find( 'input[name=action]' ).length == 0 )
		{
			form.append( '<input type="hidden" name="action" value="' + submit_action + '" />' );
		}
		else
		{
			form.find( 'input[name=action]' ).val( submit_action );
		}
	}

	// Submit a form in special iframe for preview:
	form.attr( 'target', 'iframe_item_preview' );
	form.submit();

	if( typeof( form_action ) != 'undefined' && form_action !== false )
	{	// Revert form action url to original value:
		form.attr( 'action', saved_form_action );
	}
	form.attr( 'target', '_self' );
	if( typeof( submit_action ) != 'undefined' )
	{	// Revert form action field value to original value:
		form.find( 'input[name=action]' ).val( saved_submit_action );
	}

	// Unfold panel with preview iframe:
	jQuery( '#fieldset_wrapper_itemform_preview.folded' ).removeClass( 'folded' );

	return false;
}


/**
 * Save item/post form by REST API
 *
 * @param object Event object
 * @param integer Item ID
 */
function b2edit_save_item( obj, coll_urlname )
{
	var form = jQuery( obj ).closest( 'form' );
	var item_ID = form.find( 'input[name=post_ID]' ).val();

	// Get all form params for REST API call below:
	var params = {};
	form.find( 'input, select, textarea' ).each( function()
	{
		var field_name = jQuery( this ).attr( 'name' );
		var field_value = jQuery( this ).val();
		var field_type = jQuery( this ).attr( 'type' );
		if( typeof( field_name ) != 'undefined' && field_name != ''
		    && ( ( field_type != 'checkbox' && field_type != 'radio' ) || jQuery( this ).is( ':checked' ) ) )
		{	// Get only really selected params:
			if( typeof( params[ field_name ] ) != 'undefined' && field_name.indexOf( '[' ) != -1 )
			{	// Array param:
				if( typeof( params[ field_name ] ) != 'object' )
				{
					var first_param_value = params[ field_name ];
					params[ field_name ] = [];
					params[ field_name ].push( first_param_value );
				}
				params[ field_name ].push( field_value );
			}
			else
			{	// Scalar param:
				params[ field_name ] = field_value;
			}
		}
	} );

	// Call REST API request to save Item:
	evo_rest_api_request( 'collections/' + coll_urlname + '/items/' + ( item_ID > 0 ? item_ID + '/update' : 'create' ), params,
	function( data )
	{	// Success updating:
		b2edit_save_item_print_messages( data, 'success' );
		// Reload preview iframe:
		b2edit_update_item_preview( obj, data.coll_url );
		if( item_ID == 0 )
		{	// If new item has been created we should switch form to "update" mode in order to don't create the item twice:
			form.find( 'input[name=post_ID]' ).val( data.item_ID );
			form.find( 'input[name^="actionArray[create"]' ).each( function()
			{
				jQuery( this ).attr( 'name', jQuery( this ).attr( 'name' ).replace( '[create', '[update' ) );
			} );
			jQuery( 'a[href$="action=new_type"]' ).each( function()
			{
				jQuery( this ).attr( 'href', jQuery( this ).attr( 'href' ).replace( 'action=new_type', 'action=edit_type&post_ID=' + data.item_ID ) );
				jQuery( this ).attr( 'onclick', jQuery( this ).attr( 'onclick' ).replace( "'new_type'", "'edit_type'" ) );
			} );
			jQuery( 'a[href$="disp=edit"]' ).each( function()
			{
				jQuery( this ).attr( 'href', jQuery( this ).attr( 'href' ).replace( 'disp=edit', 'disp=edit&p=' + data.item_ID ) );
			} );
			jQuery( 'a[href*="prev_action=new"]' ).each( function()
			{
				jQuery( this ).attr( 'href', jQuery( this ).attr( 'href' ).replace( 'prev_action=new', 'prev_action=edit&p=' + data.item_ID ) );
			} );
		}
	}, 'GET',
	function( data )
	{	// Failed updating:
		b2edit_save_item_print_messages( data, 'error' );

		jQuery( '.field_error' ).each( function()
		{	// Clear all error fields from previous request:
			jQuery( this ).removeClass( 'field_error' );
			if( jQuery( this ).next().hasClass( 'help-inline' ) )
			{
				jQuery( this ).next().remove();
			}
		} );

		if( typeof( data.field_errors ) == 'object' )
		{	// Add error messages and red style for fields with entered error:
			for( var i = 0; i < data.field_errors.length; i++ )
			{
				var field_name = data.field_errors[i][0];
				var field_error = data.field_errors[i][1];
				jQuery( '[name="' + field_name + '"]' )
					.addClass( 'field_error' )
					.after( '<span class="help-inline"><span class="field_error" rel="' + field_name + '">' + field_error + '</span></span>' );
			}
		}
	} );

	function b2edit_save_item_print_messages( data, msg_type )
	{
		if( jQuery( '.action_messages' ).length == 0 )
		{	// Create block for messages:
			jQuery( '.page-content' ).before( '<div class="action_messages container-fluid"><ul></ul></div>' );
		}
		// Clear previous messages:
		var messages_list = jQuery( '.action_messages ul' );
		messages_list.html( '' );

		var msg_template = '<li><div class="alert alert-dismissible $message_class$ fade in"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span></button>$message$</div></li>';

		// Print additional messages:
		if( typeof( data.messages ) == 'object' )
		{
			for( var i = 0; i < data.messages.length; i++ )
			{
				messages_list.append( msg_template
					.replace( '$message_class$', b2edit_save_item_get_message_class( data.messages[i][0] ) )
					.replace( '$message$', data.messages[i][1] ) );
			}
		}

		if( typeof( data.error_info ) != 'undefined' )
		{	// Print main message:
			messages_list.append( msg_template
				.replace( '$message_class$', b2edit_save_item_get_message_class( 'error' ) )
				.replace( '$message$', data.error_info ) );
		}

		if( typeof( data.message ) != 'undefined' )
		{	// Print main message:
			messages_list.append( msg_template
				.replace( '$message_class$', b2edit_save_item_get_message_class( msg_type ) )
				.replace( '$message$', data.message ) );
		}
	}

	function b2edit_save_item_get_message_class( msg_type )
	{
		var msg_class = 'alert-info';
		switch( msg_type )
		{
			case 'error':
				msg_class = 'alert-danger';
				break;
			case 'success':
				msg_class = 'alert-success';
				break;
			case 'warning':
				msg_class = 'alert-warning';
				break;
		}
		return msg_class;
	}

	return false;
}


/**
 * Submits the form after setting its action attribute to "newaction" and the blog value to "blog" (if given).
 *
 * This is used to switch to another blog or tab, but "keep" the input in the form.
 */
function b2edit_reload( form, newaction, blog, params, reset )
{
	// Set the new form action URL:
	if( ! set_new_form_action(form, newaction) )
	{
		return false;
	}

	var hidden_action_set = false;

	// Set the new form "action" HIDDEN value:
	if( form.elements.namedItem("actionArray[update]") )
	{
		jQuery(form).append('<input type="hidden" name="action" value="edit_switchtab" />');
		hidden_action_set = true;
	}
	else if( form.elements.namedItem("actionArray[create]") )
	{
		jQuery(form).append('<input type="hidden" name="action" value="new_switchtab" />');
		hidden_action_set = true;
	}
	else
	{
		jQuery(form).append('<input type="hidden" name="action" value="switchtab" />');
		hidden_action_set = true;
	}

	if( hidden_action_set && ( typeof params != 'undefined' ) )
	{
		for( param in params )
		{
			jQuery(form).append('<input type="hidden" name="' + param + '" value="' + params[param] + '" />');
		}
	}

	// Set the blog we are switching to:
	if( typeof blog != 'undefined' && blog != 'undefined' )
	{
		if( blog == null )
		{ // Set to an empty string, otherwise POST param value will be 'null' in IE and it cause issues
			blog = '';
		}
		form.elements.blog.value = blog;
	}

	// form.action.value = 'reload';
	// form.post_title.value = 'demo';
	// alert( form.action.value + ' ' + form.post_title.value );

	// disable bozo validator if active:
	// TODO: dh> this seems to actually delete any events attached to beforeunload, which can cause problems if e.g. a plugin hooks this event
	window.onbeforeunload = null;

	if( typeof( reset ) != 'undefined' && reset == true )
	{ // Reset the form:
		form.reset();
	}

	// Submit the form:
	form.submit();

	return false;
}


/**
 * Submits the form after clicking on link to change item type
 *
 * This is used to switch to another blog or tab, but "keep" the input in the form.
 */
function b2edit_type( msg, newaction, submit_action )
{
	var reset = false;
	if( typeof( bozo ) && bozo.nb_changes > 0 )
	{ // Ask about saving of the changes in the form
		reset = ! confirm( msg );
	}

	return b2edit_reload( document.getElementById( 'item_checkchanges' ), newaction, null, { action: submit_action }, reset );
}


/**
 * Ask to submit the form after clicking on action button
 *
 * This is used to the button "Extract tags"
 */
function b2edit_confirm( msg, newaction, submit_action )
{
	if( typeof( bozo ) && bozo.nb_changes > 0 )
	{	// Ask about saving of the changes in the form:
		if( ! confirm( msg ) )
		{
			return false;
		}
	}

	return b2edit_reload( document.getElementById( 'item_checkchanges' ), newaction, null, { action: submit_action }, false );
}

// Code to resize widths of left and right columns on item/post edit form:
jQuery( document ).ready( function()
{
	if( jQuery( '#item_checkchanges' ).length == 0 )
	{	// Initialize the code below only when preview iframe exists on current page:
		return;
	}

	var b2evo_item_edit_full_width;
	var b2evo_item_edit_min_width = 320;
	jQuery( '.evo_item_form__left_col' ).resizable(
	{
		minWidth: b2evo_item_edit_min_width,
		handles: 'e',
		start: function( e, ui )
		{
			// Get full width of two columns depending on view(two columns per line or right under left col):
			b2evo_item_edit_full_width = ui.element.hasClass( 'evo_item_form__full_width_col' )
				? ui.element.width()
				: ui.element.width() + ui.element.next().width();
			// Remove full width style from left col to resize it:
			ui.element.removeClass( 'evo_item_form__full_width_col' );
			// Display the resize handler as active during all resizing time:
			ui.element.find( '.ui-resizable-handle' ).addClass( 'active_handler' );
			// Create div over preview iframe, because the resizing action is broken when mouse pointer is over iframe:
			jQuery( '#iframe_item_preview_wrapper' ).append( '<div id="iframe_item_preview_disabler"></div>' );
		},
		resize: function( e, ui )
		{
			if( b2evo_item_edit_full_width - ui.element.width() < b2evo_item_edit_min_width )
			{	// If right col width became less 320px then expand this to full width and move under left col:
				ui.element.next().addClass( 'evo_item_form__full_width_col' );
			}
			else
			{	// If normal two columns view:
				// Resize right column on left column resizing:
				ui.element.next().width( b2evo_item_edit_full_width - ui.element.width() )
					.removeClass( 'evo_item_form__full_width_col' );
				// Convert widths to percent values in order to keep ratio on window resize:
				var percent_width = ( ui.element.width() / ( ui.element.width() + ui.element.next().width() ) * 100 ).toFixed(3);
				ui.element.css( 'width', percent_width + '%' );
				ui.element.next().css( 'width', ( 100 - percent_width ) + '%' );
			}
		},
		stop: function( e, ui )
		{
			// Hide the resize handler:
			ui.element.find( '.ui-resizable-handle' ).removeClass( 'active_handler' );
			// Remove a helper to fix iframe issue:
			jQuery( '#iframe_item_preview_disabler' ).remove();
			// Save column width in cookie:
			b2evo_item_edit_save_cookie();
		}
	} );

	function b2evo_item_edit_save_cookie()
	{
		if( jQuery( '.evo_item_form__right_col' ).hasClass( 'evo_item_form__full_width_col' ) )
		{	// If right col has been moved under left col and expanded to full width then left col must be full width too:
			jQuery( '.evo_item_form__left_col' ).addClass( 'evo_item_form__full_width_col' );
			var percent_width = '100';
		}
		else
		{	// Calculate left col width in percents for normal two columns mode:
			var percent_width = ( jQuery( '.evo_item_form__left_col' ).width() / ( jQuery( '.evo_item_form__left_col' ).width() + jQuery( '.evo_item_form__right_col' ).width() ) * 100 ).toFixed(3);
		}
		// Save percent width in cookie:
		jQuery.cookie( 'b2evo_item_edit_column_width'+ ( typeof( blog_id ) == 'undefined' ? '' : '_' + blog_id ), percent_width, { path: '/', expires: 3650 } );
	}

	jQuery( window ).on( 'resize', function( handler )
	{
		if( handler.target !== window )
		{	// Exclude resizing event of left column:
			return;
		}

		if( jQuery( window ).width() < 960 )
		{	// Switch columns to full width view if window width less than 960px:
			jQuery( '.evo_item_form__left_col, .evo_item_form__right_col' ).addClass( 'evo_item_form__full_width_col' );
		}

		if( jQuery( '.evo_item_form__left_col' ).width() < b2evo_item_edit_min_width ||
		    jQuery( '.evo_item_form__right_col' ).width() < b2evo_item_edit_min_width )
		{	// Decrease only max column if at least one column width is less minimum 320px:
			if( jQuery( '.evo_item_form__left_col' ).width() < jQuery( '.evo_item_form__right_col' ).width() )
			{
				var min_column = jQuery( '.evo_item_form__left_col' );
				var max_column = jQuery( '.evo_item_form__right_col' );
			}
			else
			{
				var min_column = jQuery( '.evo_item_form__right_col' );
				var max_column = jQuery( '.evo_item_form__left_col' );
			}
			var percent_width = ( b2evo_item_edit_min_width / jQuery( window ).width() * 100 ).toFixed(3);
			min_column.css( 'width', percent_width + '%' );
			max_column.css( 'width', ( 100 - percent_width ) + '%' );

			// Save column width in cookie:
			b2evo_item_edit_save_cookie();
		}
	} );
} );

// Code to resize height of item preview frame on edit form:
jQuery( document ).ready( function()
{
	if( jQuery( '#iframe_item_preview_wrapper' ).length == 0 )
	{	// Initialize the code below only when preview iframe exists on current page:
		return;
	}

	function update_item_preview_frame_height()
	{
		var body_height = jQuery( '#iframe_item_preview' ).contents().find( 'body' ).height();
		if( body_height == 0 )
		{	// Some browsers cannot get iframe body height correctly, Use this default min value:
			body_height = 600;
		}

		if( jQuery( '#iframe_item_preview_wrapper' ).prop( 'style' ).height == '' &&
		    body_height > jQuery( '#iframe_item_preview_wrapper' ).height() )
		{	// Expand the frame height if it is more than wrapper height:
			jQuery( '#iframe_item_preview_wrapper' ).css( 'height', body_height < 600 ? body_height : 600 );
		}
		// Set max-height on each iframe reload in order to avoid a space after upload button:
		jQuery( '#iframe_item_preview_wrapper' ).css( 'max-height', body_height );
	}

	var iframe_item_preview_is_loaded = false;
	jQuery( '#iframe_item_preview' ).bind( 'load', function()
	{	// Set proper height on frame loading:
		if( ! iframe_item_preview_is_loaded )
		{	// Only on first loading:
			update_item_preview_frame_height();
			iframe_item_preview_is_loaded = true;
		}
	} );

	jQuery( '#icon_folding_itemform_preview, #title_folding_itemform_preview' ).click( function()
	{	// Use this hack to fix frame height on show preview fieldset if it was hidden before:
		update_item_preview_frame_height();
	} );

	jQuery( '#iframe_item_preview_wrapper' ).resizable(
	{	// Make the frame wrapper resizable:
		minHeight: 80,
		handles: 's',
		start: function( e, ui )
		{	// Create a temp div to disable the mouse over events inside the frame:
			ui.element.append( '<div id="iframe_item_preview_disabler"></div>' );
		},
		stop: function( e, ui )
		{	// Remove the temp div element:
			ui.element.find( '#iframe_item_preview_disabler' ).remove();
			// Save height in cookie:
			b2edit_update_panel_cookie_param( 'itemform_preview', 'open', jQuery( this ).height() );
		},
		resize: function( e, ui )
		{	// Limit max height:
			jQuery( '#iframe_item_preview_wrapper' ).resizable( 'option', 'maxHeight', jQuery( '#iframe_item_preview' ).contents().find( 'body' ).height() );
		}
	} );
	jQuery( document ).on( 'click', '#iframe_item_preview_wrapper .ui-resizable-handle', function()
	{	// Increase height on click:
		jQuery( '#iframe_item_preview_wrapper' ).css( 'height', jQuery( '#iframe_item_preview_wrapper' ).height() + 80 );
	} );
} );


/**
 * Update params of panel in cookie
 * Used on forms to edit posts and comments
 *
 * @param string Panel name
 * @param string Visibility: 'open', 'closed'
 * @param string Height
 */
function b2edit_update_panel_cookie_param( panel_name, visibility, height )
{
	var cookie_name = 'editscrnpanels'
		+ ( typeof( blog_id ) == 'undefined' ? '' : '_' + blog_id )
		+ ( typeof( cookie_suffix ) == 'undefined' ? '_b2evo' : cookie_suffix );
	var current_params = jQuery.cookie( cookie_name );
	current_params = current_params ? current_params.split( ';' ) : [];
	var new_params = [];
	var settings_updated = false;

	for( var i = 0, len = current_params.length; i < len; i++ )
	{
		var match = current_params[ i ].match( /^([a-z0-9\-_]+)\(([^\)]+)\)$/i );
		if( match )
		{
			if( panel_name == match[1] )
			{	// Update settings of the requested panel:
				var height_param = '';
				if( typeof( height ) != 'undefined' )
				{	// Update height:
					height_param = ',' + height;
				}
				else
				{
					var params = match[2].split( ',' );
					if( typeof( params[1] ) != 'undefined' )
					{	// Save previous height value:
						height_param = ',' + params[1];
					}
				}
				new_params.push( match[1] + '(' + visibility + height_param + ')' );
				settings_updated = true;
			}
			else
			{	// Keep settings of other panels:
				new_params.push( match[0] );
			}
		}
	}

	if( ! settings_updated )
	{	// Add new param:
		new_params.push( panel_name + '(' + visibility + ( typeof( height ) != 'undefined' ? ',' + height : '' ) + ')' );
	}

	jQuery.cookie( cookie_name, new_params.join( ';' ), { path: '/', expires: 3650 } );
}