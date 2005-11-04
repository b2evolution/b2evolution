<?php
/**
 * This is b2evolution's admin config file
 *
 * This sets how the back-office works
 * Last significant changes to this file: version 1.6
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package conf
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Cross posting
 *
 * Possible values:
 *   - 0 if you want users to post to a single category only
 *   - 1 if you want to be able to cross-post among multiple categories
 *   - 2 if you want to be able to cross-post among multiple blogs/categories
 *   - 3 if you want to be able to change main cat among blogs (which will move the
 *       posts from one blog to another; use with caution)
 *
 * @global int $allow_cross_posting
 */
$allow_cross_posting = 1;


/**
 * Moving chapters between blogs?
 *
 * Special value: NULL and we won't even talk about moving
 *
 * @global bool|NULL $allow_moving_chapters
 */
$allow_moving_chapters = false;


/**
 * Default status for new posts:
 *
 * Possible values: 'published', 'deprecated', 'protected', 'private', 'draft'
 *
 * @global string $default_post_status
 */
$default_post_status = 'published';


/**
 * set this to 1 if you want to use the 'preview' function
 * @global boolean $use_preview
 */
$use_preview = 1;


/**
 * Do you want to be able to link each post to an URL ?
 * @global boolean $use_post_url
 */
$use_post_url = 1;  // 1 to enable, 0 to disable


/**
 * When banning, do you want to report abuse to the
 * centralized ban list at b2evolution.net?
 *
 * @global boolean $report_abuse
 */
$report_abuse = 1;


// ** Image upload ** {{{ @deprecated moved to admin interface, but used for upgrading to 1.6
/**
 * Set this to 0 to disable file upload, or 1 to enable it
 * @global boolean $use_fileupload
 * @deprecated 0.9.2: this is only used for creating the defaults when upgrading
 */
$use_fileupload = 1;

/**
 * Enter the real path of the directory where you'll upload the pictures.
 *
 * If you're unsure about what your real path is, please ask your host's support staff.
 * Note that the  directory must be writable by the webserver (ChMod 766).
 * Note for windows-servers users: use forwardslashes instead of backslashes.
 * Example: $fileupload_realpath = '/home/example/public_html/media/';	# WITH traling slash!
 * Alternatively you may want to use a path relative to $basepath.
 *
 * @global string $fileupload_realpath
 * @deprecated 0.9.2: the user uploads to his own media folder (or somewhere else with write permissions)
 * This is still used by MMS and XMLRPC though.
 */
$fileupload_realpath = $basepath.'media/';	# WARNING: slashes moved!

/**
 * Enter the URL of that directory
 *
 * This is used to generate the links to the pictures
 * Example: $fileupload_url = 'http://example.com/media/';	# WITH traling slash!
 * Alternatively you may want to use an URL relatibe to $baseurl
 *
 * @global string $fileupload_url
 * @deprecated 0.9.2: the user uploads to his own media folder (or somewhere else with write permissions)
 * This is still used by MMS and the MT importer though.
 */
$fileupload_url = $baseurl.'media/';				# WARNING: slashes moved!

// }}}


/**
 * max length for blog_urlname and blog_stub values
 *
 * (this gets checked when editing/creating blogs).
 *
 * @global int $maxlength_urlname_stub
 */
$maxlength_urlname_stub = 30;
?>