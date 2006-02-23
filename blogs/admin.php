<?php
/**
 * This is the main dispatcher for the admin interface.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package main
 *
 * @version $Id$
 */


/**
 * Do the MAIN initializations:
 */
require_once dirname(__FILE__).'/conf/_config.php';

$login_required = true;
require_once $inc_path.'_main.inc.php';


// Check global permission:
if( ! $current_User->check_perm( 'admin', 'any' ) )
{	// No permission to access admin...
	require dirname(__FILE__).'/_access_denied.inc.php';
}


// Note: The header file will me merged into admin.php:
require_once dirname(__FILE__).'/_header.php';


// Get requested controller and memorize it:
param( 'ctrl', '/^[a-z0-9]+$/', $default_ctrl, true );

// Check matching controller file:
if( !isset($ctrl_mappings[$ctrl]) )
{
	die( 'The requested controller ['.$ctrl.'] does not exist.' );
}

// Call the requested controller:
require $control_path.$ctrl_mappings[$ctrl];

?>