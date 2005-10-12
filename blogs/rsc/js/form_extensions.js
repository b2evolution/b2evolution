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