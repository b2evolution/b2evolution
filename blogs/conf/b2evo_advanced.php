<?php
/*
 * b2evolution advanced config
 * Version of this file: 0.8.3
 *
 * Reminder: everything that starts with #, /* or // is a comment
 */

# short name of this system (will be used for cookies and notification emails)
# Change this only if you install mutliple b2evolutions on the same website.
# WARNING: don't play with this or you'll have tons of cookies sent away and your
# readers surely will complain about it!
# You can change notification email address alone later in this file
$b2evo_name = 'b2evo';


# database tables' names (change them if you want to have multiple b2's in a single database)
$tableposts = 'evo_posts';
$tableusers = 'evo_users';
$tablesettings = 'evo_settings';
$tablecategories = 'evo_categories';
$tablecomments = 'evo_comments';
$tableblogs = 'evo_blogs';
$tablepostcats = 'evo_postcats';
$tablehitlog = 'evo_hitlog';

# old b2 tables used exclusively by the upgrade mode of the install script
# you can delete or ignore those if you're not planning to upgrade from an original b2 database
$oldtableposts = 'b2posts';
$oldtableusers = 'b2users';
$oldtablesettings = 'b2settings';
$oldtablecategories = 'b2categories';
$oldtablecomments = 'b2comments';


# gzip compression. can actually be done either by PHP or by Apache (if your apache has mod_gzip)
# Set this to 1 if you want PHP to do gzip compression
# Set this to 0 if you want to let Apache do the job instead of PHP
# (Developpers: Letting apache do the compression will make PHP debugging easier)
$use_gzipcompression = 0;		



// *** Permalink control ***

# What do you want permalinks to link to?
# 'single' will link to a single post
# 'archive' will link to the post in the archives
$permalink_destination = 'single';					// 'single' is HIGHLY RECOMMENDED !!!

# These variables control what options permalinks include:
# It is recommended to set them to 1 except if you use $permalink_destination = 'archive';
$permalink_include_more = 1;			// Set this to 1 for permalinks to include full post text
$permalink_include_comments = 1;	// Set this to 1 for permalinks to include comments
$permalink_include_trackback = 1;	// Set this to 1 for permalinks to include trackbacks
$permalink_include_pingback = 1;	// Set this to 1 for permalinks to include pingbackss

# Extra path info allows really clean URLS for permalinks
# They will look like http://localhost/b2evolution/blogs/demoa/2003/05/20/p4
# instead of http://localhost/b2evolution/blogs/demoa.php?p=4&c=1&tb=1&pb=1&more=1
# Your server has to suuport this. This requires apache configuration.
# Note: OVH for example supports this by default.
$use_extra_path_info = 0; 				// Set this to 1 to enable clean extra path info


# Default status for new posts:
$default_post_status = 'published';		// 'published' or 'draft'


# set this to 1 if you want to use the 'preview' function
$use_preview = 1;


# set this to 0 to disable the spell checker, or 1 to enable it
$use_spellchecker = 0;



# ** Comments options **

# set this to 1 to require e-mail and name, or 0 to allow comments without e-mail/name
$require_name_email = 1;

# set this to 1 to let every author be notified about comments on their posts
# notifications will be sent to original author of the post
$comments_notify = 1;

# default email address for sending notifications
# Set a custom address:
// $notify_from = 'b2evolution@your_server.com'; // uncomment this if you want to customize
# Alternatively you can use this automated adress generation
$notify_from = $b2evo_name.'@'.$basehost; // comment this if you want to customize




# fill these only if you have a Cafelog ID,
# this enables your blog to be in the Recently Updated b2 blogs list.
# to obtain this ID, e-mail update@tidakada.com with these details:
#  name of the weblog, weblog's URL, your e-mail address, and a password
# in the future, the password will allow you to change these details online
$cafelogID = '';
$use_cafelogping = 0;    # set this to 1 if you do have a Cafelog ID

# set this to 1 if you want your site to be listed on http://weblogs.com when you add a new post
$use_weblogsping = 1;

# set this to 1 if you want your site to be listed on http://blo.gs when you add a new post
$use_blodotgsping = 1;
# Use etended ping to RSS?
$use_rss = 1;



// ** Trackback / Pingback **

# set this to 0 or 1, whether you want to allow your posts to be trackback'able or not
# note: setting it to zero would also disable sending trackbacks
$use_trackback = 1;

# set this to 0 or 1, whether you want to allow your posts to be pingback'able or not
# note: setting it to zero would also disable sending pingbacks
$use_pingback = 1;




// ** Image upload **

# set this to 0 to disable file upload, or 1 to enable it
$use_fileupload = 0;
# enter the real path of the directory where you'll upload the pictures
#   if you're unsure about what your real path is, please ask your host's support staff
#   note that the  directory must be writable by the webserver (ChMod 766)
#   note for windows-servers users: use forwardslashes instead of backslashes
#$fileupload_realpath = '/home/your/site/b2/images';
$fileupload_realpath = '/home/example/public_html/images';
# Alternatively you may want to use this form:
# $fileupload_realpath = dirname(__FILE__).'/../contents';
# enter the URL of that directory (it's used to generate the links to the pictures)
$fileupload_url = 'http://example.com/images';
# Alternatively you may want to use this form:
# $fileupload_url = $baseurl."/contents";
# accepted file types, you can add to that list if you want
#   note: add a space before and after each file type
#   example: $fileupload_allowedtypes = ' jpg gif png ';
$fileupload_allowedtypes = ' jpg gif png ';
# by default, most servers limit the size of uploads to 2048 KB
#   if you want to set it to a lower value, here it is (you cannot set a higher value)
$fileupload_maxk = '96';
# you may not want all users to upload pictures/files, so you can set a minimum level for this
$fileupload_minlevel = '1';
# ...or you may authorize only some users. enter their logins here, separated by spaces
#   if you leave that variable blank, all users who have the minimum level are authorized to upload
#   note: add a space before and after each login name
#   example: $fileupload_allowedusers = ' barbara anne ';
$fileupload_allowedusers = '';





// ** Configuration for b2mail.php ** (skip this if you don't intend to blog via email)
# mailserver settings
$mailserver_url = 'mail.example.com';
$mailserver_login = 'login@example.com';
$mailserver_pass = 'password';
$mailserver_port = 110;
# by default posts will have this category
$default_category = 1;
# subject prefix
$subjectprefix = 'blog:';
# body terminator string (starting from this string, everything will be ignored, including this string)
$bodyterminator = "___";
# set this to 1 to run in test mode
$thisisforfunonly = 0;
### Special Configuration for some phone email services
# some mobile phone email services will send identical subject & content on the same line
# if you use such a service, set $use_phoneemail to 1, and indicate a separator string
# when you compose your message, you'll type your subject then the separator string
# then you type your login:password, then the separator, then content
$use_phoneemail = 0;
$phoneemail_separator = ':::';



# New stub files:
// $default_stub_mod = 0644;	// don't forget leading 0 !!!
// $default_stub_owner = 'use_your_unix_username_here';




# CHANGE THE FOLLOWING ONLY IF YOU KNOW WHAT YOU'RE DOING!
$use_cache = 1;							// Not using this will dramatically overquery the DB !
$sleep_after_edit = 0;			// let DB do its stuff...


# Getting vars from web server:
$REMOTE_ADDR=getenv('REMOTE_ADDR'); /* visitor's IP */
$HTTP_USER_AGENT=getenv('HTTP_USER_AGENT'); /* visitor's browser */


/* ## Cookies ## */

# This is the path that will be associated to cookies
# That means cookies set by this b2evo install won't be seen outside of this path on the domain below
$cookie_path = preg_replace('#http://[^/]+#', '', $baseurl ).'/';

# This is the cookie domain
# That means cookies set by this b2evo install won't be seen outside of this domain
$cookie_domain = ($basehost=='localhost') ? '' : '.'.$basehost;
//echo 'domain=',$cookie_domain,' path=',$cookie_path;

# Cookie names:
$cookie_user = 'cookie'.$b2evo_name.'user';
$cookie_pass = 'cookie'.$b2evo_name.'pass';
$cookie_state = 'cookie'.$b2evo_name.'state';
$cookie_name = 'cookie'.$b2evo_name.'name';
$cookie_email = 'cookie'.$b2evo_name.'email';
$cookie_url = 'cookie'.$b2evo_name.'url';

# Expiration (values in seconds)
# Set this to 0 if you wish to use non permanent cookies (erased when browser is closed)
$cookie_expires = time() + 31536000;		// Default: one year from now

# Expired time used to erase cookies:
$cookie_expired = time() - 86400;				// Default: 24 hours ago


/* Stop editing */

/* EXPERIMENTAL: Edit at your own risk! */

# BELOW IS SUBDIRECTORY CONFIGURATION
# THIS IS NOT FUNCTIONAL YET !!!
# DO NOT ATTEMPT TO CHANGE THIS UNTIL A NEWER VERSION THAT CAN HANDLE THIS
$backoffice_subdir = 'admin';
# Relative path to go back to base:
$pathadmin_out = '..';
# You should NOT (NEVER!) touch this:
$pathserver = $baseurl.'/'.$backoffice_subdir;
# Other paths (you can change these if you know what you're doing)
$pathxmlsrv = 'xmlsrv';
$pathxmlsrv_out = '..';
$xmlsrvurl = $baseurl.'/'.$pathxmlsrv;
# this is the relative FILE path for accessing the includes
$pathcore = 'b2evocore';
$b2inc = $pathadmin_out.'/'.$pathcore;
# Relative path to go back to base:
$pathcore_out = '..';
$pathhtsrv = "htsrv";
$pathhtsrv_out = "..";
# END OF SUBDIRECTORY CONFIGURATION

?>