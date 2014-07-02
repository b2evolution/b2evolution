/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 * @version $Id: backoffice.js 7043 2014-07-02 08:35:45Z yura $
 */

jQuery( document ).ready( function()
{
	jQuery( '[id^=fadeout-]' ).each( function()
	{ // Highlight each element that requires this
		evoFadeBg( this, new Array( '#FFFF33' ), { speed: 3000 } );
	} );
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
	var xy = clickimg.css( 'background-position' ).match( /-*\d+/g );

	if( hide )
	{
		clickdiv.style.display = 'none';
		// Shift background position to the right to the one icon in the sprite
		clickimg.css( 'background-position', ( parseInt( xy[0] ) + 16 ) + 'px ' + parseInt( xy[1] ) + 'px' );
	}
	else
	{
		clickdiv.style.display = displayVisible;
		// Shift background position to the left to the one icon in the sprite
		clickimg.css( 'background-position', ( parseInt( xy[0] ) - 16 ) + 'px ' + parseInt( xy[1] ) + 'px' );
	}

	return false;
}


/**
 * Fades the relevant object to provide feedback, in case of success.
 *
 * Used only on BACK-office in the following files:
 *  - _misc_js.funcs.php
 *  - blog_widgets.js
 *  - links.js
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
 *  - links.js
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
 *  - links.js
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
		toEval += '"'+bgs[e]+'"'+'}, '+speed+' ).animate({ backgroundColor: ';
	}
	toEval += 'origBg }, '+speed+', "", function(){jQuery( this ).removeAttr( "style" );});';

	eval(toEval);
}