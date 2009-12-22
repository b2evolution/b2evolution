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

	if( !empty($debug_pwd) )
	{	// We have configured a password that could enable debug mode:
		if( isset($_GET['debug']) )
		{	// We have submitted a ?debug=password
			if( $_GET['debug'] == $debug_pwd )
			{	// Password matches
				$debug = 1;
				setcookie( 'debug', $debug_pwd, 0, $cookie_path, $cookie_domain );
			}
			else
			{	// Password doesn't match: turn off debug mode:
				setcookie( 'debug', '', $cookie_expired, $cookie_path, $cookie_domain );
			}
		}
		elseif( !empty($_COOKIE['debug'])	&& $_COOKIE['debug'] == $debug_pwd )
		{	// We have a cookie with the correct debug password:
			$debug = 1;
		}
	}
}

// STUFF THAT SHOULD BE INITIALIZED (to avoid param injection on badly configured PHP)
$use_db = true;
$use_session = true;

/*
 * $Log$
 * Revision 1.62  2009/12/22 09:03:19  fplanque
 * lose debug cookie when closing browser
 *
 * Revision 1.61  2009/12/11 23:01:19  fplanque
 * Reverted debug pwd changes.
 * As said before, hashing a cookie does not improve security. You can still steal the cookie.
 * Also requiring 2 params make it more painful to use.
 * Add a different password for debug level 2 if you really need it, but I think it's fine if you cannot turn on level 2 through the URL.
 *
 * Revision 1.60  2009/12/10 20:46:02  blueyed
 * debug_pwd: extra param for the password, which allows setting debug=2, too. Also, hash the cookie value for some more security.
 *
 * Revision 1.59  2009/12/08 20:08:48  fplanque
 * oops
 *
 * Revision 1.58  2009/12/02 00:05:29  fplanque
 * Debug mode can be turned on & off through the URL with a password
 *
 * Revision 1.57  2009/11/20 23:56:41  fplanque
 * minor  + doc
 *
 * Revision 1.56  2009/10/18 00:22:12  fplanque
 * doc/maintenance mode
 *
 * Revision 1.55  2009/09/15 19:31:55  fplanque
 * Attempt to load classes & functions as late as possible, only when needed. Also not loading module specific stuff if a module is disabled (module granularity still needs to be improved)
 * PHP 4 compatible. Even better on PHP 5.
 * I may have broken a few things. Sorry. This is pretty hard to do in one swoop without any glitch.
 * Thanks for fixing or reporting if you spot issues.
 *
 * Revision 1.54  2009/07/02 15:43:55  fplanque
 * B2evolution no longer ships with _basic_config.php .
 * It ships with _basic_config.template.php instead.
 * That way, uploading a new release never overwrites the previous base config.
 * The installer now creates  _basic_config.php based on _basic_config.template.php + entered form values.
 *
 * Revision 1.53  2009/01/25 19:09:32  blueyed
 * phpdoc fixes
 *
 * Revision 1.52  2008/04/06 19:19:30  fplanque
 * Started moving some intelligence to the Modules.
 * 1) Moved menu structure out of the AdminUI class.
 * It is part of the app structure, not the UI. Up to this point at least.
 * Note: individual Admin skins can still override the whole menu.
 * 2) Moved DB schema to the modules. This will be reused outside
 * of install for integrity checks and backup.
 * 3) cleaned up config files
 *
 * Revision 1.51  2006/11/26 01:42:08  fplanque
 * doc
 *
 */
?>
