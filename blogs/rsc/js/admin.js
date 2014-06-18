/**
 * General functions for the backoffice.
 * b2evolution - http://b2evolution.net/
 * @version $Id: admin.js 5564 2014-01-06 10:24:07Z attila $
 */


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
function b2edit_reload( form, newaction, blog, params )
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

	// Submit the form:
	form.submit();

	return false;
}
