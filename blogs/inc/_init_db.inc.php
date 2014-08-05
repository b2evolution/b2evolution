<?php
// This is separated from init_base inc case we want to do a apage that does not connect to the database at all.
// However, once we connect, we load everything we normally expect to be available from the DB.
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
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 * @author mfollett: Matt FOLLETT
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id: _init_db.inc.php 7212 2014-08-05 04:28:40Z yura $
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

$Timer->resume( '_init_db' );

/**
 * Load DB class
 */
require_once dirname(__FILE__).'/_core/model/db/_db.class.php';

/**
 * Database connection (connection opened here)
 *
 * @global DB $DB
 */
$DB = new DB( $db_config );


// ---------------- Initialize locale: ---- START ---------------- //

/**
 * Locale selection:
 * We need to do this as early as possible in order to set DB connection charset below
 * fp> that does not explain why it needs to be here!! Why do we need to set the Db charset HERE? BEFORE WHAT?
 *
 * sam2kb> ideally we should set the right DB charset at the time when we connect to the database. The reason is until we do it all data pulled out from DB is in wrong encoding. I put the code here because it depends on _param.funcs, so if move the _param.funcs higher we can also move this code right under _connect_db
 * See also http://forums.b2evolution.net//viewtopic.php?p=95100
 *
 * yura> We have done this suggestion because we had an issue with russian symbols from table T_settings,
 *       I.e. we should call $DB->set_connection_charset() before we build the object $Settings.
 *
 */
$Debuglog->add( 'Login: default_locale from conf: '.$default_locale, 'locale' );

// Don't stop a code execution when DB is not created yet
$db_halt_on_error = $DB->halt_on_error;
$db_show_errors = $DB->show_errors;
$DB->halt_on_error = false;
$DB->show_errors = false;

locale_overwritefromDB();
$Debuglog->add( 'Login: default_locale from DB: '.$default_locale, 'locale' );

// Restore original values for DB debug
$DB->halt_on_error = $db_halt_on_error;
$DB->show_errors = $db_show_errors;

if( empty( $default_locale ) )
{ // Set default locale by autodetect if it is not defined in DB yet
	$default_locale = locale_from_httpaccept();
	$Debuglog->add( 'Login: default_locale from HTTP_ACCEPT: '.$default_locale, 'locale' );
}

load_funcs( '_core/_param.funcs.php' );

// $locale_from_get: USE CASE: allow overriding the locale via GET param &locale=, e.g. for tests.
if( ( $locale_from_get = param( 'locale', 'string', NULL, true ) ) )
{
	$locale_from_get = str_replace( '_', '-', $locale_from_get );
	if( $locale_from_get != $default_locale )
	{
		if( isset( $locales[$locale_from_get] ) )
		{
			$default_locale = $locale_from_get;
			$Debuglog->add( 'Overriding locale from REQUEST: '.$default_locale, 'locale' );
		}
		else
		{
			$Debuglog->add( '$locale_from_get ('.$locale_from_get.') is not set. Available locales: '.implode( ', ', array_keys( $locales ) ), 'locale' );
			$locale_from_get = false;
		}
	}
	else
	{
		$Debuglog->add( '$locale_from_get == $default_locale ('.$locale_from_get.').', 'locale' );
	}

	if( $locale_from_get )
	{ // locale from GET being used. It should not get overridden below.
		$redir = 'no'; // do not redirect to canonical URL
	}
}

/**
 * Activate default locale:
 */
locale_activate( $default_locale );

// Set encoding for MySQL connection:
$DB->set_connection_charset( $current_charset );

// ---------------- Initialize locale: ---- END ------------------ //


/**
 * Load settings class
 */
load_class( 'settings/model/_generalsettings.class.php', 'GeneralSettings' );
/**
 * Interface to general settings
 *
 * Keep this below the creation of the {@link $DB DB object}, because it checks for the
 * correct db_version and catches "table does not exist" errors, providing a link to the
 * install script.
 *
 * @global GeneralSettings $Settings
 */
$Settings = new GeneralSettings();


$time_difference = $Settings->get('time_difference');

/**
 * Corrected Unix timestamp to match server timezone
 * @global int $localtimenow
 */
$localtimenow = $servertimenow + $time_difference;


/**
 * @global AbstractSettings
 */
$global_Cache = new AbstractSettings( 'T_global__cache', array( 'cach_name' ), 'cach_cache', 0 /* load all */ );


$Timer->pause( '_init_db' );

?>