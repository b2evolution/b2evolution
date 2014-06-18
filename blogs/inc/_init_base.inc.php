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
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 * @author mfollett: Matt FOLLETT
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id: _init_base.inc.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * @global boolean Are we running on Command Line Interface instead of a web request?
 */
$is_cli = empty($_SERVER['SERVER_SOFTWARE']) ? true : false;
$is_web = ! $is_cli;
// echo ($is_cli ? 'cli' : 'web' );


if( $maintenance_mode )
{ // Maintenance mode with a conf switch
	header('HTTP/1.0 503 Service Unavailable');
	echo '<h1>503 Service Unavailable</h1>';
	die( 'The site is temporarily down for maintenance.' );
}
elseif( file_exists( $conf_path.'imaintenance.html' ) )
{ // Maintenance mode with a file - "imaintenance.html" with an "i" prevents access to the site but NOT to install
	header('HTTP/1.0 503 Service Unavailable');
	readfile( $conf_path.'imaintenance.html' );
	die();
}


/**
 * Absolute Unix timestamp for server
 * @global int $servertimenow
 */
$servertimenow = time();


/**
 * Security check for older PHP versions
 * Contributed by counterpoint / MAMBO team
 */
{
	$protects = array( '_REQUEST', '_GET', '_POST', '_COOKIE', '_FILES', '_SERVER', '_ENV', 'GLOBALS', '_SESSION' );
	foreach( $protects as $protect )
	{
		if(  in_array( $protect, array_keys($_REQUEST) )
			|| in_array( $protect, array_keys($_GET) )
			|| in_array( $protect, array_keys($_POST) )
			|| in_array( $protect, array_keys($_COOKIE) )
			|| in_array( $protect, array_keys($_FILES) ) )
		{
			require_once $inc_path.'/_core/_misc.funcs.php';
			bad_request_die( 'Unacceptable params.' );
		}
	}
}

/**
 * Request/Transaction name, used for performance monitoring.
 */
$request_transaction_name = '';


if( !$config_is_done )
{ // base config is not done!
	$error_message = 'Base configuration is not done! (see /conf/_basic_config.php)';
}
elseif( !isset( $locales[$default_locale] ) )
{
	$error_message = 'The default locale '.var_export( $default_locale, true ).' does not exist! (see /conf/_locales.php)';
}
if( isset( $error_message ) )
{ // error & exit
	require dirname(__FILE__).'/../skins_adm/conf_error.main.php';
}


/**
 * Class loader.
 */
require_once $inc_path.'_core/_class'.floor(PHP_VERSION).'.funcs.php';


/**
 * Locale related functions
 */
require_once $inc_path.'locales/_locale.funcs.php';


/**
 * Miscellaneous functions
 */
require_once $inc_path.'_core/_misc.funcs.php';


/**
 * Debug message log for debugging only (initialized here).
 *
 * @global Log|Log_noop $Debuglog
 */
if( $debug )
{
	load_class( '_core/model/_log.class.php', 'Log' );
	$Debuglog = new Log();
}
else
{
	load_class( '_core/model/_log.class.php', 'Log_noop' );
	$Debuglog = new Log_noop();
}


/**
 * Info & error message log for end user (initialized here)
 * @global Log $Messages
 */
load_class( '_core/model/_messages.class.php', 'Messages' );
$Messages = new Messages();


/*
 * Start timer:
 */
load_class( '_core/model/_timer.class.php', 'Timer' );
$Timer = new Timer('total');
$Timer->resume( '_init_base' );
$Timer->resume( '_MAIN.inc' );



// the weekdays and the months..
$weekday[0] = NT_('Sunday');
$weekday[1] = NT_('Monday');
$weekday[2] = NT_('Tuesday');
$weekday[3] = NT_('Wednesday');
$weekday[4] = NT_('Thursday');
$weekday[5] = NT_('Friday');
$weekday[6] = NT_('Saturday');

// the weekdays short form (typically 3 letters)
// TRANS: abbrev. for Sunday
$weekday_abbrev[0] = NT_('Sun');
// TRANS: abbrev. for Monday
$weekday_abbrev[1] = NT_('Mon');
// TRANS: abbrev. for Tuesday
$weekday_abbrev[2] = NT_('Tue');
// TRANS: abbrev. for Wednesday
$weekday_abbrev[3] = NT_('Wed');
// TRANS: abbrev. for Thursday
$weekday_abbrev[4] = NT_('Thu');
// TRANS: abbrev. for Friday
$weekday_abbrev[5] = NT_('Fri');
// TRANS: abbrev. for Saturday
$weekday_abbrev[6] = NT_('Sat');

// the weekdays even shorter form (typically 1 letter)
// TRANS: abbrev. for Sunday
$weekday_letter[0] = NT_(' S ');
// TRANS: abbrev. for Monday
$weekday_letter[1] = NT_(' M ');
// TRANS: abbrev. for Tuesday
$weekday_letter[2] = NT_(' T ');
// TRANS: abbrev. for Wednesday
$weekday_letter[3] = NT_(' W ');
// TRANS: abbrev. for Thursday
$weekday_letter[4] = NT_(' T  ');
// TRANS: abbrev. for Friday
$weekday_letter[5] = NT_(' F ');
// TRANS: abbrev. for Saturday
$weekday_letter[6] = NT_(' S  ');

// the months
$month['00'] = '\?\?';	// This can happen when importing junk dates from WordPress
$month['01'] = NT_('January');
$month['02'] = NT_('February');
$month['03'] = NT_('March');
$month['04'] = NT_('April');
// TRANS: space at the end only to differentiate from short form. You don't need to keep it in the translation.
$month['05'] = NT_('May ');
$month['06'] = NT_('June');
$month['07'] = NT_('July');
$month['08'] = NT_('August');
$month['09'] = NT_('September');
$month['10'] = NT_('October');
$month['11'] = NT_('November');
$month['12'] = NT_('December');

// the months short form (typically 3 letters)
// TRANS: abbrev. for January
$month_abbrev['01'] = NT_('Jan');
// TRANS: abbrev. for February
$month_abbrev['02'] = NT_('Feb');
// TRANS: abbrev. for March
$month_abbrev['03'] = NT_('Mar');
// TRANS: abbrev. for April
$month_abbrev['04'] = NT_('Apr');
// TRANS: abbrev. for May
$month_abbrev['05'] = NT_('May');
// TRANS: abbrev. for June
$month_abbrev['06'] = NT_('Jun');
// TRANS: abbrev. for July
$month_abbrev['07'] = NT_('Jul');
// TRANS: abbrev. for August
$month_abbrev['08'] = NT_('Aug');
// TRANS: abbrev. for September
$month_abbrev['09'] = NT_('Sep');
// TRANS: abbrev. for October
$month_abbrev['10'] = NT_('Oct');
// TRANS: abbrev. for November
$month_abbrev['11'] = NT_('Nov');
// TRANS: abbrev. for December
$month_abbrev['12'] = NT_('Dec');


/**
 * Load modules.
 *
 * This initializes table name aliases and is required before trying to connect to the DB.
 */
load_class( '_core/model/_module.class.php', 'Module' );
foreach( $modules as $module )
{
	require_once $inc_path.$module.'/_'.$module.'.init.php';
}

$Timer->pause( '_init_base' );

?>