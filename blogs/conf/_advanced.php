<?php
/*
 * b2evolution advanced config
 * Version of this file: 0.8.6.2
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
$tablemailinglist = 'evo_mailinglist';
$tableantispam = 'evo_antispam';
$tablepluginsettings = 'evo_pluginsettings';

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
$permalink_include_pingback = 1;	// Set this to 1 for permalinks to include pingbacks

# Extra path info allows really clean URLS for permalinks
# They will look like http://localhost/b2evolution/blogs/demoa/2003/05/20/p4
# instead of http://localhost/b2evolution/blogs/demoa.php?p=4&c=1&tb=1&pb=1&more=1
# Your server has to suuport this. This requires apache configuration.
# Note: OVH for example supports this by default.
$use_extra_path_info = 0; 				// Set this to 1 to enable clean extra path info


# Default status for new posts:
$default_post_status = 'published';		// 'published', 'deprecated', 'protected', 'private', 'draft'


# set this to 1 if you want to use the 'preview' function
$use_preview = 1;


# set this to 0 to disable the spell checker, or 1 to enable it
$use_spellchecker = 1;



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



# Listing on http://b2evolution.net
# set this to 1 if you want your site to be listed on b2evolution.net when you add a new post
# ATTENTION: Please do only enable this AFTER you have done all your blog setup tests and when
# your blog url is definitive. This way you avoid leaving broken URLs out on the net.
# PLEASE NOTE: If you removed the b2evolution button and the link to b2evolution from your blog,
# don't even bother enabling this. You will *not* be approved and your blog will be blacklisted.
# Also, the Full Name of your blog must be written in ISO 8859-1 (Latin-1) charset, otherwise we
# cannot display it on b2evolution.net. You can use HTML entities (e-g &Kappa;) for non latin chars.
$use_b2evonetping = 0;

$use_b2evonetping = 1;		// FOR TESTING


# Listing on http://cafelog.com
# set this to 1 if you want your site to be listed on cafelog.com when you add a new post
# ATTENTION: Please do only enable this AFTER you have done all your blog setup tests and when
# your blog url is definitive. This way you avoid leaving broken URLs out on the net.
# Fill these only if you obtained a Cafelog ID when b2/cafelog was active. 
$cafelogID = '';				 # Fill in your Cafelog ID here
$use_cafelogping = 0;    # set this to 1 if you do have a Cafelog ID

# Listing on http://weblogs.com
# set this to 1 if you want your site to be listed on weblogs.com when you add a new post
# ATTENTION: Please do only enable this AFTER you have done all your blog setup tests and when
# your blog url is definitive. This way you avoid leaving broken URLs out on the net.
$use_weblogsping = 1;		 # This default is 1 for all those guys who'll never read this file! :P
												 # this way they'll have at least one ping up and running. 
												 # weblogs.com will automatically discard test pings after a while.

# Listing on http://blo.gs
# set this to 1 if you want your site to be listed on http://blo.gs when you add a new post
# ATTENTION: Please do only enable this AFTER you have done all your blog setup tests and when
# your blog url is definitive. This way you avoid leaving broken URLs out on the net.
$use_blodotgsping = 0;
# Use extended ping to RSS?
$use_rss = 1;

# Listing on http://technorati.com
# set this to 1 if you want your site to be listed on http://technorati.com when you add a new post
# ATTENTION: Please do only enable this AFTER you have done all your blog setup tests and when
# your blog url is definitive. This way you avoid leaving broken URLs out on the net.
$use_technoratiping = 0;


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


/* ## Cookies ## */

# This is the path that will be associated to cookies
# That means cookies set by this b2evo install won't be seen outside of this path on the domain below
$cookie_path = preg_replace('#https?://[^/]+#', '', $baseurl ).'/';

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


# Location of the b2evolution subdirectories:
# You should only move these around if you really need to.
# You should keep everything as subdirectories of the base folder 
# ($baseurl which is set in _config.php, default is the /blogs folder)
# Remember you can set the baseurl to your website root.
# NOTE: ALL PATHS MUST HAVE NO LEADING AND NO TRAILING SLASHES !!!
# Example of a possible setting:
# $admin_subdir = 'backoffice/b2evo';				// Subdirectory relative to base
# $admin_dirout = '../..';									// Relative path to go back to base

# Location of the backoffice (admin) folder:
$admin_subdir = 'admin';										// Subdirectory relative to base
$admin_dirout = '..';												// Relative path to go back to base
$admin_url = $baseurl.'/'.$admin_subdir;		// You should not need to change this
# Location of the HTml SeRVices folder:
$htsrv_subdir = 'htsrv';										// Subdirectory relative to base
$htsrv_dirout = '..';												// Relative path to go back to base
$htsrv_url = $baseurl.'/'.$htsrv_subdir;		// You should not need to change this
# Location of the XML SeRVices folder:
$xmlsrv_subdir = 'xmlsrv'; 									// Subdirectory relative to base
$xmlsrv_dirout = '..';											// Relative path to go back to base
$xmlsrv_url = $baseurl.'/'.$xmlsrv_subdir;	// You should not need to change this
# Location of the skins folder:
$skins_subdir = 'skins'; 										// Subdirectory relative to base
$skins_dirout = '..';												// Relative path to go back to base
$skins_url = $baseurl.'/'.$skins_subdir;		// You should not need to change this
# Location of the core (the "includes") files:
$core_subdir = 'b2evocore'; 								// Subdirectory relative to base
$core_dirout = '..';												// Relative path to go back to base
# Location of the locales folder:
$locales_subdir = 'locales';								// Subdirectory relative to base
$locales_dirout = '..';											// Relative path to go back to base
# Location of the configuration files:
$conf_subdir = 'conf';											// Subdirectory relative to base
$conf_dirout = '..';												// Relative path to go back to base
# Location of the install folder:
$install_dirout = '..';											// Relative path to go back to base

?>
