/**
 * This file is public domain.
 * Source: http://www.alistapart.com/articles/alternate/
 * Changes:
 *  2006-10-17, http://daniel.hahler.de/:
 *  - fix/optimization regarding disabling of chosen stylesheet(-set)
 *  - only use selected style from cookie, if available
 *  - Transformed into prototyped object (encapsulation)
 *  - setActiveStyleSheet(): do not disable all stylesheets in case there's
 *    an invalid one provided as param (e.g. from obsolete cookie);
 *    This would disable all styles..!
 *  - Use addEvent() and do not overwrite window.on(un)load
 */

function StyleSwitcher()
{
	/**
	 * Cross browser event handling for IE5+, NS6+ an Mozilla/Gecko
	 * NOTE: taken/copied here from /rsc/js/functions.js
	 * @author Scott Andrew
	 */
	var addEvent = function( elm, evType, fn, useCapture )
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
	};

	var oThis = this;

	this.onload = function(e)
	{
		var cookie = oThis.readCookie("evo_style");
		var title = cookie ? cookie : oThis.getPreferredStyleSheet();
		oThis.setActiveStyleSheet(title);
	};

	addEvent( window, 'load', this.onload, false );

	this.onload(); // do it now already, so there's no "skin changing" after load on "larger" pages
};


StyleSwitcher.prototype.setActiveStyleSheet = function (title)
{
	var i, a;

	var valid = false;

	// test if it's a valid one:
	for(var i=0; (a = document.getElementsByTagName("link")[i]); i++)
	{
		if(a.getAttribute("rel").indexOf("style") != -1 && a.getAttribute("title"))
		{
			if(a.getAttribute("title") == title)
			{
				valid = true;
				break;
			}
		}
	}

	if( ! valid )
		return false;

	// IE6 and IE7 need to disable all stylesheets first and then re-enable it..
	// ..at least when we call the onload event directly, too.
	// There is a bug with Konqueror (http://bugs.kde.org/135849) but it does not seem to apply here anymore?!
	for(i=0; (a = document.getElementsByTagName("link")[i]); i++)
	{
		if(a.getAttribute("rel").indexOf("style") != -1 && a.getAttribute("title"))
		{
			a.disabled = true;
			if(a.getAttribute("title") == title)
			{
				a.disabled = false;
			}
		}
	}

	this.createCookie("evo_style", title, 365);

	return true;
};

StyleSwitcher.prototype.getActiveStyleSheet = function()
{
	var i, a;
	for(i=0; (a = document.getElementsByTagName("link")[i]); i++)
	{
		if(a.getAttribute("rel").indexOf("style") != -1 && a.getAttribute("title") && !a.disabled)
		{
			return a.getAttribute("title");
		}
	}
	return null;
};

StyleSwitcher.prototype.getPreferredStyleSheet = function()
{
	var i, a;
	for(i=0; (a = document.getElementsByTagName("link")[i]); i++)
	{
		if(a.getAttribute("rel").indexOf("style") != -1
			&& a.getAttribute("rel").indexOf("alt") == -1
			&& a.getAttribute("title") )
		{
			return a.getAttribute("title");
		}
	}
	return null;
};

StyleSwitcher.prototype.createCookie = function(name,value,days)
{
	if (days)
	{
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
};

StyleSwitcher.prototype.readCookie = function(name)
{
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++)
	{
		var c = ca[i];
		while (c.charAt(0)==' ')
		{
			c = c.substring(1,c.length);
		}
		if (c.indexOf(nameEQ) == 0)
		{
			return c.substring(nameEQ.length,c.length);
		}
	}

	return null;
};


var StyleSwitcher = new StyleSwitcher();
