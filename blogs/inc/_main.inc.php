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
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 * @author mfollett: Matt FOLLETT
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
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


// Initialize the most basic stuff
require dirname(__FILE__).'/_init_base.inc.php';


if( $use_db )
{
	// Initialize DB connection
	require dirname(__FILE__).'/_init_db.inc.php';


	// Let the modules load/register what they need:
	$Timer->resume('init modules');
	modules_call_method( 'init' );
	$Timer->pause( 'init modules' );


	// Initialize Plugins
	// At this point, the first hook is "SessionLoaded"
	// The dnsbl_antispam plugin is an example that uses this to check the user's IP against a list of DNS blacklists.
	load_class( 'plugins/model/_plugins.class.php', 'Plugins' );
	/**
	 * @global Plugins The Plugin management object
	 */
	$Plugins = new Plugins();


	// Initialize WWW HIT
	if( ! $is_cli )
	{
		require dirname(__FILE__).'/_init_hit.inc.php';
	}
}

// Load hacks file if it exists (DEPRECATED):
if( $use_hacks && file_exists($conf_path.'hacks.php') )
{
	$Timer->resume( 'hacks.php' );
	include_once $conf_path.'hacks.php';
	$Timer->pause( 'hacks.php' );
}


/*
 * $Log$
 * Revision 1.137  2009/12/07 17:32:51  blueyed
 * fix typos
 *
 * Revision 1.136  2009/12/06 05:20:36  fplanque
 * Violent refactoring for _main.inc.php
 * Sorry for potential side effects.
 * This needed to be done badly -- for clarity!
 *
 * Revision 1.135  2009/12/06 03:24:11  fplanque
 * minor/doc/fixes
 *
 * Revision 1.134  2009/12/04 23:27:50  fplanque
 * cleanup Expires: header handling
 *
 * Revision 1.133  2009/12/02 00:05:52  fplanque
 * no message
 *
 * Revision 1.132  2009/11/30 00:22:04  fplanque
 * clean up debug info
 * show more timers in view of block caching
 *
 * Revision 1.131  2009/11/23 18:41:17  fplanque
 * Make sure we are calling the right page (on the right domain) to make sure that session cookie goes through
 *
 * Revision 1.130  2009/11/20 23:56:39  fplanque
 * minor  + doc
 *
 * Revision 1.129  2009/09/29 03:47:06  fplanque
 * doc
 *
 * Revision 1.128  2009/09/28 23:57:31  blueyed
 * if locale_from_get is used, set redir=no
 *
 * Revision 1.127  2009/09/26 13:50:28  tblue246
 * MFH: Reverting fix for timezone warnings, let users fix their errors themselves.
 *
 * Revision 1.126  2009/09/26 12:00:42  tblue246
 * Minor/coding style
 *
 * Revision 1.125  2009/09/25 21:52:03  tblue246
 * - Suppress PHP warning ("It is not safe to rely on the system's timezone settings. [...]").
 * - Doc about odd PHP behaviour regarding error reporting settings.
 *
 * Revision 1.124  2009/09/25 07:32:52  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.123  2009/09/20 16:55:14  blueyed
 * Performance boost: add Timer_noop class and use it when not in debug mode.
 *
 * Revision 1.122  2009/09/20 16:21:17  blueyed
 * If locale gets set from REQUEST (locale_from_get), do not override it from user settings.
 *
 * Revision 1.121  2009/09/16 00:48:50  fplanque
 * getting a bit more serious with modules
 *
 * Revision 1.120  2009/09/16 00:25:41  fplanque
 * rollback of stuff that doesn't make any sense at all!!!
 *
 * Revision 1.118  2009/09/15 19:31:54  fplanque
 * Attempt to load classes & functions as late as possible, only when needed. Also not loading module specific stuff if a module is disabled (module granularity still needs to be improved)
 * PHP 4 compatible. Even better on PHP 5.
 * I may have broken a few things. Sorry. This is pretty hard to do in one swoop without any glitch.
 * Thanks for fixing or reporting if you spot issues.
 *
 * Revision 1.117  2009/09/14 12:26:53  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.116  2009/09/08 19:17:59  fplanque
 * reverted change that broke user registration
 *
 * Revision 1.115  2009/08/30 18:52:11  tblue246
 * Removed checking of unused variable
 *
 * Revision 1.114  2009/08/23 00:25:27  sam2kb
 * Never use locale from HTTP_ACCEPT nor locale from REQUEST when we set DB connection charset
 *
 * Revision 1.113  2009/08/12 12:01:49  sam2kb
 * doc
 *
 * Revision 1.112  2009/08/06 15:11:15  fplanque
 * doc
 *
 * Revision 1.111  2009/07/28 23:51:08  sam2kb
 * Do locale selection and set DB connection charset as early as possible
 * in order to get results in the right encoding
 */
?>
