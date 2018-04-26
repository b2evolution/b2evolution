/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 */


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
 * Open the item in a preview window (a new window with target 'b2evo_preview'), by changing
 * the form's action attribute and target temporarily.
 *
 * fp> This is gonna die...
 */
function b2edit_open_preview( form_selector, new_action_url )
{
	var form = jQuery( form_selector );

	if( form.length == 0 )
	{	// Form is not detected on the current page by requested selector:
		// Redirect to new URL without form submitting:
		location.href = new_action_url;
		return false;
	}

	if( form.attr( 'target' ) == 'b2evo_preview' )
	{	// Avoid a double-click on the Preview button:
		return false;
	}

	// Set new form action URL:
	var saved_action_url = form.attr( 'action' );
	form.attr( 'action', new_action_url );

	// Submit a form with a preview action to new opened window:
	form.attr( 'target', 'b2evo_preview' );
	preview_window = window.open( '', 'b2evo_preview' );
	preview_window.focus();
	form.submit();

	// Revert action URL and target of the form to original values:
	form.attr( 'action', saved_action_url );
	form.attr( 'target', '_self' );

	// Don't submit the original form:
	return false;
}


/**
 * Submits the form after setting its action attribute to "newaction" and the blog value to "blog" (if given).
 *
 * This is used to switch to another blog or tab, but "keep" the input in the form.
 */
function b2edit_reload( form_selector, new_action_url, blog, params, reset )
{
	var form = jQuery( form_selector );

	if( form.length == 0 )
	{	// Form is not detected on the current page by requested selector:
		// Redirect to new URL without form submitting:
		location.href = new_action_url;
		return false;
	}

	// Set new form action URL:
	form.attr( 'action', new_action_url );

	var hidden_action_set = false;

	// Set the new form "action" HIDDEN value:
	if( form.find( '[name="actionArray[update]"]' ).length > 0 )
	{	// Is an editing mode?
		form.append( '<input type="hidden" name="action" value="edit_switchtab" />' );
		hidden_action_set = true;
	}
	else if( form.find( '[name="actionArray[create]"]' ).length > 0 )
	{	// Is a creating mode?
		form.append( '<input type="hidden" name="action" value="new_switchtab" />' );
		hidden_action_set = true;
	}
	else
	{	// Other modes:
		form.append( '<input type="hidden" name="action" value="switchtab" />' );
		hidden_action_set = true;
	}

	if( hidden_action_set && ( typeof params != 'undefined' ) )
	{
		for( param in params )
		{
			form.append( '<input type="hidden" name="' + param + '" value="' + params[param] + '" />' );
		}
	}

	// Set the blog we are switching to:
	if( typeof blog != 'undefined' && blog != 'undefined' )
	{
		if( blog == null )
		{ // Set to an empty string, otherwise POST param value will be 'null' in IE and it cause issues
			blog = '';
		}
		form.find( '[name="blog"]' ).val( blog );
	}

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

	return b2edit_reload( '#item_checkchanges', newaction, null, { action: submit_action }, reset );
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

	return b2edit_reload( '#item_checkchanges', newaction, null, { action: submit_action }, false );
}