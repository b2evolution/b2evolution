<?php
/**
 * This is b2evolution's admin config file
 *
 * This sets how the back-office works
 * Last significant changes to this file: version 0.9.1
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package conf
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

// EXPERMIENTAL:
$admin_skin = 'evo';
$admin_skin = 'legacy';

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
 * @global bool $allow_moving_chapters
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
 * When banning referrers/comment URLs, do you want to remove
 * any referrers and comments containing the banned domain?
 *
 * (you will be asked to confirm the ban if you enable this)
 *
 * @global boolean $deluxe_ban
 */
$deluxe_ban = 1;  // 1 to enable, 0 to disable

/**
 * When banning, do you want to report abuse to the
 * centralized ban list at b2evolution.net?
 *
 * @global boolean $report_abuse
 */
$report_abuse = 1;


// ** Image upload ** {{{ @deprecated moved to admin interface, but used for upgrading to 0.9.2
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
 */
$fileupload_url = $baseurl.'media/';				# WARNING: slashes moved!

/**
 * Accepted file types, you can add to that list if you want.
 *
 * Note: add a space before and after each file type.
 * Example: $fileupload_allowedtypes = ' jpg gif png ';
 *
 * @global string $fileupload_allowedtypes
 * @deprecated 0.9.2: this is only used for creating the defaults when upgrading
 */
$fileupload_allowedtypes = ' jpg gif png ';

/**
 * by default, most servers limit the size of uploads to 2048 KB
 * if you want to set it to a lower value, here it is (you cannot set a higher value)
 *
 * @global int $fileupload_maxk
 * @deprecated 0.9.2: this is only used for creating the defaults when upgrading
 */
$fileupload_maxk = '96'; // in kilo bytes

/**
 * you may not want all users to upload pictures/files, so you can set a minimum level for this
 * @global int $fileupload_minlevel
 * @deprecated 0.9.2: this is only used for creating the defaults when upgrading
 */
$fileupload_minlevel = 1;

/**
 * You may want to authorize only some users to upload. Enter their logins here, separated by space.
 *
 * if you leave that variable blank, all users who have the minimum level are authorized to upload.
 * note: add a space before and after each login name.
 * example: $fileupload_allowedusers = ' barbara anne ';
 *
 * @global string $fileupload_allowedusers
 * @deprecated 0.9.2: this is only used for creating the defaults when upgrading
 */
$fileupload_allowedusers = '';
// }}}


/**
 * max length for blog_urlname and blog_stub values
 *
 * (this gets checked when editing/creating blogs).
 *
 * @global int $maxlength_urlname_stub
 */
$maxlength_urlname_stub = 30;


/**
 * Map of filenames for icons and their respective alt tag.
 *
 * @global array icon name => array( 'file', 'alt' )
 */
$map_iconfiles = array(
	'folder' => array(        // icon for folders
		'file' => $admin_subdir.'img/fileicons/folder.png',
		'alt' => T_('folder'),
	),
	'file_unknown' => array(  // icon for unknown files
		'file' => $admin_subdir.'img/fileicons/default.png',
		'alt' => T_('Unknown file'),
	),
	'file_empty' => array(    // empty file
		'file' => $admin_subdir.'img/fileicons/empty.png',
		'alt' => T_('Empty file'),
	),

	'folder_parent' => array( // go to parent directory
		'file' => $admin_subdir.'img/up.png',
		'alt' => T_('Parent folder'),
	),
	'folder_home' => array(   // home folder
		'file' => $admin_subdir.'img/folder_home2.png',
		'alt' => T_('Home folder'),
	),

	'file_edit' => array(     // edit a file
		'file' => $admin_subdir.'img/edit.png',
		'alt' => T_('Edit'),
	),
	'file_copy' => array(     // copy a file/folder
		'file' => $admin_subdir.'img/filecopy.png',
		'alt' => T_('Copy'),
	),
	'file_move' => array(     // move a file/folder
		'file' => $admin_subdir.'img/filemove.png',
		'alt' => T_('Move'),
	),
	'file_rename' => array(   // rename a file/folder
		'file' => $admin_subdir.'img/filerename.png',
		'alt' => T_('Rename'),
	),
	'file_delete' => array(   // delete a file/folder
		'file' => $admin_subdir.'img/filedelete.png',
		'alt' => T_('Delete'),
	),


	'ascending' => array(     // sort ascending
		'file' => $admin_subdir.'img/ascending.png',
		'alt' => T_('ascending'),
	),
	'descending' => array(    // sort descending
		'file' => $admin_subdir.'img/descending.png',
		'alt' => T_('descending'),
	),
	'window_new' => array(    // open in a new window
		'file' => $admin_subdir.'img/window_new.png',
		'alt' => T_('New window'),
	),


	'file_word' => array(
		'ext' => '\.(s[txd]w|doc|rtf)',
		'file' => $admin_subdir.'img/fileicons/wordprocessing.png',
		'alt' => '',
	),
	'file_image' => array(
		'ext' => '\.(gif|png|jpe?g)',
		'file' => $admin_subdir.'img/fileicons/image2.png',
		'alt' => '',
	),
	'file_www' => array(
		'ext' => '\.html?',
		'file' => $admin_subdir.'img/fileicons/www.png',
		'alt' => '',
	),
	'file_log' => array(
		'ext' => '\.log',
		'file' => $admin_subdir.'img/fileicons/log.png',
		'alt' => '',
	),
	'file_sound' => array(
		'ext' => '\.(mp3|ogg|wav)',
		'file' => $admin_subdir.'img/fileicons/sound.png',
		'alt' => '',
	),
	'file_video' => array(
		'ext' => '\.(mpe?g|avi)',
		'file' => $admin_subdir.'img/fileicons/video.png',
		'alt' => '',
	),
	'file_message' => array(
		'ext' => '\.msg',
		'file' => $admin_subdir.'img/fileicons/message.png',
		'alt' => '',
	),
	'file_document' => array(
		'ext' => '\.pdf',
		'file' => $admin_subdir.'img/fileicons/pdf-document.png',
		'alt' => '',
	),
	'file_php' => array(
		'ext' => '\.php[34]?',
		'file' => $admin_subdir.'img/fileicons/php.png',
		'alt' => '',
	),
	'file_encrypted' => array(
		'ext' => '\.(pgp|gpg)',
		'file' => $admin_subdir.'img/fileicons/encrypted.png',
		'alt' => '',
	),
	'file_tar' => array(
		'ext' => '\.tar',
		'file' => $admin_subdir.'img/fileicons/tar.png',
		'alt' => '',
	),
	'file_tgz' => array(
		'ext' => '\.tgz',
		'file' => $admin_subdir.'img/fileicons/tgz.png',
		'alt' => '',
	),
	'file_document' => array(
		'ext' => '\.te?xt',
		'file' => $admin_subdir.'img/fileicons/document.png',
		'alt' => '',
	),
	'file_pk' => array(
		'ext' => '\.(zip|rar)',
		'file' => $admin_subdir.'img/fileicons/pk.png',
		'alt' => '',
	),

);


/**
 * Image sizes of icon files.
 *
 * @global array filename relative to {@link $basepath} => width, height
 */
$map_iconsizes = array(
	$admin_subdir.'img/fileicons/folder.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/default.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/empty.png' => array( 16, 16 ),
	$admin_subdir.'img/up.png' => array( 22, 22 ),
	$admin_subdir.'img/folder_home2.png' => array( 22, 22 ),
	$admin_subdir.'img/edit.png' => array( 16, 16 ),
	$admin_subdir.'img/filecopy.png' => array( 16, 16 ),
	$admin_subdir.'img/filemove.png' => array( 16, 16 ),
	$admin_subdir.'img/filerename.png' => array( 16, 16 ),
	$admin_subdir.'img/filedelete.png' => array( 16, 16 ),
	$admin_subdir.'img/ascending.png' => array( 16, 16 ),
	$admin_subdir.'img/descending.png' => array( 16, 16 ),
	$admin_subdir.'img/window_new.png' => array( 15, 13 ),
);


?>