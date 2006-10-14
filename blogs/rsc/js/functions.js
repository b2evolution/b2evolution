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
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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
 * Opens a window and makes sure it gets focus.
 */
function pop_up_window( href, target, params )
{
	if( typeof(params) == 'undefined' )
	{
		params = 'width=750,height=550,scrollbars=yes,status=yes,resizable=yes';
	}

	opened = window.open( href, target, params );
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


/**
 * Textarea insertion code.
 *
 * TODO: Make the quicktags plugin use this general function.
 * @var element
 * @var text
 * @var document (needs only be passed from a popup window as window.opener.document)
 */
function textarea_replace_selection( myField, snippet, target_document )
{
	if( b2evo_Callbacks.trigger_callback( "insert_raw_into_"+myField.id, snippet ) )
	{
		return;
	}
	if( window.opener && ( typeof window.opener.b2evo_Callbacks != "undefined" ) )
	{ // we're called in a popup: try that b2evo_Callbacks event
		if( window.opener.b2evo_Callbacks.trigger_callback( "insert_raw_into_"+myField.id, snippet ) )
		{
			return;
		}
	}

	if (target_document.selection)
	{ // IE support:
		myField.focus();
		sel = target_document.selection.createRange();
		sel.text = snippet;
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

		myField.value = myField.value.substring(0, startPos)
										+ snippet
										+ myField.value.substring(endPos, myField.value.length);
		cursorPos = startPos+snippet.length;

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
		myField.value += snippet;
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
 * @todo dh> I'd really like to have a JSON-Callback instead of the fixed "scheme" that only allows
 *       to toggle an element with ID "asyncResponse".. this is very limited.. :/
 *       I vote for the MochiKit JS framework which provides a decent "Async" library.
 *
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
 *   b2evo_Callbacks.register_callback( "insert_raw_into_itemform_post_content", function(value) {
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
 *   if( b2evo_Callbacks.trigger_callback("insert_raw_into_"+b2evoCanvas.id, tag) )
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

	trigger_callback : function(event, value) {
		if( typeof this.eventHandlers[event] == "undefined" )
		{
			return false;
		}

		var r = false;
		for( var i = 0; i < this.eventHandlers[event].length; i++ )
		{
			var f = this.eventHandlers[event][i];
			r = eval( "f(value);" ) || r;
		}
		return r;
	}
};

var b2evo_Callbacks = new b2evo_Callbacks();
