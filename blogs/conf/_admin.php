<?php
/**
 * This is b2evolution's admin config file
 *
 * This sets how the back-office works
 * Last significant changes to this file: version 0.9.0.0
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package conf
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

# Cross posting:
# set this to 0 if you want users to post to a single category only
# set this to 1 if you want to be able to cross-post among multiple categories
# set this to 2 if you want to be able to cross-post among multiple blogs/categories
$allow_cross_posting = 1;


# Default status for new posts:
$default_post_status = 'published';  // 'published', 'deprecated', 'protected', 'private', 'draft'


# set this to 1 if you want to use the 'preview' function
$use_preview = 1;


# set this to 0 to disable the spell checker, or 1 to enable it
$use_spellchecker = 1;


# Do you want to be able to link each post to an URL ?
$use_post_url = 1;  // 1 to enable, 0 to disable


# When banning referrers/comment URLs, do you want to remove
# any referrers and comments containing the banned domain?
# (you will be asked to confirm the ban if you enable this)
$deluxe_ban = 1;  // 1 to enable, 0 to disable

# When banning, do you want to report abuse to the
# centralized ban list at b2evolution.net?
$report_abuse = 1;


// ** Image upload **

# set this to 0 to disable file upload, or 1 to enable it
$use_fileupload = 1;
# enter the real path of the directory where you'll upload the pictures
#   if you're unsure about what your real path is, please ask your host's support staff
#   note that the  directory must be writable by the webserver (ChMod 766)
#   note for windows-servers users: use forwardslashes instead of backslashes
# $fileupload_realpath = '/home/example/public_html/media';
# Alternatively you may want to use this form:
$fileupload_realpath = $basepath.'/media';
# enter the URL of that directory (it's used to generate the links to the pictures)
# $fileupload_url = 'http://example.com/media';
# Alternatively you may want to use this form:
$fileupload_url = $baseurl.'/media';
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

/**
 * max length for blog_urlname and blog_stub values (this gets checked when editing/creating blogs).
 */
$maxlength_urlname_stub = 30;


# Do not edit the following unless you known what you're doing...
# Backoffice two colum display:
$admin_2col_start = '<div class="bPosts">';
$admin_2col_nextcol = '</div><div class="bSideBar">';
$admin_2col_end = '</div><div style="clear:both;"></div>';
# If you run into troubles with the backoffice two colum display, you can use the alternative below:
# $admin_2col_start = '<table cellspacing="0"><tr><td class="bPosts">';
# $admin_2col_nextcol = '</td><td class="bSideBar">';
# $admin_2col_end = '</td></tr></table>';

?>