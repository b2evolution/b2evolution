<?php

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
	'file_perms' => array(    // edit permissions of a file
		'file' => $admin_subdir.'img/fileperms.gif',
		'alt' => T_('Permissions'),
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


	'collapse' => array(
		'file' => $img_subdir.'collapse.gif',
		'alt' => T_('Close'),
	),
	'expand' => array(
		'file' => $img_subdir.'expand.gif',
		'alt' => T_('Open'),
	),
	'reload' => array(
		'file' => $img_subdir.'reload.png',
		'alt' => T_('Reload'),
	),
	'download' => array(
		'file' => $img_subdir.'download_manager.png',
		'alt' => T_('Download'),
	),

);


/**
 * Image sizes of icon files.
 *
 * @global array filename relative to {@link $basepath} => width, height
 */
$map_iconsizes = array(
	$admin_subdir.'img/ascending.png' => array( 16, 16 ),
	$admin_subdir.'img/descending.png' => array( 16, 16 ),
	$admin_subdir.'img/edit.png' => array( 16, 16 ),
	$admin_subdir.'img/filecopy.png' => array( 16, 16 ),
	$admin_subdir.'img/filedelete.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/default.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/document.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/empty.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/encrypted.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/folder.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/image2.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/log.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/message.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/pdf-document.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/php.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/pk.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/sound.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/tar.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/tgz.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/video.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/wordprocessing.png' => array( 16, 16 ),
	$admin_subdir.'img/fileicons/www.png' => array( 16, 16 ),
	$admin_subdir.'img/filemove.png' => array( 16, 16 ),
	$admin_subdir.'img/fileperms.gif' => array( 16, 16 ),
	$admin_subdir.'img/filerename.png' => array( 16, 16 ),
	$admin_subdir.'img/folder_home2.png' => array( 22, 22 ),
	$admin_subdir.'img/up.png' => array( 22, 22 ),
	$admin_subdir.'img/window_new.png' => array( 15, 13 ),
	$img_subdir.'download_manager.png' => array( 16, 16 ),
	$img_subdir.'reload.png' => array( 16, 16 ),

);

?>
