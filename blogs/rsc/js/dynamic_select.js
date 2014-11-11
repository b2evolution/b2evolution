/**
 *	Dynamic select list options
 * 	Example:
 * 	LIST 1 Values 'a','b','c'
 *	LIST 2 Values 'a-1','a-2','b-3','c-2','c-4'
 *	==> After Refresh LIST 2 values '1','2' if SL1 = 'a' -- OR -- '3' if SL1 = 'b'  ..........
 * 	CloneOptions always Values 'a-1','a-2','b-3','c-2','c-4'
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 * @version $Id: dynamic_select.js 9 2011-10-24 22:32:00Z fplanque $
 */

/**
* Called by window.onload event
* Initialize all parent_child selects lists of the form
*/
function init_dynamicSelect()
{
	for( var i = 0; i < nb_dynamicSelects ; i++)
	{
		dynamicSelect( tab_dynamicSelects[i]['parent'], tab_dynamicSelects[i]['child'] );
	}
}

/**
 * Initialize select lists
 *
 * You need to use it to handle parent_child select lists
 * This will clone all select options in order to reuse a filtered subset later, depending on the parent selection.
 * This will also add the onchange handler on the parent
 *
 * @param parent select list
 * @param child select list
 */
function dynamicSelect( id1, id2 )
{
	// Feature test to see if there is enough W3C DOM support
	if (document.getElementById && document.getElementsByTagName)
	{
		// Obtain references to both select boxes
		var sel1 = document.getElementById(id1);
		var sel2 = document.getElementById(id2);
		// Clone the dynamic select box
		var clone = sel2.cloneNode(true);
		// Obtain references to all cloned options
		var clonedOptions = clone.getElementsByTagName("option");
		// Onload init: call a generic function to display the related options in the dynamic select box
		refreshDynamicSelectOptions(sel1, sel2, clonedOptions);
		// Onchange of the main select box: call a generic function to display the related options in the dynamic select box
		sel1.onchange = function()
		{
			refreshDynamicSelectOptions(sel1, sel2, clonedOptions);
		}
	}
}


/**
 * Refresh the child select list when the parent changes.
 *
 * @param parent select list
 * @param child select list
 * @param clone of the child select list initialized
 */
function refreshDynamicSelectOptions( sel1, sel2, clonedOptions )
{
	// Delete all options of the dynamic select box
	while( sel2.options.length )
	{
		sel2.remove(0);
	}
	// Regular expression to test if the value of a cloned option begins with the value of the selected option of the main select box
	var pattern1 = new RegExp( "^" + sel1.options[sel1.selectedIndex].value + "-.*$" );

	// Regular expression to keep only the second part(X2) of the value "X1-X2"
	//var pattern2 = new RegExp( "^.*-(.*)$" );

	// Iterate through all cloned options
	for( var i = 0, j = 0; i < clonedOptions.length; i++ )
	{
		// If the value of a cloned option begins with the value of the selected option of the main select box
		if ( clonedOptions[i].value.match( pattern1 ) || clonedOptions[i].value == '' )
		{
			// Clone the option from the hidden option pool and append it to the dynamic select box
			sel2.appendChild( clonedOptions[i].cloneNode( true ) );

			// keep only the second part of value if exist (pattern 2)
			//	val = sel2.options[j].value.match( pattern2 );
			//if (val) sel2.options[j].value = val[1];
			//j++; //indice options in the new list SL2
		}
	}
}
