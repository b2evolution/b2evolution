<?php
/**
 * This file implements functions for email messaging users.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author jeffbearer: Jeff BEARER.
 *
 * @version $Id$
 */

/**
 * Display "Message User" title if it has been requested
 *
 * {@internal msg_title(-) }}
 *
 * @todo move to {@link Request} class (fplanque)
 *
 * @param string Prefix to be displayed if something is going to be displayed
 * @param mixed Output format, see {@link format_to_output()} or false to
 *								return value instead of displaying it
 */
function msgform_title( $prefix = ' ', $display = 'htmlbody' )
{
	global $disp;

	if( $disp == 'msgform' )
	{
		$info = $prefix.T_('Send an email message');
		if ($display)
			echo format_to_output( $info, $display );
		else
			return $info;
	}
}

/*
 * $Log$
 * Revision 1.8  2004/10/12 18:48:34  fplanque
 * Edited code documentation.
 *
 */
?>