/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 * @version $Id: form_extensions.js 8373 2015-02-28 21:44:37Z fplanque $
 */


/**
 * Takes an object and returns the parent form
 *
 * @param object the child object
 * @return form the parent form of the child object
 */
function get_form( object )
{
 	while( object.tagName != 'FORM' )
	{	// loop which goes from the checkbox to the form
		// alert( object.nodeName );
		if( typeof( object ) != 'undefined' ) // make sure that we have not gone too far
		{
			object = object.parentNode;
		}
		else
		{
			return false;
		}
	}
	// alert( 'ok:'+object.tagName );
	return object;
}


/*
 * Cancel a click event
 */
function cancelClick( e )
{
	if( window.event && window.event.returnValue )
	{	// Ie
		window.event.returnValue = false;
	}
	if( e && e.preventDefault )
	{	// Firefox
		e.preventDefault();
	}
	return false;
}


/**
 * Clear the form the current object belongs to
 *
 * @param object the object whose form must be cleared
 */
function clear_form( object )
{
	object = check( object, false );

	// empties all the input fields of the form
	i = 0;
	while( i < object.length )
	{
		if( object.elements[i].type == 'text' )
		{
			object.elements[i].value = '';
		}
		i++;
	}

	return object;
}


/**
 * focus on the first form input text
 */
function focus_on_first_input()
{
	all_inputs = document.getElementsByTagName( 'input' );

	if( all_inputs.length )
	{ // There is at least one input
		// Loop on all inputs to find the first input text
		for( i = 0 ; i < all_inputs.length ; i++ )
		{
      if( all_inputs[i].type == 'text'
					&& all_inputs[i].disabled != true
					)
			{	// We found the first input text, so we focus on it
				try
				{	// Will fail in IE if element is not visible/displayed
					all_inputs[i].focus();
				}
				catch( ex )
				{
				}
				break;
			}
		}
	}
}

// This will be conditionnaly enabled by PHP:
//addEvent( window, 'load', focus_on_first_input, false );

/**
 * Handle Combo Boxes
 * Display the input text when value is 'new'(first option to enter new value)
 * and hide the input text for all other values
 *
 * @param string|object jQuery selector or JavaScript object of the combo box <select> element
 */
function check_combo( selector )
{
	var select_obj = jQuery( selector ),
	input_obj = select_obj.next();

	if( select_obj.find( 'option:first' ).is( ':selected' ) )
	{	// Display the input text and focus on:
		input_obj.show().focus();
		if( select_obj.attr( 'required' ) == 'required' )
		{	// Combo box is required, restore the appropriate attribute for input:
			input_obj.attr( 'required', 'required' );
		}
	}
	else
	{	// Hide the input text:
		input_obj.hide();
		input_obj.removeAttr( 'required' );
	}
}


/**
 * Decorate an input field with a "help value", which gets
 * removed onfocus and re-added onblur (if the fields real
 * value is still unchanged).
 *
 * @param string ID of the input field
 * @param string "Help value"
 */
function input_decorated_help( id, hvalue )
{
	var elm = document.getElementById(id);

	var onblur = function() {
			if( elm.value == '' || elm.value == hvalue )
			{
				elm.style.color = '#666';
				elm.value = hvalue;
			}
		}

	jQuery( elm ).bind( 'blur', onblur );

	jQuery( elm ).bind( 'focus', function() {
			elm.style.color = '';

			if( elm.value == hvalue )
				elm.value = '';
		} );

	/* on form's submit: set to empty, if help value */
	jQuery( elm.form ).bind( 'submit', function() {
			if( elm.value == hvalue )
			{
				elm.value = '';
			}
		} );

	// init:
	onblur();
}


/**
 * caters for the differences between Internet Explorer and fully DOM-supporting browsers
 */
function findTarget( e )
{
	var target;

	if( window.event && window.event.srcElement )
		target = window.event.srcElement;
	else if( e && e.target )
		target = e.target;
	if( ! target )
		return null;

	while( target != document.body && target.nodeName.toLowerCase() != 'a' )
		target = target.parentNode;

	if( target.nodeName.toLowerCase() != 'a' )
		return null;

	return target;
}


/**
 * Add spinner to button
 */
function addSpinner( button )
{
	button = jQuery( button );
	button.addClass( 'btn-spinner' );
	button.css( 'width', '+=24px' );
}


/**
 * Set custom validity of field based on input value length
 * @param obj Input field
 * @param integer mininum input value length
 * @param string message to display if input value does not meet minimum length
 */
function checkInputLength( input, minLength, message )
{
	if( input.value.length < minLength )
	{
		input.setCustomValidity( message );
	}
	else
	{
		input.setCustomValidity( '' );
	}
}