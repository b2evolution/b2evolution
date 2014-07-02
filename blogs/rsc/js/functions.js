/**
 * This file is DEPRECATED. It is left here only so that old plugins can load the functions they need for their toolbars
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 * @version $Id: functions.js 6976 2014-06-25 07:08:15Z yura $
 */


/**
 * Opens a window, centers it and makes sure it gets focus.
 *
 * Used to open popup window in BACK-office and FRONT-office:
 *  - _cronjob_list.view.php: ctrl=crontab, to execute cronjob in new window
 *  - _file.class.php: for url of file view
 *  - _file_list.inc.php: to open directory
 *  - _item.class.php: to open feedbacks ( FRONT-office only in skin "Photoblog" 
 *                                         when skin setting "Comments display" == "In a popup window"(by default)
 *                                         or it may be used by 3rd party skin)
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
 *  - links.js: to insert inline tag like this [image:123:caption text]
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
	if( !( clickdiv = document.getElementById( 'clickdiv_'+filter_name ) )
			|| !( clickimg = document.getElementById( 'clickimg_'+filter_name ) ) )
	{
		alert( 'ID '+filter_name+' not found!' );
		return false;
	}

	// Determine if we want to show or to hide (based on current state).
	hide = document.getElementById( 'clickdiv_'+filter_name ).style.display != 'none';

	if( hide )
	{	// Hide/collapse filters:
		clickdiv.style.display = 'none';
		clickimg.style.backgroundPosition = bgxy_expand;
		jQuery.post( htsrv_url+'anon_async.php?action=collapse_filter&target='+filter_name );
	}
	else
	{	// Show/expand filters
		clickdiv.style.display = 'block';
		clickimg.style.backgroundPosition = bgxy_collapse;
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
	register_callback : function(event, f) {
		if( typeof this.eventHandlers[event] == "undefined" )
		{
			this.eventHandlers[event] = new Array();
		}
		this.eventHandlers[event][this.eventHandlers[event].length] = f;
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
 * Initialize onclick event for each button with attribute "data-func"
 *
 * Used only by plugins for toolbar buttons. FRONT-office and BACK-office.
 */
jQuery( document ).ready( function()
{
	jQuery( '[data-func]' ).each( function()
	{
		var func_args = jQuery( this ).data( 'func' ).match( /([^\\\][^\|]|\\\|)+/g );
		var func_name = func_args[0];
		func_args.splice( 0, 1 );
		for( var i = 0; i < func_args.length; i++ )
		{
			if( func_args[ i ] == 'b2evoCanvas' )
			{ // Replace special param with global object
				func_args[ i ] = b2evoCanvas;
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
		jQuery( this ).bind( 'click', function()
		{ // Bind function in click event
			window[ func_name ].apply( null, func_args );
		} );
		// Remove attribute data-func
		jQuery( this ).removeAttr( 'data-func' );
	} );
} );