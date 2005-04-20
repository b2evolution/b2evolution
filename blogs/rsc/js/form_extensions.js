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
 	while( object.nodeName != 'FORM' )
	{	// loop which goes from the checkbox to the form
		if( typeof( object ) != 'undefined' ) // make sure that we have not gone too far
		{
			object = object.parentNode;
		}
		else
		{
			return 0;
		}
	}
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

	object = get_form( object );

	//checks or unchecks all checkboxes in the form
	i = 0;
	while( i < object.length )
	{
		if( object.elements[i].type == 'checkbox' )
		{
			object.elements[i].checked = action;
		}
		i++;
	}
	
	return object;
	
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

