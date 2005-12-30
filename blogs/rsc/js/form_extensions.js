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
function surround_check( class_name, check_val )
{	// Get all surround_check elements
	var els = document.getElementsByName('surround_check');
	for( i=0; i < els.length; i++)
	{
		el = els[i];
		//alert(el);
		if( check_val == null || el.childNodes[0].checked == check_val )
		{	// Restrict change classname to check_val
			el.className = class_name;
		}
	}	
}

/**
 *	Suround all not checked checkboxes 
 */
function surround_unchecked()
{	
	surround_check( 'checkbox_surround', false );
}
/**
 *	Suround all checked checkboxes 
 */
function surround_checked()
{	
	surround_check( 'checkbox_surround', true);
}

/**
 *	Unsuround all checkboxes 
 */
function unsurround_all()
{	
	surround_check( '', null);
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