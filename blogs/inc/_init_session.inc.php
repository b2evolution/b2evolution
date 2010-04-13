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


$Timer->resume( '_init_session' );

// fp> This needs to move to a better place
// Check base domain for admin
load_funcs( '_core/_url.funcs.php' );
if( !empty($is_admin_page) )
{	// Make sure we are calling the right page (on the right domain) to make sure that session cookie goes through:
	if( ! is_same_url( $ReqHost.$ReqPath, $admin_url) )
	{	// The requested URL does not look like it's under the admin URL...
		header('X-Evo-Redirect: Redirect to canonical $admin_url'); // Add debug header to find the cause for infinite redirects better!
		header_redirect( $admin_url, 302 );
	}
}

/**
 * The Session class.
 */
load_class( 'sessions/model/_session.class.php', 'Session' );
/**
 * The Session object.
 * It has to be instantiated before the "SessionLoaded" hook.
 * @global Session
 * @todo dh> This needs the same "SET NAMES" MySQL-setup as with Session::dbsave() - see the "TODO" with unserialize() in Session::Session()
 * @todo dh> makes no sense in CLI mode (no cookie); Add isset() checks to calls on the $Session object, e.g. below?
 *       fp> We might want to use a special session for CLI. And for cron jobs through http as well.
 */
$Session = new Session(); // If this can't pull a session from the DB it will always INSERT a new one!

/**
 * Handle saving the HIT and updating the SESSION at the end of the page
 */
register_shutdown_function( 'shutdown' );


// NOTE: it might be faster (though more bandwidth intensive) to spit cached pages (CachePageContent event) than to look into blocking the request (SessionLoaded event).
$Plugins->trigger_event( 'SessionLoaded' );


// Trigger a page content caching plugin. This would either return the cached content here or start output buffering
if( empty($generating_static) )
{
	if( $Session->get( 'core.no_CachePageContent' ) )
	{ // The event is disabled for this request:
		$Session->delete('core.no_CachePageContent');
		$Debuglog->add( 'Login: Skipping CachePageContent event, because of core.no_CachePageContent setting.', 'plugins' );
	}
	elseif( ( $get_return = $Plugins->trigger_event_first_true( 'CachePageContent' ) ) // Plugin responded to the event
			&& ( isset($get_return['data']) ) ) // cached content returned
	{
		echo $get_return['data'];
		// Note: we should not use debug_info() here, because the plugin has probably sent a Content-Length header.
		exit(0);
	}
}


// TODO: we need an event hook here for the transport_optimizer_plugin, which must get called, AFTER another plugin might have started an output buffer for caching already.
//       Plugin priority is no option, because CachePageContent is a trigger_event_first_true event, for obvious reasons.
//       Name?
//       This must not be exactly here, but before any output.


// The following is needed during login, not sure that's right :/
load_class( 'users/model/_usersettings.class.php', 'UserSettings' );
/**
 * Interface to user settings
 *
 * @global UserSettings $UserSettings
 */
$UserSettings = new UserSettings();


// LOGIN:
// fp> TODO: even if the session already has a user, we still need to get in there... that should be changed.
$Timer->pause( '_init_session' );
require dirname(__FILE__).'/_init_login.inc.php';
$Timer->resume( '_init_session' );



/*
 * User locale selection. Only override it if not set from REQUEST.
 */
if( is_logged_in() && $current_User->get('locale') != $current_locale && ! $locale_from_get )
{ // change locale to users preference
	/*
	 * User locale selection:
	 * TODO: this should get done before instantiating $current_User, because we already use T_() there...
	 */
	locale_activate( $current_User->get('locale') );
	if( $current_locale == $current_User->get('locale') )
	{
		$default_locale = $current_locale;
		$Debuglog->add( 'Login: default_locale from user profile: '.$default_locale, 'locale' );
	}
	else
	{
		$Debuglog->add( 'Login: locale from user profile could not be activated: '.$current_User->get('locale'), 'locale' );
	}
}


$Timer->pause( '_init_session' );


/*
 * $Log$
 * Revision 1.5  2010/04/13 22:23:38  blueyed
 * Use header_redirect for canonical admin redirect. Also add extra header to aid debugging.
 *
 * Revision 1.4  2010/02/08 17:51:25  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.3  2010/01/30 18:55:15  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.2  2009/12/06 05:34:31  fplanque
 * Violent refactoring for _main.inc.php
 * Sorry for potential side effects.
 * This needed to be done badly -- for clarity!
 *
 * Revision 1.1  2009/12/06 05:20:36  fplanque
 * Violent refactoring for _main.inc.php
 * Sorry for potential side effects.
 * This needed to be done badly -- for clarity!
 *
 */
?>
