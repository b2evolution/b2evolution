<?php
/**
 * This is b2evolution's basic config file.
 *
 * You do NOT need to edit this file. In most situations, the installer will do it for you.
 * If however you are doing a MANUAL install, make sure you do NOT edit _basic_config.template.php !
 * You should edit _basic_config.php instead. If _basic_config.php doesn't exist yet, then
 * open _basic_config.template.php and SAVE it AS _basic_config.php.
 *
 * Reminder: every line starting with # or // is a comment, multiline comments are
 *           surrounded by '/*' and '* /' (without space).
 *
 * IMPORTANT: Take special care not to erase quotes (') around text parameters
 * and semicolums (;) at the end of the lines. Otherwise you'll get some
 * "unexpected T_STRING" parse errors!
 *
 * Contributors: you should override this file by creating a file named _config_TEST.php
 * (see end of this file).
 *
 * @package conf
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


// TODO: dh> this file was meant to be used for things where you only need the basic config..
// fp> also:
// - At least _admin.php should only be called when in the backoffice.
// - Also we should probably start by moving as many conf options to the backoffice as possible and see how much stuff is left in conf files
//    Note: some stuff does not make sense in the back-office (for example stuff that depends on the physical path where the files are installed)
// - In view of reorganization, please list (all or examples) of situations where only a subset of the conf should be loaded.


/**
 * Maintenance mode. Set this to 1 in order to temporarily disable access to the application.
 *
 * Note: it is still possible to access the install script during maintenance mode.
 */
$maintenance_mode = 0;


// Below is an alternative hardcore version of maintenance mode.
// This one will block the install script too.
// Remove /* and */ to activate.
/*
header('HTTP/1.0 503 Service Unavailable');
echo '<h1>503 Service Unavailable</h1>';
die( 'The site is temporarily down for maintenance. Please reload this page in a few minutes.' );
*/


/**
 * MySQL DB settings.
 * Fill in your database details (check carefully or nothing will work!)
 */
$db_config = array(
	'user'          => 'demouser',     // your MySQL username
	'password'      => 'demopass',     // ...and password
	'name'          => 'b2evolution',  // the name of the database
	'host'          => 'localhost',    // MySQL Server (typically 'localhost')
);


/**
 * the tables prefix (gets placed before each b2evo table name),
 * use this to have multiple installations in one DB.
 *
 * @global string $tableprefix
 */
$tableprefix = 'evo_';


/**
 * If you want to be able to reset your existing b2evolution tables and start anew,
 * set $allow_evodb_reset to 1 or 2.
 *
 * $allow_evodb_reset = 1; will give you an option to delete the DB in the install menu.
 * $allow_evodb_reset = 2; will allow you do auto-delete if you try to install again.
 * Use option 2 only on developer installs where there is no chance to lose important data,
 * not even important test data.
 *
 * NEVER LEAVE THIS SETTING ON ANYTHING ELSE THAN 0 (ZERO) ON A PRODUCTION SERVER.
 * IF THIS IS ON (1 or 2) AND YOU FORGET TO DELETE THE INSTALL FOLDER, ANYONE WOULD BE ABLE
 * TO ERASE YOUR B2EVOLUTION TABLES AND DATA BY A SINGLE CLICK!
 */
$allow_evodb_reset = 0;	// Set to 1 or 2 to enable. LEAVE this at 0 on production servers!


/**
 * If you are a developer and you are making repeated installs of b2evolution, you might want to
 * automatically set a very easy password for the admin.
 *
 * DO THIS ON DEVELOPMENT MACHINES ONLY! NEVER USE THIS SETTING ON A PRODUCTION SERVER!
 */
// $install_password = 'easy';


/**
 * If you are a developer and you are making repeated installs of b2evolution, you might want to
 * automatically force the install of the .htaccess file that matches the current version without
 * requiring an extra confirmation.
 *
 * DO THIS ON DEVELOPMENT MACHINES ONLY! MAY BE DANGEROUS ON A PRODUCTION SERVER!
 */
// $htaccess = 'force';


/**
 * $baseurl is where your blogs reside by default. CHECK THIS CAREFULLY or nothing will work.
 * It should be set to the URL where you can find the blog templates and/or the blog stub files,
 * that means index.php, blog1.php, blog2.php, etc. as well as admin.php.
 * Note: Blogs can be in subdirectories of the baseurl. However, no blog should be outside
 * of there, or some tricky things may fail (including intempestive logouts)
 *
 * IMPORTANT: If you want to test b2evolution on your local machine, do NOT use that machine's
 * name in the $baseurl!
 * For example, if your machine is called HOMER, do not use http://homer/b2evolution/blogs/ !
 * Use http://localhost/b2evolution/blogs/ instead. And log in on localhost too, not homer!
 * If you don't, login cookies will not hold.
 *
 * @global string $baseurl
 */
$baseurl = 'http://localhost/b2evolution/blogs/';
// Use the following if you want to use the current domain:
/*
if( isset($_SERVER['HTTP_HOST']) )
{	// This only works if HOSt provided by webserver (i-e DOES NOT WORK IN PHP CLI MODE)
	$baseurl = ( (isset($_SERVER['HTTPS']) && ( $_SERVER['HTTPS'] != 'off' ) ) ?'https://':'http://')
							.$_SERVER['HTTP_HOST'].'/';
}
*/


/**
 * $assets_baseurl could be used to load assets from a different domain, like a cookie-less domain or a CDN.
 * Assets can be all kinds of static contents: CSS, JS, media images, skin specific CSS, etc.
 *
 * @global string $assets_baseurl
 */
$assets_baseurl = $baseurl;


/**
 * This is used to create the Admin and the demo accounts at install time (not used after install).
 * @todo move to installer.
 */
$admin_email = 'postmaster@localhost';


/**
 * Once you have edited this file to your settings, set the following to 1 (one):
 */
$config_is_done = 0;


/*
 * IMPORTANT: you will find more parameters in the other files of the /conf folder.
 * IT IS RECOMMENDED YOU DO NOT TOUCH THOSE SETTINGS
 * UNTIL YOU ARE FAMILIAR WITH THE DEFAULT INSTALLATION.
 */
?>