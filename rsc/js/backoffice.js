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
 * Open the item in a preview window (a new window with target 'b2evo_preview'), by changing
 * the form's action attribute and target temporarily.
 *
 * fp> This is gonna die...
 */
function b2edit_open_preview( form, newaction )
{
	if( form.target == 'b2evo_preview' )
	{ // A double-click on the Preview button
		return false;
	}

	var saved_action = form.attributes.getNamedItem('action').value;
	if( ! set_new_form_action(form, newaction) )
	{
		alert( "Preview not supported. Sorry. (Could not set form.action for preview)" );
		return false;
	}

	form.target = 'b2evo_preview';
	preview_window = window.open( '', 'b2evo_preview' );
	preview_window.focus();
	// submit after target window is created.
	form.submit();
	form.attributes.getNamedItem('action').value = saved_action;
	form.target = '_self';
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