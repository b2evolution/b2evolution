/**
 * This file implements general Javascript functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package main
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id$
 */


/**
 * Open or close a clickopen area (by use of CSS style).
 *
 * @param string id des Menus
 * @param string CSS display property to use when visible ('inline', 'block')
 * @return false
 */
function toggle_clickopen( id, hide, displayVisible )
{
	if( typeof(hide) == 'undefined' )
	{
		hide = document.getElementById( 'clickdiv_'+id ).style.display != 'none';
	}
	if( typeof(displayVisible) == 'undefined' )
	{
		displayVisible = 'block';
	}

	if( !( clickdiv = document.getElementById( 'clickdiv_'+id ) )
			|| !( clickimg = document.getElementById( 'clickimg_'+id ) ) )
	{
		alert( 'ID '+id+' not found!' );
		return false;
	}

	clickimg.style.display = 'inline';

	if( hide )
	{
		clickdiv.style.display = 'none';
		clickimg.src = imgpath_expand;

		return false;
	}
	else
	{
		clickdiv.style.display = displayVisible;
		clickimg.src = imgpath_collapse;

		return false;
	}
}

