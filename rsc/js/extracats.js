/**
 * This file implements general Javascript functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.com/}
 *
 * @package admin
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
