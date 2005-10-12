<?php
/**
 * This is b2evolution's main config file
 *
 * You need to edit this file to your settings before attempting to install the database!
 * Last significant changes to this file: version 1.6
 *
 *
 * Reminder: every line starting with # or // is a comment, multiline comments are
 *           surrounded by '/*' and '* /' (without space).
 *
 * IMPORTANT: Take special care not to erase quotes (') around text parameters
 * and semicolums (;) at the end of the lines. Otherwise you'll get some
 * "unexpected T_STRING" parse errors!
 *
 * Contributors: you should override this file by creating a file named _config_TEST.php
 * (see end of this file)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package conf
 */

/*
header('HTTP/1.0 503 Service Unavailable');
echo '<h1>503 Service Unavailable</h1>';
die( 'The site is temporarily down for maintainance.' );
*/

/**
 * MySQL DB settings.
 * Fill in your database details (check carefully or nothing will work!)
 */
$EvoConfig->DB = array(
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
 * If you want to be able to reset your existing b2evolution tables and start anew
 * you must set $allow_evodb_reset to 1.
 *
 * NEVER LEAVE THIS SETTING ON ANYTHING ELSE THAN 0 (ZERO) ON A PRODUCTION SERVER.
 * IF THIS IS ON (1) AND YOU FORGET TO DELETE THE INSTALL FOLDER, ANYONE WOULD BE ABLE TO
 * ERASE YOUR B2EVOLUTION TABLES AND DATA BY A SINGLE CLICK!
 */
$allow_evodb_reset = 0;	// Set to 1 to enable. Do not leave this on 1 on production servers


/**
 * $baseurl is where your blogs reside by default. CHECK THIS CAREFULLY or nothing will work.
 * It should be set to the URL where you can find the blog templates and/or the blog stub files,
 * that means index.php, blog_b.php, etc.
 * Note: Blogs can be in subdirectories of the baseurl. However, no blog should be outside
 * of there, or some tricky things may fail (e-g: pingback)
 *
 * IMPORTANT: If you want to test b2evolution on your local machine, do NOT use that machine's
 * name in the $baseurl!
 * For example, if you machine is called HOMER, do not use http://homer/b2evolution/blogs/ !
 * Use http://localhost/b2evolution/blogs/ instead. And log in on localhost too, not homer!
 * If you don't, login cookies will not hold.
 *
 * @global string $baseurl
 */
$baseurl = 'http://localhost/b2evolution/blogs/';


/**
 * Your email. Will be used in severe error messages so that users can contact you.
 * You will also receive notifications for new user registrations there.
 */
$admin_email = 'postmaster@localhost';


/**
 * Once you have edited this file to your settings, set the following to 1 (one):
 */
$config_is_done = 0;


# IMPORTANT: you will find more parameters in the other files of the /conf folder.
# IT IS RECOMMENDED YOU DO NOT TOUCH THOSE SETTINGS
# UNTIL YOU ARE FAMILIAR WITH THE DEFAULT INSTALLATION.
#
# It is however strongly recommended you browse through these files as soon as you've
# got your basic installation working. They'll let you customize a lot of things!

# DO NOT EDIT THE FOLLOWING!

define( 'EVO_CONFIG_LOADED', true );

@include_once dirname(__FILE__).'/_config_TEST.php';    // Put testing conf in there (For testing, you can also set $install_password here)
require_once  dirname(__FILE__).'/_advanced.php';       // advanced settings
require_once  dirname(__FILE__).'/_locales.php';        // locale settings
require_once  dirname(__FILE__).'/_formatting.php';     // formatting settings
require_once  dirname(__FILE__).'/_admin.php';          // admin settings
require_once  dirname(__FILE__).'/_stats.php';          // stats/hitlogging settings
@include_once dirname(__FILE__).'/_overrides_TEST.php'; // Override for testing in there
?>