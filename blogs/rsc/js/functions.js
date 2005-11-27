/**
 * This file implements general Javascript functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
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
	if( typeof(hide) == 'undefined' )
	{
		hide = document.getElementById( 'clickdiv_'+id ).style.display != 'none';
	}
	if( typeof(displayVisible) == 'undefined' )
	{
		displayVisible = 'block';
	}

	if( !( clickdiv = document.getElementById( 'clickdiv_'+id ) )
			|| !( clickimg = document.getElementById( 'clickimg_'+id ) ) )
	{
		alert( 'ID '+id+' not found!' );
		return false;
	}

	clickimg.style.display = 'inline';

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
 */
function textarea_replace_selection( myField, snippet )
{
	if (document.selection)
	{ // IE support:
		myField.focus();
		sel = document.selection.createRange();
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

