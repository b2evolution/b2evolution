<?php
/**
 * This is the handler for different modules action
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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

/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

global $Session, $modules;

// Module param must exists
$module_name = param( 'module', 'string', true );

// Check that this action request is not a CSRF hacked request:
$Session->assert_received_crumb( $module_name.'_action' );

foreach( $modules as $module )
{
	if( $module == $module_name )
	{ // the requested module was founded
		$Module = & $GLOBALS[$module.'_Module'];
		if( method_exists( $Module, 'handle_htsrv_action' ) )
		{	// Module has handle_htsrv_action function, we can call it
			$Module->handle_htsrv_action();
			break;
		}
	}
}

header_redirect();
// exited

/*
 * $Log$
 * Revision 1.1  2011/10/18 09:14:34  efy-asimo
 * Ability to display different module forms and handle module actions  inside the module
 *
 */
?>