/**
 * This file implements general Javascript functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package main
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */


/**
 * Cross browser event handling for IE5+, NS6+ an Mozilla/Gecko
 * @obsolete Use jQuery instead
 * @author Scott Andrew
 */
function addEvent( elm, evType, fn, useCapture )
{
	if( elm.addEventListener )
	{ // Standard & Mozilla way:
		elm.addEventListener( evType, fn, useCapture );
		return true;
	}
	else if( elm.attachEvent )
	{ // IE way:
		var r = elm.attachEvent( 'on'+evType, fn );
		return r;
	}
	else
	{ // "dirty" way (IE Mac for example):
		// Will overwrite any previous handler! :((
		elm['on'+evType] = fn;
		return false;
	}
}


/**
 * Browser status changed.
 * Warning: This is disabled in modern browsers.
 */
function setstatus( message )
{
	window.status = message;
	return true;
}
function resetstatus()
{
	window.status = 'Done';
}

/**
 * Opens a window, centers it and makes sure it gets focus.
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


/**
 * Shows/Hides target_id, and updates text_id object with either
 * text_when_displayed or text_when_hidden.
 *
 * It simply uses the value of the elements display attribute and toggles it.
 *
 * @return false
 */
function toggle_display_by_id( text_id, target_id, text_when_displayed, text_when_hidden )
{
	if( document.getElementById(target_id).style.display=="" )
	{
		document.getElementById( text_id ).innerHTML = text_when_hidden;
		document.getElementById( target_id ).style.display="none";
	}
	else
	{
		document.getElementById( text_id ).innerHTML = text_when_displayed;
		document.getElementById( target_id ).style.display="";
	}
	return false;
}


/**
 * Open or close a clickopen area (by use of CSS style).
 *
 * You have to define a div with id clickdiv_<ID> and a img with clickimg_<ID>,
 * where <ID> is the first param to the function.
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
		hide = document.getElementById( 'clickdiv_'+id ).style.display != 'none';
	}

	if( typeof(displayVisible) == 'undefined' )
	{
		displayVisible = ''; // setting it to "empty" is the default for an element's display CSS attribute
	}

	if( hide )
	{
		clickdiv.style.display = 'none';
		clickimg.src = imgpath_expand;

		return false;
	}
	else
	{
		clickdiv.style.display = displayVisible;
		clickimg.src = imgpath_collapse;

		return false;
	}
}


// deprecated but left for old plugins:
function textarea_replace_selection( myField, snippet, target_document )
{
	textarea_wrap_selection( myField, snippet, '', 1, target_document );
}

/**
 * Textarea insertion code.
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
	if( window.opener
		&& window.opener.b2evo_Callbacks
		&& ( typeof window.opener.b2evo_Callbacks != "undefined" ) )
	{ // callback in parent document (e.g. "Files" popup)
		if( window.opener.b2evo_Callbacks.trigger_callback( "wrap_selection_for_"+myField.id, hook_params ) )
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
		clickimg.src = imgpath_expand;
		asyncRequest( htsrv_url+'async.php?collapse='+filter_name );
	}
	else
	{	// Show/expand filters
		clickdiv.style.display = 'block';
		clickimg.src = imgpath_collapse;
		asyncRequest( htsrv_url+'async.php?expand='+filter_name );
	}

	return false;
}


/**
 * "AJAX" wrapper
 *
 * What this really is actually, is just a function to perform an asynchronous Request to the server.
 * There is no need to have any XML involved.
 *
 * @obsolete Use jQuery instead
 * @param string url urlencoded
 */
function asyncRequest( url )
{
	if (window.XMLHttpRequest)
	{ // browser has native support for XMLHttpRequest object
		req = new XMLHttpRequest();
	}
	else if (window.ActiveXObject)
	{ // try XMLHTTP ActiveX (Internet Explorer) version
		req = new ActiveXObject("Microsoft.XMLHTTP");
	}

	if(req)
	{
		swapSection( '...' );
		//req.onreadystatechange = responseHandler;
    req.onreadystatechange = asyncResponseHandler;
		req.open( 'GET', url, true );
		req.setRequestHeader("content-type","application/x-www-form-urlencoded");
		req.send('dummy');
	}
	else
	{
		swapSection('Your browser does not seem to support XMLHttpRequest.');
	}

	return false;
}

function asyncResponseHandler()
{
	if( req.readyState == 4 )
	{	// Request has been loaded (readyState = 4)
		if( req.status == 200 )
		{	// Status is 200 OK:
			swapSection( req.responseText );
		}
		else
		{
			swapSection("There was a problem retrieving the XML data:\n" + req.statusText);
		}
	}
}

function swapSection( data )
{
	var swappableSection = document.getElementById('asyncResponse');
	if( swappableSection )
	{
		swappableSection.innerHTML = data;
	}
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
 * Fades the relevant object to provide feedback, in case of success.
 * @param jQuery selector
 */
function evoFadeSuccess( selector )
{
	evoFadeBg(selector, new Array("#ffff00", "#ffffff", "#eeee88"), {speed:"fast"});
}


/**
 * Fades the relevant object to provide feedback, in case of failure.
 * @param jQuery selector
 */
function evoFadeFailure( selector )
{
	evoFadeBg(selector, new Array("#9300ff", "#ff000a", "#ff0000"), {speed:"fast"});
}


/**
 * Fades the relevant object to provide feedback, in case of highlighting
 * e.g. for items the file manager get called for ("#fm_highlighted").
 * @param jQuery selector
 */
function evoFadeHighlight( selector )
{
	evoFadeBg(selector, new Array("#ddff00", "#bbff00"));
}


/**
 * Fade jQuery selector via backgrounds colors (bgs), back to original background
 * color and then remove any styles (from animations and others)
 *
 * @param string|jQuery
 * @param Array
 * @param object Options ("speed")
 */
function evoFadeBg( selector, bgs, options )
{
	var origBg = jQuery(selector).css("backgroundColor");
	var speed = options && options.speed || "normal";

	var toEval = 'jQuery(selector).animate({ backgroundColor: ';
	for( e in bgs )
		toEval += '"'+bgs[e]+'"'+'}, "'+speed+'" ).animate({ backgroundColor: ';
	toEval += 'origBg },"'+speed+'", "", function(){jQuery( this ).removeAttr( "style" );});';

	eval(toEval);
}


/*
 * $Log$
 * Revision 1.35  2009/10/13 22:27:06  blueyed
 * Add general EvoFadeBg function, and use it from the other faders. Add EvoFadeHighlight, meant for highlighting. Color scheme cries for improvement.
 *
 * Revision 1.34  2009/10/11 02:51:19  blueyed
 * Add JS functions evoFadeSuccess/evoFadeFailure, derived from doFade. IMHO this is the approach to take throughout the app (at least regarding the stubs), also for Results (uses FAT currently).
 *
 * Revision 1.33  2009/03/22 23:39:33  fplanque
 * new evobar Menu structure
 * Superfish jQuery menu library
 * + removed obsolete JS includes
 *
 * Revision 1.32  2008/06/26 23:36:07  blueyed
 * Move evo_menu_show() and evo_menu_hide() javascript functions to global functions.js, instead of inlining them on every page
 *
 * Revision 1.31  2008/02/20 02:08:49  blueyed
 * doc
 *
 * Revision 1.30  2007/09/16 22:06:37  fplanque
 * minor
 *
 * Revision 1.29  2007/06/30 21:05:17  fplanque
 * fixed for FF; I hope.
 *
 * Revision 1.28  2007/06/29 23:42:30  blueyed
 * - Fixed b2evoCallback in textarea_wrap_selection: uses "wrap_selection_for_" callback now instead of "insert_raw_into_"
 * - trigger_callback now returns null if no callback is registered
 *
 * Revision 1.27  2007/04/20 01:42:32  fplanque
 * removed excess javascript
 *
 * Revision 1.26  2007/03/07 19:26:20  blueyed
 * Fix; for IE IIRC
 *
 * Revision 1.25  2007/01/26 02:12:09  fplanque
 * cleaner popup windows
 *
 * Revision 1.24  2006/12/10 03:05:02  blueyed
 * cleanup
 *
 * Revision 1.23  2006/11/28 19:44:51  blueyed
 * b2evo_Callbacks.trigger_callback() now supports variable number of arguments; textarea_replace_selection(): default to document for target_document arg
 *
 * Revision 1.22  2006/11/26 01:42:10  fplanque
 * doc
 *
 */
