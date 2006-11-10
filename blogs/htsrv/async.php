<?php
/**
 * This is the handler for asynchronous 'AJAX' calls.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * }}
 *
 * @package evocore
 *
 * @version $Id$
 */


/**
 * Do the MAIN initializations:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

/**
 * HEAVY :(
 *
 * @todo dh> refactor _main.inc.php to be able to include small parts
 *           (e.g. $current_User, charset init, ...) only..
 *           It works already for $DB (_connect_db.inc.php).
 */
require_once $inc_path.'_main.inc.php';

param( 'action', 'string', '' );


// Check global permission:
// TODO: there might be actions for anon users also..
if( empty($current_User) || ! $current_User->check_perm( 'admin', 'any' ) )
{	// No permission to access admin...
	require $view_path.'errors/_access_denied.inc.php';
}


switch( $action )
{
	case 'add_plugin_sett_set':
		header('Content-type: text/html; charset='.$io_charset);

		param( 'plugin_ID', 'integer', true );
		$Plugin = & $Plugins->get_by_ID($plugin_ID);
		if( ! $Plugin )
		{
			bad_request_die('Invalid Plugin.');
		}
		param( 'set_type', 'string', '' ); // "Settings" or "UserSettings"
		if( $set_type != 'Settings' && $set_type != 'UserSettings' )
		{
			bad_request_die('Invalid set_type param!');
		}
		param( 'set_path', '/^\w+(?:\[\w+\])+$/', '' );

		require_once $inc_path.'_misc/_plugin.funcs.php';

		// Init the new setting set:
		_set_setting_by_path( $Plugin, $set_type, $set_path, array() );

		$r = get_plugin_settings_node_by_path( $Plugin, $set_type, $set_path );

		$Form = new Form(); // fake Form
		display_plugin_settings_fieldset_field( $set_path, $r[3], $Plugin, $Form, $set_type = 'Settings', $set_target = NULL, $r[2] );

		exit;

	case 'del_plugin_sett_set':
		// TODO: may use validation here..
		echo 'OK';
		exit;
}


/**
 * @todo dh> What's the reason to delegate to another file here, instead of
 *           having it all here?
 */
require_once $inc_path.'_async.inc.php';


// QUESTION: dh> is this really meant to handle expanding and collapsing only??
// fp> NO this is meant to be extended

// Debug info:
echo '-expand='.$expand;
echo '-collapse='.$collapse;

/*
 * $Log$
 * Revision 1.6  2006/11/10 16:37:30  blueyed
 * Send charset
 *
 * Revision 1.5  2006/11/09 23:40:57  blueyed
 * Fixed Plugin UserSettings array type editing; Added jquery and use it for AJAHifying Plugin (User)Settings editing of array types
 *
 * Revision 1.4  2006/11/02 18:14:59  fplanque
 * normalized
 *
 * Revision 1.3  2006/11/02 02:04:08  blueyed
 * QUESTION
 *
 * Revision 1.2  2006/10/14 04:43:55  blueyed
 * MFB: E_FATAL for anon user
 *
 * Revision 1.1  2006/06/01 19:06:27  fplanque
 * a taste of Ajax in the framework
 *
 */
?>