/**
 * This file implements general Javascript functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: extracats.js 9 2011-10-24 22:32:00Z fplanque $
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
