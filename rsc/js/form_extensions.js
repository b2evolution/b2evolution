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


/**
 * Toggles all checkboxes of the current form
 *
 * @param form the form
 * @param integer force set/unset
 */
function check( object, action )
{

	form_obj = get_form( object );

	if( ! form_obj )
	{
		alert( 'Could not find form' );
		return false;
	}

	//checks or unchecks all checkboxes in the form
	i = 0;
	while( i < form_obj.length )
	{
		if( form_obj.elements[i].type == 'checkbox' )
		{
			form_obj.elements[i].checked = action;
		}
		i++;
	}

	// Cancel default action:
	return false;
}


/**
 * Event function
 * check all checkboxes of the current form
 */
function check_all( e )
{	// Get the event target element
	target = findTarget(e);
	// Call check funtion to check all check boxes
	// Cancel the event click (href..)
	check( target, true );
	// For Firefox
	cancelClick( e );
	// For IE
	return false;
}

/**
 * Event function
 * uncheck all checkboxes of the current form
 */
function uncheck_all( e )
{	// Get the event target element
	target = findTarget(e);
	// Call check funtion to uncheck all check boxes
	check( target, false );
	// Cancel the event click (href..)
	// For Firefox
	cancelClick( e );
	// For IE
	return false;
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
 *	Surround or unsurrond all' surround_check' span restrict to check_val
 *	used to surround all check_all checkboxes
 */
function surround_check( e, class_name, check_val )
{	// Get the event target element
	var el = findTarget(e);
	// Get the parent form
	el_form = get_form( el );
	// Get all form inputs
	el_inputs = el_form.getElementsByTagName( 'INPUT' );

	// Loop on all inputs
	for( i = 0 ; i < el_inputs.length ; i++ )
	{
		el_input = el_inputs[i];

		if( el_input.type == 'checkbox' )
		{	// The input is a checkbox
			if( check_val == null || el_input.checked == check_val )
			{	// Change the parent (span) class
				el_input.parentNode.className = class_name;
			}
		}
	}
}

/**
 *	Suround all not checked checkboxes
 */
function surround_unchecked( e )
{
	surround_check( e, 'checkbox_surround', false );
}
/**
 *	Suround all checked checkboxes
 */
function surround_checked( e )
{
	surround_check( e, 'checkbox_surround', true);
}

/**
 *	Unsuround all checkboxes
 */
function unsurround_all( e )
{
	surround_check( e, 'checkbox_surround_init', null);
}

/*
 * 	Add links event on all check_all and un_chek_all links
 */
function init_check_all()
{	// Get all check_all elements

	//var exx = document.getElementsByName('surround_check');
	//alert(exx.length);

	var check_links = document.getElementsByName('check_all_nocheckchanges')
	// Add click event on all check_all links
	for( var i=0; i < check_links.length ; i++ )
	{
		var link = check_links[i];
		jQuery( link ).bind( {
			click: check_all,
			mouseover: surround_unchecked,
			mouseout: unsurround_all
		} );
	}
	// Add click event on all un_check_all links
	var uncheck_links = document.getElementsByName('uncheck_all_nocheckchanges')
	for( var i=0; i < uncheck_links.length ; i++ )
	{
		var link = uncheck_links[i];
		jQuery( link ).bind( {
			click: uncheck_all,
			mouseover: surround_checked,
			mouseout: unsurround_all
		} );
	}
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
 * Display the input text when value is 'new'
 * and hide the input text for all other values
 *
 * @param string ID of the select list
 * @param string value selected
 * @param string class name for the input text
 */
function check_combo( el_ID, value, class_name )
{
	if( value == 'new' )
	{	// Display the input text and focus on

		// Get the combo the input text
		input_text = document.getElementById(el_ID+'_combo' );

		// Display the input text
		input_text.style.display = "inline";

 		// Focus on the new input text
		input_text.focus();
	}
	else
	{ // Hide the input text

		// Get the combo the input text
		input_text = document.getElementById(el_ID+'_combo' );

		// Hide the input text
		input_text.style.display = "none";
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