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

/**
 * Handle fatal error in order to display info message when debug is OFF
 */
// set_error_handler( 'evo_error_handler' );
// fp> I disabled the above because it kills display of warnings like the following
// fp> see function evo_error_handler() for more comments
// echo $fddfdjshfjkdfsd;

// NOTE: it might be faster (though more bandwidth intensive) to spit cached pages (CachePageContent event) than to look into blocking the request (SessionLoaded event).
$Plugins->trigger_event( 'SessionLoaded' );


// Trigger a page content caching plugin. This would either return the cached content here or start output buffering
/* fp> if you still need this, please let me know which plugin uses that.

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


// TODO: we need an event hook here for the transport_optimizer_plugin, which must get called,
//       AFTER another plugin might have started an output buffer for caching already.
//       Plugin priority is no option, because CachePageContent is a trigger_event_first_true event, for obvious reasons.
//       Name?
//       This must not be exactly here, but before any output.
*/


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
if( is_logged_in() )
{
	$Debuglog->add( 'Login: locale from user profile: '.$current_User->get('locale'), 'locale' );
}
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
		$Debuglog->add( 'Login: changing default_locale to: '.$default_locale, 'locale' );
	}
	else
	{
		$Debuglog->add( 'Login: locale from user profile could not be activated: '.$current_User->get('locale'), 'locale' );
	}
	// Init charset based on the selected locale
	if( init_charsets( $current_charset ) )
	{ // Charset was changed reload current User from db to make sure that all of it's data is in the current charset
		$UserCache = & get_UserCache();
		$UserCache->clear();
		$current_User = & $UserCache->get_by_ID( $current_User->ID );
	}
}


$Timer->pause( '_init_session' );
?>