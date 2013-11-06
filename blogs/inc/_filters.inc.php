<?php
/**
 * This is the handler/dispatcher for asynchronous calls (both AJax calls and HTTP GET fallbacks)
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package evocore
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$action = param( 'action', 'string', '' );

switch( $action )
{
	case 'collapse_filter':
	case 'expand_filter':
		param( 'target', 'string', '' );
		if( !empty( $target ) )
		{	// We want to record a 'collapse'/'expand' value:
			$target_status = $action == 'collapse_filter' ? 'collapsed' : 'expanded';
			if( preg_match( '/_(filters|colselect)$/', $target ) )
			{	// accept all _filters and _colselect open/close requests!
				// We have a valid value:
				$Session->set( $target, $target_status );
			}
			else
			{	// Warning: you may not see this on AJAX calls
				$Messages->add( 'Cannot ['.$target_status.'] unknown param ['.$target.']' );
			}
		}
		break;
}

/*
 * $Log$
 * Revision 1.2  2013/11/06 08:03:45  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>