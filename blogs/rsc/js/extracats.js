/**
 * This file implements general Javascript functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package main
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */


/**
 * Automagically checks the matching extracat when we select a new main cat
 */
function check_extracat( radio )
{
	var main_cat_ID = radio.value;

	// Get ALL the links in the current document:
	var extracats = document.getElementsByName('post_extracats[]');

	// Go through all the links:
	for(var i = 0; i < extracats.length; i++)
	{
		var extracat_checkbox = extracats[i];
		if( extracat_checkbox.value == main_cat_ID )
		{
			extracat_checkbox.checked = true;
		}
	}
}
