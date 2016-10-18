/**
 * This file is DEPRECATED. It is left here only so that old plugins can load the functions they need for their toolbars
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 */


/**
 * Opens a window, centers it and makes sure it gets focus.
 *
 * Used to open popup window in BACK-office and FRONT-office:
 *  - _cronjob_list.view.php: ctrl=crontab, to execute cronjob in new window
 *  - _file.class.php: for url of file view
 *  - _file_list.inc.php: to open directory
 *  - system.ctrl.php: to view phpinfo
 */
function pop_up_window( href, target, width, height, params )
{
	if( typeof(width) == 'undefined' )
	{
		width = 750;
	}

	if( typeof(height) == 'undefined' )
	{
		height = 550;
	}

	var left = (screen.width - width) / 2;
	var top = (screen.height - height) / 2;

	if( typeof(params) == 'undefined' )
	{
		params = 'scrollbars=yes, status=yes, resizable=yes, menubar=yes';
	}

	params = 'width=' + width + ', height=' + height + ', ' + 'left=' + left + ', top=' + top + ', ' + params;

	// Open window:
	opened = window.open( href, target, params );

	// Bring to front!
	opened.focus();

	if( typeof(openedWindows) == 'undefined' )
	{
		openedWindows = new Array(opened);
	}
	else
	{
		openedWindows.push(opened);
	}

	// Tell the caller there is no need to process href="" :
	return false;
}


// deprecated but left for old plugins:
function textarea_replace_selection( myField, snippet, target_document )
{
	textarea_wrap_selection( myField, snippet, '', 1, target_document );
}

/**
 * Textarea insertion code.
 *
 * Used on FRONT-office (EDITING) and BACK-office in the following files:
 *  - By each plugin that works with textarea content of post or comment, to insert a code inside content by click event of toolbar button
 *  - upload.ctrl.php: ???
 *  - _file_list.inc.php: ???
 *  - src/evo_links.js: to insert inline tag like this [image:123:caption text]
 *
 * @var element
 * @var text
 * @var text
 * @var boolean
 * @var document (needs only be passed from a popup window as window.opener.document)
 */
function textarea_wrap_selection( myField, before, after, replace, target_document )
{
	target_document = target_document || document;

	var hook_params = {
		'element': myField,
		'before': before,
		'after': after,
		'replace': replace,
		'target_document': target_document
	};

	// First try, if a JavaScript callback is registered to handle this.
	// E.g. the tinymce_plugin uses registers "wrap_selection_for_itemform_post_content"
	//      to replace the (non-)selection
	if( b2evo_Callbacks.trigger_callback( "wrap_selection_for_"+myField.id, hook_params ) )
	{
		return;
	}

	if( window.opener && ( typeof window.opener != "undefined" ) )
	{
		try
		{ // Try find object 'b2evo_Callbacks' on window.opener to avoid halt error when page was opened from other domain
			if( window.opener.b2evo_Callbacks &&
		   ( typeof window.opener.b2evo_Callbacks != "undefined" ) &&
		   window.opener.b2evo_Callbacks.trigger_callback( "wrap_selection_for_"+myField.id, hook_params ) )
			{ // callback in opener document (e.g. "Files" popup)
				return;
			}
		}
		catch( e )
		{ // Catch an error of the cross-domain restriction
			// Ignore this error because it dies when browser has no permission to access to other domain windows
		}
	}

	if( window.parent
		&& ( typeof window.parent != "undefined" )
		&& window.parent.b2evo_Callbacks
		&& ( typeof window.parent.b2evo_Callbacks != "undefined" ) )
	{	// callback in parent document (e.g. "Links" iframe)
		if( window.parent.b2evo_Callbacks.trigger_callback( "wrap_selection_for_"+myField.id, hook_params ) )
		{
			return;
		}
	}

	// Basic handling:
	if(target_document.selection)
	{ // IE support:
		myField.focus();
		sel = target_document.selection.createRange();
		if( replace )
		{
			sel.text = before + after;
		}
		else
		{
			sel.text = before + sel.text + after;
		}
		myField.focus();
	}
	else if (myField.selectionStart || myField.selectionStart == '0')
	{ // MOZILLA/NETSCAPE support:
		var startPos = myField.selectionStart;
		var endPos = myField.selectionEnd;
		var cursorPos;

		var scrollTop, scrollLeft;
		if( myField.type == 'textarea' && typeof myField.scrollTop != 'undefined' )
		{ // remember old position
			scrollTop = myField.scrollTop;
			scrollLeft = myField.scrollLeft;
		}

		if( replace )
		{
			myField.value = myField.value.substring( 0, startPos)
				+ before
				+ after
				+ myField.value.substring( endPos, myField.value.length);
			cursorPos = startPos + before.length + after.length;
		}
		else
		{
			myField.value = myField.value.substring( 0, startPos)
				+ before
				+ myField.value.substring(startPos, endPos)
				+ after
				+ myField.value.substring( endPos, myField.value.length);
			cursorPos = endPos + before.length + after.length;
		}

		if( typeof scrollTop != 'undefined' )
		{ // scroll to old position
			myField.scrollTop = scrollTop;
			myField.scrollLeft = scrollLeft;
		}

		myField.focus();
		myField.selectionStart = cursorPos;
		myField.selectionEnd = cursorPos;
	}
	else
	{ // Default browser support:
		myField.value += before + after;
		myField.focus();
	}
}

/**
 * Replace substring in textarea.
 *
 * Used on FRONT-office (EDITING) and BACK-office in the following files:
 *  - src/evo_links.js: to remove inline tag like this [image:123:caption text]
 *
 * @var element
 * @var text
 * @var text
 * @var document (needs only be passed from a popup window as window.opener.document)
 */
function textarea_str_replace( myField, search, replace, target_document )
{
	target_document = target_document || document;

	var hook_params = {
		'element': myField,
		'search': search,
		'replace': replace,
		'target_document': target_document
	};

	// First try, if a JavaScript callback is registered to handle this.
	// E.g. the tinymce_plugin uses registers "wrap_selection_for_itemform_post_content"
	//      to replace the (non-)selection
	if( b2evo_Callbacks.trigger_callback( 'str_replace_for_' + myField.id, hook_params ) )
	{
		return;
	}

	if( window.opener && ( typeof window.opener != 'undefined' ) )
	{
		try
		{ // Try find object 'b2evo_Callbacks' on window.opener to avoid halt error when page was opened from other domain
			if( window.opener.b2evo_Callbacks &&
			    ( typeof window.opener.b2evo_Callbacks != 'undefined' ) &&
			    window.opener.b2evo_Callbacks.trigger_callback( 'str_replace_for_' + myField.id, hook_params ) )
			{ // callback in opener document (e.g. "Files" popup)
				return;
			}
		}
		catch( e )
		{ // Catch an error of the cross-domain restriction
			// Ignore this error because it dies when browser has no permission to access to other domain windows
		}
	}

	if( window.parent &&
	    ( typeof window.parent != 'undefined' ) &&
	    window.parent.b2evo_Callbacks &&
	    ( typeof window.parent.b2evo_Callbacks != 'undefined' ) )
	{ // callback in parent document (e.g. "Links" iframe)
		if( window.parent.b2evo_Callbacks.trigger_callback( 'str_replace_for_' + myField.id, hook_params ) )
		{
			return;
		}
	}

	// Replace substring with new value
	myField.value = myField.value.replace( search, replace );
	myField.focus();
}


/**
 * Open or close a filter area (by use of CSS style).
 *
 * You have to define a div with id clickdiv_<ID> and a img with clickimg_<ID>,
 * where <ID> is the first param to the function.
 *
 * Used to expand/collapse a filter area of Results table on FRONT-office and BACK-office in the following files:
 *  - _uiwidget.class.php
 *
 * @param string html id of the element to toggle
 * @return false
 */
function toggle_filter_area( filter_name )
{
	// Find objects to toggle:
	var clickdiv = jQuery( '#clickdiv_'+filter_name );
	var clickimg = jQuery( '#clickimg_'+filter_name );
	if( clickdiv.length == 0 || clickimg.length == 0 )
	{
		alert( 'ID '+filter_name+' not found!' );
		return false;
	}

	if( clickimg.hasClass( 'fa' ) || clickimg.hasClass( 'glyphicon' ) )
	{	// Fontawesome icon | Glyph bootstrap icon
		if( clickimg.data( 'toggle' ) != '' && clickimg.data( 'toggle' ) != undefined )
		{	// This icon has a class name to toggle
			var icon_prefix = ( clickimg.hasClass( 'fa' ) ? 'fa' : 'glyphicon' );
			if( clickimg.data( 'toggle-orig-class' ) == undefined )
			{	// Store original class name in data
				clickimg.data( 'toggle-orig-class', clickimg.attr( 'class' ).replace( new RegExp( '^'+icon_prefix+' (.+)$', 'g' ), '$1' ) );
			}
			if( clickimg.hasClass( clickimg.data( 'toggle-orig-class' ) ) )
			{	// Replace original class name with exnpanded
				clickimg.removeClass( clickimg.data( 'toggle-orig-class' ) )
					.addClass( icon_prefix + '-' + clickimg.data( 'toggle' ) );
			}
			else
			{	// Revert back original class
				clickimg.removeClass( icon_prefix + '-' + clickimg.data( 'toggle' ) )
					.addClass( clickimg.data( 'toggle-orig-class' ) );
			}
		}
	}
	else
	{	// Sprite icon
		var xy = clickimg.css( 'background-position' ).match( /-*\d+/g );
		// Shift background position to the right/left to the one icon in the sprite
		clickimg.css( 'background-position', ( parseInt( xy[0] ) + ( !clickdiv.is( ':hidden' ) ? 16 : - 16 ) ) + 'px ' + parseInt( xy[1] ) + 'px' );
	}

	if( !clickdiv.is( ':hidden' ) )
	{	// Hide/collapse filters:
		clickdiv.slideUp( 500 );
		jQuery.post( htsrv_url+'anon_async.php?action=collapse_filter&target='+filter_name );
	}
	else
	{	// Show/expand filters
		clickdiv.slideDown( 500 );
		jQuery.post( htsrv_url+'anon_async.php?action=expand_filter&target='+filter_name );
	}

	return false;
}


/*
 * Javascript callback handling, for helping plugins to interact in Javascript.
 *
 * This is, so one plugin (e.g. the tinymce_plugin) can say that it handles insertion of raw
 * content into a specific element ("itemform_post_content" in this case):
 *
 * <code>
 * if( typeof b2evo_Callbacks == "object" )
 * { // add a callback, that lets us insert the
 *   b2evo_Callbacks.register_callback( "wrap_selection_for_itemform_post_content", function(value) {
 *       tinyMCE.execCommand( "mceInsertRawHTML", false, value );
 *       return true;
 *     } );
 * }
 * </code>
 *
 * and others (e.g. the smilies_plugin or the youtube_plugin) should first try to use this
 * callback to insert the HTML:
 *
 * if( typeof b2evo_Callbacks == 'object' )
 * { // see if there's a callback registered that should handle this:
 *   if( b2evo_Callbacks.trigger_callback("wrap_selection_for_"+b2evoCanvas.id, tag) )
 *   {
 *     return;
 *   }
 * }
 */
function b2evo_Callbacks() {
	this.eventHandlers = new Array();
};

b2evo_Callbacks.prototype = {
	register_callback : function(event, f, single_event) {
		if( typeof this.eventHandlers[event] == "undefined" )
		{
			this.eventHandlers[event] = new Array();
		}
		if( typeof( single_event ) != 'undefined' && single_event )
		{	// Use only single last registered event:
			this.eventHandlers[event][0] = f;
		}
		else
		{	// Keep all registered events:
			this.eventHandlers[event][this.eventHandlers[event].length] = f;
		}
	},

	/**
	 * @param String event name
	 * @param mixed argument1
	 * @param mixed argument2
	 * ...
	 * @return boolean true, if any callback returned true
	 *                 null, if no callback registered
	 */
	trigger_callback : function(event, args) {

		if( typeof this.eventHandlers[event] == "undefined" )
		{
			return null;
		}

		var r = false;

		// copy arguments and build function param string for eval():
		var cb_args = '';
		var cb_arguments = arguments;
		for( var i = 1; i < arguments.length; i++ ) {
			cb_args += "cb_arguments[" + i + "], ";
		}
		if( cb_args.length )
		{ // remove last ", ":
			cb_args = cb_args.substring( 0, cb_args.length - 2 );
		}

		// eval() for each registered callback:
		for( var i = 0; i < this.eventHandlers[event].length; i++ )
		{
			var f = this.eventHandlers[event][i];
			r = eval( "f("+cb_args+");" ) || r;
		}

		return r;
	}
};

var b2evo_Callbacks = new b2evo_Callbacks();


/**
 * Display alert message in top-right corner
 *
 * Used on FRONT-office and BACK-office in the following file:
 *  - _comment_js.funcs.php
 *
 * @param string Message
 */
function evoAlert( message )
{
	var previous_alerts = jQuery( '.b2evo_alert' );
	if( previous_alerts.length > 0 )
	{ // Clear previous alerts
		previous_alerts.remove();
	}

	jQuery( 'body' ).append( '<div class="b2evo_alert">' + message + '</div>' );
	setTimeout( function()
	{ // Hide alert after 3 seconds
		jQuery( '.b2evo_alert' ).fadeOut(
		{
			complete: function() { jQuery( this ).remove(); }
		} );
	}, 3000 );

	if( !evo_alert_events_initialized )
	{ // Initialize events only one time
		evo_alert_events_initialized = true;
		jQuery( document ).on( 'click', '.b2evo_alert', function(){
			jQuery( this ).remove();
		} );
	}
}
evo_alert_events_initialized = false;


/**
 * Initialize onclick event for each button with attribute "data-func"
 *
 * Used only by plugins for toolbar buttons. FRONT-office and BACK-office.
 */
jQuery( document ).ready( function()
{
	jQuery( document ).on( 'click', '[data-func]', function()
	{
		var func_args = jQuery( this ).data( 'func' ).match( /([^\\|]|\\\|)+/g );
		var func_name = func_args[0];
		func_args.splice( 0, 1 );
		for( var i = 0; i < func_args.length; i++ )
		{
			if( func_args[ i ].indexOf( 'b2evoCanvas' ) > -1 )
			{ // Replace special param with global object
				func_args[ i ] = window[ func_args[ i ] ];
			}
			else if( func_args[ i ] == ' ' )
			{ // Fix an empty param
				func_args[ i ] = '';
			}
			else
			{ // Back escaped delimiter
				func_args[ i ] = func_args[ i ].replace( /\\\|/g, '|' );
			}
		}

		if( jQuery( this ).closest( '.disabled[class*=_toolbar]' ).length > 0 )
		{	// Deny action when toolbar is disabled:
			return false;
		}

		// Execute the function of this element:
		window[ func_name ].apply( null, func_args );

		// Prevent default event:
		return false;
	} );

	// Enable/Disable plugin toolbars depending on selected plugins for current edit form:
	function change_plugin_toolbar_activity( this_obj )
	{
		var prefix = this_obj.data( 'prefix' ) ? this_obj.data( 'prefix' ) : '';
		var toolbar_obj = jQuery( '.' + prefix + this_obj.val() + '_toolbar' );
		if( toolbar_obj.length == 0 )
		{ // Skip this function if plugin has no toolbar
			return true;
		}

		if( this_obj.is( ':checked' ) )
		{ // Enable toolbar:
			toolbar_obj.removeClass( 'disabled' );
			toolbar_obj.find( 'input[type=button]' ).removeAttr( 'disabled' );
		}
		else
		{ // Disable toolbar:
			toolbar_obj.addClass( 'disabled' );
			toolbar_obj.find( 'input[type=button]' ).attr( 'disabled', 'disabled' );
		}
	}
	jQuery( 'input[type=checkbox][name="renderers[]"]' ).each( function() { change_plugin_toolbar_activity( jQuery( this ) ) } );
	jQuery( 'input[type=checkbox][name="renderers[]"]' ).click( function() { change_plugin_toolbar_activity( jQuery( this ) ) } );
} );