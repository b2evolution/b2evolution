<?php
// This is for www only. You don't want to include this when runnignin CLI (command line) mode
/**
 * This file initializes everything BUT the blog!
 *
 * It is useful when you want to do very customized templates!
 * It is also called by more complete initializers.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @global integer ID of featured post that is being displayed (will become an array() in the future) -- needed so we can filter it out of normal post flow
 */
$featured_displayed_item_ID = NULL;

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
$ReqHost = ( (isset($_SERVER['HTTPS']) && ( $_SERVER['HTTPS'] != 'off' ) ) ?'https://':'http://').$_SERVER['HTTP_HOST'];


$ReqURL = $ReqHost.$ReqURI;


$Debuglog->add( 'vars: $ReqHost: '.$ReqHost, 'request' );
$Debuglog->add( 'vars: $ReqURI: '.$ReqURI, 'request' );
$Debuglog->add( 'vars: $ReqPath: '.$ReqPath, 'request' );


// on which page are we ?
/* old:
$pagenow = explode( '/', $_SERVER['PHP_SELF'] );
$pagenow = trim( $pagenow[(count($pagenow) - 1)] );
$pagenow = explode( '?', $pagenow );
$pagenow = $pagenow[0];
*/
// find precisely the first occurrence of something.php in PHP_SELF, extract that and ignore any extra path.
if( ! preg_match( '#/([A-Za-z0-9_\-]+\.php[0-9]?)#', $_SERVER['PHP_SELF'], $matches ))
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



$Timer->resume( '_init_hit' );

// Init charset handling:
init_charsets( $current_charset );


// fp> TODO: the following was in _vars.inc -- temporaily here, b2evolution stuff needs to move out of evoCORE.

// dummy var for backward compatibility with versions < 2.4.1 -- prevents "Undefined variable"
$credit_links = array();

$francois_links = array( 'fr' => array( 'http://fplanque.net/', array( array( 78, 'Fran&ccedil;ois'),  array( 100, 'Francois') ) ),
													'' => array( 'http://fplanque.com/', array( array( 78, 'Fran&ccedil;ois'),  array( 100, 'Francois') ) )
												);

$fplanque_links = array( 'fr' => array( 'http://fplanque.net/', array( array( 78, 'Fran&ccedil;ois Planque'),  array( 100, 'Francois Planque') ) ),
													'' => array( 'http://fplanque.com/', array( array( 78, 'Fran&ccedil;ois Planque'),  array( 100, 'Francois Planque') ) )
												);

$skin_links = array( '' => array( 'http://skinfaktory.com/', array( array( 15, 'b2evo skin'), array( 20, 'b2evo skins'), array( 35, 'b2evolution skin'), array( 40, 'b2evolution skins'), array( 55, 'Blog skin'), array( 60, 'Blog skins'), array( 75, 'Blog theme'),array( 80, 'Blog themes'), array( 95, 'Blog template'), array( 100, 'Blog templates') ) ),
												);

$skinfaktory_links = array( '' => array( array( 73, 'http://evofactory.com/', array( array( 61, 'Evo Factory'), array( 68, 'EvoFactory'), array( 73, 'Evofactory') ) ),
														             array( 100, 'http://skinfaktory.com/', array( array( 92, 'Skin Faktory'), array( 97, 'SkinFaktory'), array( 99, 'Skin Factory'), array( 100, 'SkinFactory') ) ),
																				)
												);

$Timer->pause( '_init_hit' );

/*
 * $Log$
 * Revision 1.6  2010/07/26 06:52:15  efy-asimo
 * MFB v-4-0
 *
 * Revision 1.5  2010/05/13 18:52:04  blueyed
 * doc
 *
 * Revision 1.4  2010/04/19 17:00:13  blueyed
 * Make locale_from_get work again.
 *
 * Revision 1.3  2010/02/08 17:51:25  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.2  2009/12/22 08:45:44  fplanque
 * fix install
 *
 * Revision 1.1  2009/12/06 05:20:36  fplanque
 * Violent refactoring for _main.inc.php
 * Sorry for potential side effects.
 * This needed to be done badly -- for clarity!
 *
 */
?>
