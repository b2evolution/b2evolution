<?php
/**
 * This file initializes everything BUT the blog!
 *
 * It is useful when you want to do very customized templates!
 * It is also called by more complete initializers.
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
 *
 * In addition, as a special exception, the copyright holders give permission to link
 * the code of this program with the PHP/SWF Charts library by maani.us (or with
 * modified versions of this library that use the same license as PHP/SWF Charts library
 * by maani.us), and distribute linked combinations including the two. You must obey the
 * GNU General Public License in all respects for all of the code used other than the
 * PHP/SWF Charts library by maani.us. If you modify this file, you may extend this
 * exception to your version of the file, but you are not obligated to do so. If you do
 * not wish to do so, delete this exception statement from your version.
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * {@internal Below is a list of former authors whose contributions to this file have been
 *            either removed or redesigned and rewritten anew:
 *            - t3dworld
 *            - tswicegood
 * }}
 *
 * @version $Id$
 */

if( defined( 'EVO_MAIN_INIT' ) )
{ // Prevent double loading since require_once won't work in all situations
	// on windows when some subfolders have caps :(
	// (Check it out on static page generation)
	return;
}
define( 'EVO_MAIN_INIT', true );


/**
 * Load base + advanced configuration:
 */
require_once( dirname(__FILE__).'/../conf/_config.php' );
if( !$config_is_done )
{ // base config is not done!
	$error_message = 'Base configuration is not done! (see /conf/_config.php)';
}
elseif( !isset( $locales[$default_locale] ) )
{
	$error_message = 'The default locale '.var_export( $default_locale, true ).' does not exist! (see /conf/_locales.php)';
}
if( isset( $error_message ) )
{ // error & exit
	require dirname(__FILE__).'/_conf_error.inc.php';
}


/**
 * Load logging class
 */
require_once( dirname(__FILE__).'/_log.class.php' );
/**
 * Debug message log for debugging only (initialized here)
 * @global Log $Debuglog
 */
$Debuglog = new Log( 'note' );
/**
 * Info & error message log for end user (initialized here)
 * @global Log $Messages
 */
$Messages = new Log( 'error' );


/**
 * Check conf...
 */
if( !function_exists( 'gzencode' ) )
{ // when there is no function to gzip, we won't do it
	$Debuglog->add( '$use_gzipcompression is true, but the function gzencode() does not exist. Disabling gzip compression.' );
	$use_gzipcompression = false;
}

/**
 * Include obsolete functions
 */
@include_once( dirname(__FILE__).'/_obsolete092.php' );


/**
 * Includes:
 */
require_once( dirname(__FILE__).'/_misc.funcs.php' );

timer_start();

/**
 * Sets various arrays and vars
 */
require_once( dirname(__FILE__).'/_vars.inc.php' );


/**
 * DB class
 */
require_once( dirname(__FILE__).'/_db.class.php' );
/**
 * Database connection (connection opened here)
 *
 * @global DB $DB
 */
$DB = new DB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST, $db_aliases, $db_table_options );

require_once( dirname(__FILE__).'/_results.class.php' );


/**#@+
 * Load settings class
 */
require_once( dirname(__FILE__).'/_generalsettings.class.php' );
require_once( dirname(__FILE__).'/_usersettings.class.php' );
/**#@-*/
/**
 * Interface to general settings
 *
 * @global GeneralSettings $Settings
 */
$Settings = & new GeneralSettings();
/**
 * Interface to user settings
 *
 * @global UserSettings $UserSettings
 */
$UserSettings = & new UserSettings();


/**
 * Absolute Unix timestamp for server
 * @global int $servertimenow
 */
$servertimenow = time();
/**
 * Corrected Unix timestamp to match server timezone
 * @global int $localtimenow
 */
$localtimenow = $servertimenow + ($Settings->get('time_difference') * 3600);


/**
 * Includes:
 */
require_once dirname(__FILE__).'/_template.funcs.php';    // function to be called from templates
require_once dirname(__FILE__).'/'.$core_dirout.$lib_subdir.'_xmlrpc.php';
require_once dirname(__FILE__).'/'.$core_dirout.$lib_subdir.'_xmlrpcs.php';
require_once dirname(__FILE__).'/_blog.class.php';
require_once dirname(__FILE__).'/_itemlist.class.php';
require_once dirname(__FILE__).'/_itemcache.class.php';
require_once dirname(__FILE__).'/_commentlist.class.php';
require_once dirname(__FILE__).'/_archivelist.class.php';
require_once dirname(__FILE__).'/_dataobjectcache.class.php';
require_once dirname(__FILE__).'/_element.class.php';
require_once dirname(__FILE__).'/_usercache.class.php';
// Object caches init:
$GroupCache = & new DataObjectCache( 'Group', true, 'T_groups', 'grp_', 'grp_ID' );
$UserCache = & new UserCache();
$BlogCache = & new BlogCache();
$ItemCache = & new ItemCache();

require_once dirname(__FILE__).'/_calendar.class.php';
require_once dirname(__FILE__).'/_hitlog.funcs.php';     // referer logging
require_once dirname(__FILE__).'/_form.funcs.php';
require_once dirname(__FILE__).'/_form.class.php';
require_once dirname(__FILE__).'/'.$core_dirout.$lib_subdir.'_swfcharts.php';

/**
 * Plug-ins init:
 */
require_once dirname(__FILE__).'/_plugins.class.php';
$Plugins = & new Plugins();


/**
 * Output buffering?
 */
if( $use_obhandler )
{ // register output buffer handler
	ob_start( 'obhandler' );
}


/**
 * Locale selection:
 */
$Debuglog->add( 'default_locale from conf: '.$default_locale, 'locale' );

locale_overwritefromDB();
$Debuglog->add( 'default_locale from DB: '.$default_locale, 'locale' );

$default_locale = locale_from_httpaccept(); // set default locale by autodetect
$Debuglog->add( 'default_locale from HTTP_ACCEPT: '.$default_locale, 'locale' );

if( ($locale_from_get = param( 'locale', 'string', NULL, true ))
		&& $locale_from_get != $default_locale
		&& isset( $locales[$locale_from_get] ) )
{
	$default_locale = $locale_from_get;
	$Debuglog->add( 'Overriding locale from REQUEST: '.$default_locale, 'locale' );
}


// TODO: check valid user, then activate only once the locale!

/**
 * Activate default locale:
 */
locale_activate( $default_locale );

/**
 * Login procedure:
 */
if( !isset($login_required) )
{
	$login_required = false;
}

if( $error = veriflog( $login_required ) )
{ // Login failed:
	require( dirname(__FILE__).'/'.$core_dirout.$htsrv_subdir.'login.php' );
}


// Update the active session for the current user:
online_user_update();


/**
 * User locale selection:
 */
if( is_logged_in() && $current_User->get('locale') != $current_locale
		&& !$locale_from_get )
{ // change locale to users preference
	locale_activate( $current_User->get('locale') );
	if( $current_locale == $current_User->get('locale') )
	{
		$default_locale = $current_locale;
		$Debuglog->add( 'default_locale from user profile: '.$default_locale, 'locale' );
	}
	else
	{
		$Debuglog->add( 'locale from user profile could not be activated: '.$current_User->get('locale'), 'locale' );
	}
}


/**
 * Hit type - determines if hit will be logged and/or increase view count for Items
 *
 * Possible values are:
 * - 'badchar' : referer contains junk or spam : no logging, no counting
 * - 'reload' : page is reloaded : no logging, no counting
 * - 'robot' : page is loaded by a robot: log but don't count view
 * - 'blacklist' (should be 'hidden') : we want to hide the referer, but we count the hit : log & count
 * - 'rss' : RSS feed : log & count
 * - 'invalid' : normal without a referer : log & count
 * - 'search' : referer is a search engine : log & count
 * - 'no' : normal with referer (default) : log & count
 * - 'preview' : preview mode : no logging, no counting
 * - 'already_logged' : this hit has already been logged : no relogging, no recounting
 *
 * @global string $hit_type
 */
$hit_type = filter_hit();


/**
 * Load the icons - we need the users locale set there ({@link T_()})
 */
require_once( $conf_path.'_icons.php' );


/**
 * Load hacks file if it exists
 */
@include_once( dirname(__FILE__) . '/../conf/hacks.php' );

/*
 * $Log$
 * Revision 1.17  2005/02/15 22:05:08  blueyed
 * Started moving obsolete functions to _obsolete092.php..
 *
 * Revision 1.16  2005/02/15 20:05:51  fplanque
 * no message
 *
 * Revision 1.15  2005/02/10 23:00:31  blueyed
 * small enhancements
 *
 * Revision 1.14  2005/02/08 23:57:20  blueyed
 * moved Debugmessage, ..
 *
 * Revision 1.13  2005/01/03 06:21:35  blueyed
 * moved declaration of $map_iconsfiles, $map_iconsizes so that they can make use of T_()
 *
 * Revision 1.12  2004/12/27 18:37:58  fplanque
 * changed class inheritence
 *
 * Revision 1.10  2004/12/21 21:18:38  fplanque
 * Finished handling of assigning posts/items to users
 *
 * Revision 1.9  2004/12/20 19:49:24  fplanque
 * cleanup & factoring
 *
 * Revision 1.8  2004/12/17 20:38:52  fplanque
 * started extending item/post capabilities (extra status, type)
 *
 * Revision 1.7  2004/11/09 00:25:12  blueyed
 * minor translation changes (+MySQL spelling :/)
 *
 * Revision 1.6  2004/10/28 11:11:09  fplanque
 * MySQL table options handling
 *
 * Revision 1.5  2004/10/21 00:14:44  blueyed
 * moved
 *
 * Revision 1.4  2004/10/17 20:18:37  fplanque
 * minor changes
 *
 * Revision 1.3  2004/10/16 01:31:22  blueyed
 * documentation changes
 *
 * Revision 1.2  2004/10/14 18:31:25  blueyed
 * granting copyright
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.73  2004/10/12 18:48:34  fplanque
 * Edited code documentation.
 *
 */
?>