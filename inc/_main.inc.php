<?php
/**
 * This file initializes everything BUT the blog!
 *
 * It is useful when you want to do very customized templates!
 * It is also called by more complete initializers.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package evocore
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