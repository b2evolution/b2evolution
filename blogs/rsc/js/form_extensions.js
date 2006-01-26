/**
 * @author fsaya: Fabrice SAYA-GASNIER / PROGIDISTRI
 *
 * @version $Id$
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
	target =  findTarget(e);
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
	target =  findTarget(e);
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
			{	// Change the checkbox input class
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
	surround_check( e, '', null);
}

/*
 * 	Add links event on all check_all and un_chek_all links 
 */
function init_check_all()
{	// Get all check_all elements
	
	//var exx = document.getElementsByName('surround_check');
	//alert(exx.length);
	
	var check_links = document.getElementsByName('check_all')
	// Add click event on all check_all links
	for( var i=0; i < check_links.length ; i++ )
	{
		var link = check_links[i];
		addEvent( link, 'click', check_all, false );
		addEvent( link, 'mouseover', surround_unchecked, false );
		addEvent( link, 'mouseout', unsurround_all, false );
	}
	// Add click event on all un_check_all links
	var uncheck_links = document.getElementsByName('uncheck_all')
	for( var i=0; i < uncheck_links.length ; i++ )
	{
		var link = uncheck_links[i];
		addEvent( link, 'click', uncheck_all, false );
		addEvent( link, 'mouseover', surround_checked, false );
		addEvent( link, 'mouseout', unsurround_all, false );
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
	if( first_form = document.forms[0] )
	{	// There is a form in the document, so we get all inputs of the first form
		all_inputs = first_form.getElementsByTagName( 'input' );
		
		// Loop on all inputs to find the first input text
		for( i = 0 ; all_inputs[i].type != 'text' ; i++ ); 
		
		if( all_inputs[i] )
		{	// We found the first input text, so we focus on
			all_inputs[i].focus();
		}
	}
}


/**
 * Handle Combo Boxes
 * Add an input text to the select list when new is selected
 * And remove it when another value is selected
 *
 * @param string ID of the select list
 * @param string value selected
 */
function check_combo( el_ID, value )
{	
	if( value == 'new' )
	{	// Add an input text to the select list
		
		// Get the parent of the select  list
		parent_combo = document.getElementById(el_ID ).parentNode;	
		
		// Create an input element
		input_text = 	document.createElement("input");
		
		// Set its type to text, id, name, size, name,...
		input_text.type='text';
 		input_text.id = el_ID+'_combo';
 		input_text.name = el_ID+'_combo';
 		input_text.size = 30;
 		
 		// Add to the parent of the select list
 		parent_combo.appendChild( input_text );
 		
 		// Focus on the new input text
		input_text.focus(); 		
	}
	else if( input_text = document.getElementById( el_ID+'_combo' ) )
	{ // We don't want to add an input text to the select list but there is already one, so we remove it 
		parent_combo = input_text.parentNode;
		parent_combo.removeChild( input_text);
	}
}
