<?php
/*
 * This is b2evolution's config file
 * Version of this file: 0.8.3
 *
 * You need to edit this file to your settings before attempting to install the database!
 *
 * Reminder: everything that starts with #, /* or // is a comment
 */

/* Start editing */


# IMPORTANT: Take special care not to erase quotes (') around text parameters 
# and semicolums (;) at the end of the lines. Otherwide you'll get some 
# "unexpected T_STRING" parse errors!



# Your email. Will be used in backoffice error messages. 
# You will also receive notifications for new user registrations.
$admin_email = 'postmaster@localhost';


# *** MySQL settings ***
# fill with your database details (check carefully or nothing will work)
$dbhost = 'localhost' ;                // mySQL Server 
$dbname = 'b2evolution';               // the name of the database
$dbusername = 'demouser';              // your MySQL username
$dbpassword = 'demopass';              // ...and password


# If you want to be able to reset your existing b2evolution tables and start anew
# you must set $allow_evodb_reset to 1.
# NEVER LEAVE THIS SETTING ON ANYTHING ELSE THAN 0 (ZERO) ON A PRODUCTION SERVER.
# IF THIS IS ON AND YOU FORGET TO DELETE THE INSTALL FOLDER, ANYONE WOULD BE ABLE TO
# ERASE YOUR B2EVOLUTION TABLES AND DATA BY A SINGLE CLICK!
$allow_evodb_reset = 0;	// Set to 1 to enable. Do not leave this on 1 on production servers

$allow_evodb_reset = 1;  // FOR TESTING

# $baseurl is where your blogs reside by default. CHECK THIS CAREFULLY or nothing will work.
# It should be set to the URL where you can find the blog stub files index.php, blog_b.php, etc.
# Example: 
# $baseurl = 'http://www.example.com/blogs';
# IMPORTANT: IF YOU CHANGE THIS AFTER YOU ALREADY INSTALLED THE DATABASE, YOU MUST
# EITHER RE-INSTALL THE DATABASE ANEW OR UPDATE IT BY HAND (evo_blogs table)
# Note: No blog should be outside of there, or some tricky things may fail (e-g: pingback)
$baseurl = 'http://localhost/b2evolution/blogs';		// IMPORTANT: NO ENDING SLASH !!!

$baseurl = 'http://localhost:8088/b2evolution/blogs';		// FOR TESTING


# set this to 1 if you want to allow users to register on your blog.
$users_can_register = 0;

$users_can_register = 1;		// FOR TESTING


# set this to 1 if you want new users to be able to post entries once they registered
$new_users_can_blog = 0;

$new_users_can_blog = 1;		// FOR TESTING


# set this to 1 if you want to display the blog list on blog templates
$display_blog_list = 0;

# set this to  1 if you want to be able to cross-post among multiple blogs
$allow_cross_posting = 0;


# IMPORTANT: you will find more parameters in the other files of the /conf folder
# IT IS RECOMMENDED YOU DO NOT TOUCH THOSE SETTINGS 
# UNTIL YOU ARE FAMILIAR WITH THE DEFAULT INSTALLATION
# It is however strongly recommended you browse through these files as soon as you've
# got your basic installation working. They'll let you customize a lot of things!

/* Stop editing */

// Get hostname out of baseurl
preg_match( '#http://([^:/]+)#', $baseurl, $matches );
$basehost = $matches[1];
//echo 'basehost=',$basehost;

require_once (dirname(__FILE__)."/b2evo_advanced.php");
require_once (dirname(__FILE__)."/b2evo_locale.php");
require_once (dirname(__FILE__)."/b2evo_formatting.php");

?>