/**
 * This javascript gets included in debug mode.
 * b2evolution - http://b2evolution.net/
 * @version $Id: debug.js 2875 2013-01-30 12:46:23Z yura $
 */


/**
 * Javascript function to toggle DIVs (EXPLAIN, results, backtraces).
 * Used in DB and other debug_output related functions.
 */
function debug_onclick_toggle_div( div_id, text_show, text_hide, insert_before ) {
	if( typeof insert_before === 'undefined' )
	{ // Insert a toggle text before div by default, Use FALSE to insert it after
		insert_before = true;
	}

	var divs = div_id.split(/\s*,\s*/);

	var a = document.createElement("a");
	a.href= "#";
	var a_onclick = function() {
		for( var i=0; i<divs.length; i++ )
		{
			var div = document.getElementById(divs[i]);

			// A.innerHTML follows visibility of first element
			if( i == 0 )
				a.innerHTML = div.style.display == '' ? " [" + text_show + "] " : " [" + text_hide + "] ";

			div.style.display = div.style.display == '' ? 'none' : div.style.display = '';
		}
		return false;
	};
	a.onclick = a_onclick;
	if( insert_before )
	{ // Insert before
		var div = document.getElementById(divs[0]);
		div.parentNode.insertBefore(a, div);
	}
	else
	{ // Insert after
		var div = document.getElementById(divs[divs.length-1]);
		div.parentNode.insertBefore(a, div.nextSibling);
	}
	a_onclick();
};