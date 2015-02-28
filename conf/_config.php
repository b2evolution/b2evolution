<?php
/**
 * This is b2evolution's main config file, which just includes all the other
 * config files.
 *
 * This file should not be edited. You should edit the sub files instead.
 *
 * See {@link _basic_config.php} for the basic settings.
 *
 * @package conf
 */

if( defined('EVO_CONFIG_LOADED') )
{
	return;
}

// HARD MAINTENANCE !
if( file_exists(dirname(__FILE__).'/maintenance.html') )
{ // Stop execution as soon as possible. This is useful while uploading new app files via FTP.
	header('HTTP/1.0 503 Service Unavailable');
	readfile(dirname(__FILE__).'/maintenance.html');
	die();
}
elseif( file_exists(dirname(__FILE__).'/umaintenance.html') )
{ // Maintenance mode with a file - "umaintenance.html" with an "u" prevents access to the site but NOT to upgrade
	$get_ctrl = isset( $_GET['ctrl'] ) ? $_GET['ctrl'] : ( isset( $_POST['ctrl'] ) ? $_POST['ctrl'] : '' );
	// Check if the request is to the upgrade controller or it is an upgrade action request from the upgrade ctrl
	$is_upgrade = ( ( $get_ctrl == 'upgrade' ) || ( ( substr( $_SERVER['PHP_SELF'], -17 ) == 'install/index.php' )
				&& isset( $_GET['action'] ) && ( $_GET['action'] == 'svn_upgrade' || $_GET['action'] == 'auto_upgrade' ) ) ); // The request action is 'svn_upgrade' or 'auto_upgrade'
	if( ! $is_upgrade )
	{ // NOT an upgrade
		header('HTTP/1.0 503 Service Unavailable');
		readfile(dirname(__FILE__).'/umaintenance.html');
		die();
	}
}


/**
 * This makes sure the config does not get loaded twice in Windows
 * (when the /conf file is in a path containing uppercase letters as in /Blog/conf).
 */
define( 'EVO_CONFIG_LOADED', true );

// basic settings
if( file_exists(dirname(__FILE__).'/_basic_config.php') )
{	// Use configured base config:
	require_once  dirname(__FILE__).'/_basic_config.php';
}
else
{	// Use default template:
	require_once  dirname(__FILE__).'/_basic_config.template.php';
}

// DEPRECATED -- You can now have a _basic_config.php file that will not be overwritten by new releases
if( file_exists(dirname(__FILE__).'/_config_TEST.php') )
{ // Put testing conf in there (For testing, you can also set $install_password here):
	include_once dirname(__FILE__).'/_config_TEST.php';   	// FOR TESTING / DEVELOPMENT OVERRIDES
}

require_once  dirname(__FILE__).'/_advanced.php';       	// advanced settings
require_once  dirname(__FILE__).'/_locales.php';        	// locale settings
require_once  dirname(__FILE__).'/_formatting.php';     	// formatting settings
require_once  dirname(__FILE__).'/_admin.php';          	// admin settings
require_once  dirname(__FILE__).'/_stats.php';          	// stats/hitlogging settings
require_once  dirname(__FILE__).'/_application.php';    	// application settings
if( file_exists(dirname(__FILE__).'/_overrides_TEST.php') )
{ // Override for testing in there:
	include_once dirname(__FILE__).'/_overrides_TEST.php';	// FOR TESTING / DEVELOPMENT OVERRIDES
}

// Handle debug cookie:
if( $debug == 'pwd' )
{	// Debug *can* be enabled/disabled by cookie:

	// Disabled until we find a reason to enable:
	$debug = 0;

	if( !empty( $debug_pwd ) )
	{	// We have configured a password that could enable debug mode:
		if( isset( $_GET['debug'] ) )
		{	// We have submitted a ?debug=password
			if( $_GET['debug'] == $debug_pwd )
			{	// Password matches
				$debug = 1;
				if( version_compare( phpversion(), '5.2', '>=' ) )
				{ // Use HTTP-only setting since PHP 5.2.0
					setcookie( 'debug', $debug_pwd, 0, $cookie_path, $cookie_domain, false, true );
				}
				else
				{ // PHP < 5.2 doesn't support HTTP-only
					setcookie( 'debug', $debug_pwd, 0, $cookie_path, $cookie_domain );
				}
			}
			else
			{	// Password doesn't match: turn off debug mode:
				if( version_compare( phpversion(), '5.2', '>=' ) )
				{ // Use HTTP-only setting since PHP 5.2.0
					setcookie( 'debug', '', $cookie_expired, $cookie_path, $cookie_domain, false, true );
				}
				else
				{ // PHP < 5.2 doesn't support HTTP-only
					setcookie( 'debug', '', $cookie_expired, $cookie_path, $cookie_domain );
				}
			}
		}
		elseif( !empty( $_COOKIE['debug'] ) && $_COOKIE['debug'] == $debug_pwd )
		{	// We have a cookie with the correct debug password:
			$debug = 1;
		}
	}
}

// Handle debug jslog cookie:
if( $debug_jslog == 'pwd' )
{	// Debug *can* be enabled/disabled by cookie:

	// Disabled until we find a reason to enable:
	$debug_jslog = 0;

	if( !empty( $debug_pwd ) )
	{	// We have configured a password that could enable debug mode:
		if( isset( $_GET['jslog'] ) )
		{	// We have submitted a ?jslog=password
			if( $_GET['jslog'] == $debug_pwd )
			{	// Password matches
				$debug_jslog = 1;
				setcookie( 'jslog', $debug_pwd, 0, '/' );
			}
			else
			{	// Password doesn't match: turn off debug mode:
				setcookie( 'jslog', '', $cookie_expired, '/' );
				if( !empty( $_COOKIE['jslog_style'] ) )
				{	// Change the saved styles to hide jslog
					$_COOKIE['jslog_style'] = str_replace( 'display: block', 'display: none', $_COOKIE['jslog_style'] );
					setcookie( 'jslog_style', $_COOKIE['jslog_style'], 0, '/' );
				}
			}
		}
		elseif( !empty( $_COOKIE['jslog'] ) && $_COOKIE['jslog'] == $debug_pwd )
		{	// We have a cookie with the correct debug password:
			$debug_jslog = 1;
			if( !empty( $_COOKIE['jslog_style'] ) )
			{	// Change the saved styles to show jslog
				$_COOKIE['jslog_style'] = str_replace( 'display: none', 'display: block', $_COOKIE['jslog_style'] );
				setcookie( 'jslog_style', $_COOKIE['jslog_style'], 0, '/' );
			}
		}
	}
}


// To help debugging severe errors, you'll probably want PHP to display the errors on screen.
if( $debug > 0 || $display_errors_on_production )
{ // We are debugging or w want to display errors on screen production anyways:
	ini_set( 'display_errors', 'On' );
}
else
{ // Do not display errors on screen:
	ini_set( 'display_errors', 'Off' );
}

// Check compatibility. Server PHP version can't be lower then the application required PHP version.
$php_version = phpversion();
if( version_compare( $php_version, $required_php_version[ 'application' ], '<' ) )
{
	$compat = sprintf( 'You cannot use %1$s %2$s on this server because it requires PHP version %3$s or higher. You are running version %4$s.',
				$app_name, $app_version, $required_php_version[ 'application' ], $php_version );

	die('<h1>Insufficient Requirements</h1><p>'.$compat.'</p>');
}

// Check timezone setting:
$date_timezone = ini_get( "date.timezone" );
if( version_compare( $php_version, '5.1', '>=' ) && ( !empty( $date_default_timezone ) || empty( $date_timezone ) ) )
{ // Set default timezone in php versions >= 5.1 but only if $date_default_timezone is set or php.ini 'date.timezone' setting was not set
	date_default_timezone_set( empty( $date_default_timezone ) ? 'Europe/Paris' : $date_default_timezone );
}

// STUFF THAT SHOULD BE INITIALIZED (to avoid param injection on badly configured PHP)
$use_db = true;
$use_session = true;

?>