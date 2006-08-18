/**
 * "BOZO VALIDATOR 2" : Check if a form has been changed but not submitted when a bozo clicks
 * on a link which will result in potential data input loss
 *
 * Used for bozos, ask for confirmation to change the current page when he clicks on a link after having done changes on inputs forms
 *	without saving them
 *
 * Tested on Firefox (XP & Mac OSX) , IE6 (XP), Safari (Mac OSX)
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 */
var bozo_confirm_mess;

var bozo = {

	// array of changes for each form we need to verify (needed to detect if another form has changed when we submit)
	'tab_changes' : Object(), 	
	// Total number of changes
	'nb_changes' : 0, 					

	// If no translated message has been provided, use this default:
	'confirm_mess' : bozo_confirm_mess ? bozo_confirm_mess : 'You have modified this form but you haven\'t submitted it yet.\nYou are about to lose your edits.\nAre you sure?',

	/**
	 *	BOZO VALIDATOR INITIALIZATION
	 * This is designed to track changes on forms whith an ID including '_checkchanges'
	 */
	init: function ( )
	{	// Loop through all forms
		var date_deb = new Date();

		// Loop through all forms:
		for( var i = 0; i < document.forms.length ; i++ )
		{ // Get the next form element:
			var el_form = document.forms[i];

			// add submit event on the form to control if there are changes on others forms:
			addEvent( el_form, 'submit', bozo.validate_submit, false );

			// Get all inputs for this form:
			all_inputs = el_form.getElementsByTagName( 'input' );

			if( el_form.id.indexOf( '_checkchanges' ) == -1 )
			{	// The form has no '_checkchanges' ID, we won't react on changes BUT we still need to react on SUBMIT...
				// Loop through all form inputs:
				for( var j = 0; j < all_inputs.length; j++ )
				{	// Get the next input element:
					var field = all_inputs[j];
					if( field.type == 'submit' )
					{	// The input is a submit, so we add a click event to validate_submit function
						addEvent( field , 'click', bozo.validate_submit, false );
					}
					// TODO: handle IMAGE type
				}
				continue;
			}

			// Initialize this form as having no changes yet:
			bozo.tab_changes[el_form.id] = 0;

			// Loop through all form inputs:
			for( var j = 0; j < all_inputs.length; j++ )
			{	// Get the next input element:
				var field = all_inputs[j];

				if( field.className.indexOf( 'no_checkchanges' ) == -1  )
				{	// We want to track changes on this field:

					if( field.type == 'submit' )
					{	// The input is a submit, so we add no event
					}
					// TODO: handle IMAGE type
					else if( field.type == 'reset' )
					{	// The input is a reset, so we add a click event to reset_changes function
						addEvent( field , 'click', bozo.reset_changes, false );
					}
					else
					{	// The input is not a submit/image/reset, so we add a change event:
						addEvent( field , 'change', bozo.change, false );
						addEvent( field , 'keypress', bozo.change, false );
					}
				}
			}

			all_textareas = el_form.getElementsByTagName( 'textarea' );
			// Loop on all form textareas
			for( var j = 0; j < all_textareas.length; j++ )
			{
					var field = all_textareas[j];
					addEvent( field , 'change', bozo.change, false );
					addEvent( field , 'keypress', bozo.change, false );
			}

			all_selects = el_form.getElementsByTagName( 'select' );
			// Loop on all form selects
			for( var j = 0; j < all_selects.length; j++ )
			{
					var field = all_selects[j];
					addEvent( field , 'change', bozo.change, false );
			}
		}


		var date_fin = new Date();
		var tps = date_fin.getTime() - date_deb.getTime();;
		//alert( tps );
	},


	/**
	 *	caters for the differences between Internet Explorer and fully DOM-supporting browsers
	 */
	findTarget: function ( e )
	{
		var target;
		if (window.event && window.event.srcElement)
			target = window.event.srcElement;
		else if (e && e.target)
			target = e.target;

		if (!target)
			return null;

		return target;
	},


	/*
	 * called when there is a change event on an element
	 */
	change: function( e )
	{	// Get the target element
		var target = bozo.findTarget( e );
		// Update changes number for his parent form
		bozo.tab_changes[ get_form( target ).id ]++;
		// Update Total changes number
		bozo.nb_changes++;
	},


	/*
	 * Call when there is a click on a reset input
	 * Reset changes
	 */
	reset_changes: function ( e )
	{
		alert('reset');
	
		// Loop on the forms changes array
		for( i in bozo.tab_changes )
		{	// Reset changes number to 0
			bozo.tab_changes[i] = 0;
		}
		// Total changes number
		bozo.nb_changes = 0;
	},


	/*
	 *	Called when there is a click event on a submit button
	 *	If there are no changes on others forms, cancel onbeforeunload event
	 */
	validate_submit: function( e )
	{
			var target = bozo.findTarget(e);

			var other_form_changes = 0;

			// Loop on the forms changes array
			for( i in bozo.tab_changes )
			{
				if ( ( i != get_form( target ).id ) && bozo.tab_changes[i] )
				{	// Another form contains input changes
					other_form_changes++;
				}
			}

			if( !other_form_changes )
			{	// There are no changes on others forms, so cancel onbeforeunload event
				window.onbeforeunload = '';
				return;
			}
	},



	/*
	 *	Called when the user close the window
	 *	Ask confirmation to close page without saving changes if there have been changes on all form inputs
	 */
	validate_close: function( e )
	{
		if( bozo.nb_changes )
		{	// there are input changes
			if(window.event)
			{ // For ie:
				window.event.returnValue = bozo.confirm_mess;
			}
			else
			{ // For firefox:
				//e.preventDefault();
				//alert('pjl');
				return bozo.confirm_mess;
			}
		}
  },

}

// Init Bozo validator when the window is loaded:
addEvent( window, 'load', bozo.init, false );
// The following does not seem to work with addEvent... !?
// addEvent( window, 'beforeunload', bozo.validate_close, false );
window.onbeforeunload = bozo.validate_close;
