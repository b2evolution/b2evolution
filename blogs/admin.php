<?php
/**
 * This is the main dispatcher for the admin interface.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
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


/**
 * @global boolean Is this an admin page? Use {@link is_admin_page()} to query it, because it may change.
 */
$is_admin_page = true;


$login_required = true;
require_once $inc_path.'_main.inc.php';


// Check global permission:
if( ! $current_User->check_perm( 'admin', 'any' ) )
{	// No permission to access admin...
	require $view_path.'errors/_access_denied.inc.php';
}


// Note: The header file will me merged into admin.php:
require_once dirname(__FILE__).'/_header.php';


// Get requested controller and memorize it:
param( 'ctrl', '/^[a-z0-9_]+$/', $default_ctrl, true );


// Redirect old-style URLs (e.g. /admin/plugins.php), if they come here because the webserver maps "/admin/" to "/admin.php"
if( ! empty( $_SERVER['PATH_INFO'] ) && $_SERVER['PATH_INFO'] != $_SERVER['PHP_SELF'] ) // the "!= PHP_SELF" check seems needed by IIS..
{
	// Try to find the appropriate controller (ctrl) setting
	foreach( $ctrl_mappings as $k => $v )
	{
		if( preg_match( '~'.preg_quote( $_SERVER['PATH_INFO'], '~' ).'$~', $v ) )
		{
			$ctrl = $k;
			break;
		}
	}

	// Sanitize QUERY_STRING
	if( ! empty( $_SERVER['QUERY_STRING'] ) )
	{
		$query_string = explode( '&', $_SERVER['QUERY_STRING'] );
		foreach( $query_string as $k => $v )
		{
			$query_string[$k] = strip_tags($v);
		}
		$query_string = '&'.implode( '&', $query_string );
	}
	else
	{
		$query_string = '';
	}

	header( 'HTTP/1.1 301 Moved Permanently' );
	header( 'Location: '.url_add_param( $admin_url, 'ctrl='.$ctrl.$query_string, '&' ) );
	exit;
}


// Check matching controller file:
if( !isset($ctrl_mappings[$ctrl]) )
{
	debug_die( 'The requested controller ['.$ctrl.'] does not exist.' );
}

// Call the requested controller:
require $control_path.$ctrl_mappings[$ctrl];


/*
 * $Log$
 * Revision 1.11  2006/04/19 20:13:48  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.10  2006/04/14 19:25:31  fplanque
 * evocore merge with work app
 *
 * Revision 1.9  2006/04/11 21:22:25  fplanque
 * partial cleanup
 *
 */
?>
