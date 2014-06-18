<?php
/**
 * This is for www only. You don't want to include this when runnignin CLI (command line) mode
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @package evocore
 *
 * @version $Id: _init_hit.inc.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


$Timer->resume( '_init_hit' );

/**
 * Do we want robots to index this page? -- Will be use to produce meta robots tag
 * @global boolean or NULL to ignore
 */
$robots_index = NULL;

/**
 * Do we want robots to follow links on this page? -- Will be use to produce meta robots tag
 * @global boolean or NULL to ignore
 */
$robots_follow = NULL;

$content_type_header = NULL;

/**
 * Default 200 = success
 */
$http_response_code = 200;

/**
 * @global array IDs of featured posts that are being displayed -- needed so we can filter it out of normal post flow
 */
$featured_displayed_item_IDs = array();

// Initialize some variables for template functions
$required_js = array();
$required_css = array();
$headlines = array();

// ############ Get ReqPath & ReqURI ##############
list($ReqPath,$ReqURI) = get_ReqURI();

/**
 * Full requested Host (including protocol).
 *
 * {@internal Note: on IIS you can receive 'off' in the HTTPS field!! :[ }}
 *
 * @global string
 */
$ReqHost = '';
if( !empty($_SERVER['HTTP_HOST']) )
{
	$ReqHost = ( (isset($_SERVER['HTTPS']) && ( $_SERVER['HTTPS'] != 'off' ) ) ?'https://':'http://').$_SERVER['HTTP_HOST'];
}


$ReqURL = $ReqHost.$ReqURI;


$Debuglog->add( 'vars: $ReqHost: '.$ReqHost, 'request' );
$Debuglog->add( 'vars: $ReqURI: '.$ReqURI, 'request' );
$Debuglog->add( 'vars: $ReqPath: '.$ReqPath, 'request' );

/**
 * Same domain htsrv url.
 *
 * @global string
 */
$samedomain_htsrv_url = get_samedomain_htsrv_url();

/**
 * Secure htsrv url.
 *
 * @global string
 */
$secure_htsrv_url = get_secure_htsrv_url();

// on which page are we ?
/* old:
$pagenow = explode( '/', $_SERVER['PHP_SELF'] );
$pagenow = trim( $pagenow[(count($pagenow) - 1)] );
$pagenow = explode( '?', $pagenow );
$pagenow = $pagenow[0];
*/
// find precisely the first occurrence of something.php in PHP_SELF, extract that and ignore any extra path.
if( ! preg_match( '#/([A-Za-z0-9_\-.]+\.php[0-9]?)#i', $_SERVER['PHP_SELF'], $matches ) &&
	  ! preg_match( '#/([A-Za-z0-9_\-.]+\.php[0-9]?)#i', $ReqURI, $matches ) )
{
	debug_die('Can\'t identify current .php script name in PHP_SELF.');
}
$pagenow = $matches[1];
//pre_dump( '', $_SERVER['PHP_SELF'], $pagenow );


/**
 * Number of view counts increased on this page
 * @var integer
 */
$view_counts_on_this_page = 0;


/**
 * Locale selection:
 * We need to do this as early as possible in order to set DB connection charset below
 * fp> that does not explain why it needs to be here!! Why do we need to set the Db charset HERE? BEFORE WHAT?
 *
 * sam2kb> ideally we should set the right DB charset at the time when we connect to the database. The reason is until we do it all data pulled out from DB is in wrong encoding. I put the code here because it depends on _param.funcs, so if move the _param.funcs higher we can also move this code right under _connect_db
 * See also http://forums.b2evolution.net//viewtopic.php?p=95100
 *
 */
$Debuglog->add( 'Login: default_locale from conf: '.$default_locale, 'locale' );

locale_overwritefromDB();
$Debuglog->add( 'Login: default_locale from DB: '.$default_locale, 'locale' );

$default_locale = locale_from_httpaccept(); // set default locale by autodetect
$Debuglog->add( 'Login: default_locale from HTTP_ACCEPT: '.$default_locale, 'locale' );

load_funcs('_core/_param.funcs.php');

// $locale_from_get: USE CASE: allow overriding the locale via GET param &locale=, e.g. for tests.
if( ($locale_from_get = param( 'locale', 'string', NULL, true )) )
{
	$locale_from_get = str_replace('_', '-', $locale_from_get);
	if( $locale_from_get != $default_locale )
	{
		if( isset( $locales[$locale_from_get] ) )
		{
			$default_locale = $locale_from_get;
			$Debuglog->add('Overriding locale from REQUEST: '.$default_locale, 'locale');
		}
		else
		{
			$Debuglog->add('$locale_from_get ('.$locale_from_get.') is not set. Available locales: '.implode(', ', array_keys($locales)), 'locale');
			$locale_from_get = false;
		}
	}
	else
	{
		$Debuglog->add('$locale_from_get == $default_locale ('.$locale_from_get.').', 'locale');
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


/**
 * The Hit class
 */
load_class( 'sessions/model/_hit.class.php', 'Hit' );
/**
 * @global Hit The Hit object
 */
$Hit = new Hit(); // This may INSERT a basedomain and a useragent but NOT the HIT itself!

$Timer->pause( '_init_hit' );



// Init user SESSION:
if( $use_session )
{
	require dirname(__FILE__).'/_init_session.inc.php';
}

if( is_logged_in() )
{
	$timeout_online = $Settings->get( 'timeout_online' );
	if( empty( $current_User->lastseen_ts ) || ( $current_User->lastseen_ts < date2mysql( $localtimenow - $timeout_online ) ) )
	{
		$current_User->set( 'lastseen_ts', date2mysql( $localtimenow ) );
		$current_User->dbupdate();
	}
}

$Timer->resume( '_init_hit' );

// Init charset handling:
init_charsets( $current_charset );

$Timer->pause( '_init_hit' );

?>