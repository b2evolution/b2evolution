<?php
/**
 * This file initializes everything BUT the blog!
 *
 * It is useful when you want to do very customized templates!
 * It is also called by more complete initializers.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Matt FOLLETT grants Francois PLANQUE the right to license
 * Matt FOLLETT's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * @version $Id: _main.inc.php 6264 2014-03-19 12:23:26Z yura $
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


// In case of incomplete config folder:
if( !isset($use_db) ) $use_db = true;
if( !isset($use_session) ) $use_session = true;
if( !isset($use_hacks) ) $use_hacks = false;


if( defined( 'EVO_MAIN_INIT' ) )
{	/*
	 * Prevent double loading since require_once won't work in all situations
	 * on windows when some subfolders have caps :(
	 * (Check it out on static page generation)
	 */
	return;
}
define( 'EVO_MAIN_INIT', true );


// == 1. Initialize the most basic stuff: ==
require dirname(__FILE__).'/_init_base.inc.php';


if( $use_db )
{
	// == 2. Initialize DB connection: ==
	require dirname(__FILE__).'/_init_db.inc.php';


	// == 3. Initialize Modules: ==
	// Let the modules load/register what they need:
	$Timer->resume('init modules');
	modules_call_method( 'init' );
	$Timer->pause( 'init modules' );


	// == 4. Initialize Plugins: ==
	// At this point, the first hook is "SessionLoaded"
	// The dnsbl_antispam plugin is an example that uses this to check the user's IP against a list of DNS blacklists.
	load_class( 'plugins/model/_plugins.class.php', 'Plugins' );
	/**
	 * @global Plugins The Plugin management object
	 */
	$Plugins = new Plugins();

	// This is the earliest event you can use
	$Plugins->trigger_event( 'AfterPluginsInit' );

	// == 5. Initialize WWW HIT: ==
	if( ! $is_cli )
	{
		require dirname(__FILE__).'/_init_hit.inc.php';
	}

	$Plugins->trigger_event( 'AfterMainInit' );
}


if( ! $is_cli )
{ // Move user to suspect group by IP address
	antispam_suspect_user_by_IP();
}


$Timer->pause( '_MAIN.inc' );
// LOG with APM:
$Timer->log_duration( '_MAIN.inc' );

?>