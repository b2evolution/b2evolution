/*
 * General functions for the backoffice.
 */


/*
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

	// FIX for Safari (2.0.2, OS X 10.4.3), to not submit the item on "Preview"! - (Konqueror does not fail here)
	if( form.attributes.getNamedItem('action').value == saved_action )
	{ // Still old value: Setting form.action failed! (This is the case for Safari)
		// NOTE: checking "form.action == saved_action" (or through document.getElementById()) does not work - Safari uses the input element then
		{ // _Setting_ form.action however sets the form's action attribute (not the input element) on Safari
			form.action = newaction;
		}

		if( form.attributes.getNamedItem('action').value == saved_action )
		{ // Still old value, did not work.
			return false;
		}
	}
	// END FIX for Safari

	return true;
}

/*
 * Open the item in a preview window (a new window with target 'b2evo_preview'), by changing
 * the form's action attribute and target temporarily.
 *
 * fplanque: created
 */
function b2edit_open_preview(form, newaction)
{
	if( form.target == 'b2evo_preview' )
	{ // A double-click on the Preview button
		return false;
	}

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


/*
 * Submits the form after setting its action to "newaction" and the blog value to "blog" (if given).
 *
 * This is used to switch to another blog or tab, but "keep" the input in the form.
 */
function b2edit_reload( form, newaction, blog )
{
	if( ! set_new_form_action(form, newaction) )
	{
		return false;
	}

	if( typeof blog != 'undefined' )
	{
		form.blog.value = blog;
	}

	// form.action.value = 'reload';
	// form.post_title.value = 'demo';
	// alert( form.action.value + ' ' + form.post_title.value );
	form.submit();
	return false;
}

