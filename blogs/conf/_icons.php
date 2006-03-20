<?php
/**
 *
 *
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Map of filenames for icons and their respective alt tag.
 *
 * @todo For performance reasons get_icon() shoudld handle the T_() and this array should only contain NT_() declarations
 *
 * @global array icon name => array( 'file', 'alt', 'size', 'class', 'rollover' )
 */
$map_iconfiles = array(
	'folder' => array(        // icon for folders
		'file' => $rsc_subdir.'icons/fileicons/folder.gif',
		'alt'  => T_('Folder'),
		'size' => array( 16, 15 ),
	),
	'file_unknown' => array(  // icon for unknown files
		'file' => $rsc_subdir.'icons/fileicons/default.png',
		'alt'  => T_('Unknown file'),
		'size' => array( 16, 16 ),
	),
	'file_empty' => array(    // empty file
		'file' => $rsc_subdir.'icons/fileicons/empty.png',
		'alt'  => T_('Empty file'),
		'size' => array( 16, 16 ),
	),

	'folder_parent' => array( // go to parent directory
		'file' => $rsc_subdir.'icons/up.gif',
		'alt'  => T_('Parent folder'),
		'size' => array( 16, 15 ),
	),
	'folder_home' => array(   // home folder
		'file' => $rsc_subdir.'icons/folder_home2.png',
		'alt'  => T_('Home folder'),
		'size' => array( 16, 16 ),
	),

	'file_edit' => array(     // edit a file
		'file' => $rsc_subdir.'icons/edit.png',
		'alt'  => T_('Edit'),
		'size' => array( 16, 16 ),
	),
	'file_copy' => array(     // copy a file/folder
		'file' => $rsc_subdir.'icons/filecopy.png',
		'alt'  => T_('Copy'),
		'size' => array( 16, 16 ),
	),
	'file_move' => array(     // move a file/folder
		'file' => $rsc_subdir.'icons/filemove.png',
		'alt'  => T_('Move'),
		'size' => array( 16, 16 ),
	),
	'file_rename' => array(   // rename a file/folder
		'file' => $rsc_subdir.'icons/filerename.png',
		'alt'  => T_('Rename'),
		'size' => array( 16, 16 ),
	),
	'file_delete' => array(   // delete a file/folder
		'file' => $rsc_subdir.'icons/filedelete.png',
		'alt'  => T_('Del'),
		'legend'=>T_('Delete'),
		'size' => array( 16, 16 ),
	),
	'file_perms' => array(    // edit permissions of a file
		'file' => $rsc_subdir.'icons/fileperms.gif',
		'alt'  => T_('Permissions'),
		'size' => array( 16, 16 ),
	),


	'ascending' => array(     // ascending sort order
		'file' => $rsc_subdir.'icons/ascending.gif',
		'alt'  => /* TRANS: Short (alt tag) for "Ascending" */ T_('A'),
		'size' => array( 15, 15 ),
	),
	'descending' => array(    // descending sort order
		'file' => $rsc_subdir.'icons/descending.gif',
		'alt'  => /* TRANS: Short (alt tag) for "Descending" */ T_('D'),
		'size' => array( 15, 15 ),
	),

	'window_new' => array(    // open in a new window
		'file' => $rsc_subdir.'icons/window_new.png',
		'alt'  => T_('New window'),
		'size' => array( 15, 13 ),
	),


	'file_word' => array(
		'ext'  => '\.(s[txd]w|doc|rtf)',
		'file' => $rsc_subdir.'icons/fileicons/wordprocessing.png',
		'alt'  => '',
		'size' => array( 16, 16 ),
	),
	'file_image' => array(
		'ext'  => '\.(gif|png|jpe?g)',
		'file' => $rsc_subdir.'icons/fileicons/image2.png',
		'alt'  => '',
		'size' => array( 16, 16 ),
	),
	'file_www' => array(
		'ext'  => '\.html?',
		'file' => $rsc_subdir.'icons/fileicons/www.png',
		'alt'  => '',
		'size' => array( 16, 16 ),
	),
	'file_log' => array(
		'ext'  => '\.log',
		'file' => $rsc_subdir.'icons/fileicons/log.png',
		'alt'  => '',
		'size' => array( 16, 16 ),
	),
	'file_sound' => array(
		'ext'  => '\.(mp3|ogg|wav)',
		'file' => $rsc_subdir.'icons/fileicons/sound.png',
		'alt'  => '',
		'size' => array( 16, 16 ),
	),
	'file_video' => array(
		'ext'  => '\.(mpe?g|avi)',
		'file' => $rsc_subdir.'icons/fileicons/video.png',
		'alt'  => '',
		'size' => array( 16, 16 ),
	),
	'file_message' => array(
		'ext'  => '\.msg',
		'file' => $rsc_subdir.'icons/fileicons/message.png',
		'alt'  => '',
		'size' => array( 16, 16 ),
	),
	'file_document' => array(
		'ext'  => '\.pdf',
		'file' => $rsc_subdir.'icons/fileicons/pdf-document.png',
		'alt'  => '',
		'size' => array( 16, 16 ),
	),
	'file_php' => array(
		'ext'  => '\.php[34]?',
		'file' => $rsc_subdir.'icons/fileicons/php.png',
		'alt'  => '',
		'size' => array( 16, 16 ),
	),
	'file_encrypted' => array(
		'ext'  => '\.(pgp|gpg)',
		'file' => $rsc_subdir.'icons/fileicons/encrypted.png',
		'alt'  => '',
		'size' => array( 16, 16 ),
	),
	'file_tar' => array(
		'ext'  => '\.tar',
		'file' => $rsc_subdir.'icons/fileicons/tar.png',
		'alt'  => '',
		'size' => array( 16, 16 ),
	),
	'file_tgz' => array(
		'ext'  => '\.tgz',
		'file' => $rsc_subdir.'icons/fileicons/tgz.png',
		'alt'  => '',
		'size' => array( 16, 16 ),
	),
	'file_document' => array(
		'ext'  => '\.te?xt',
		'file' => $rsc_subdir.'icons/fileicons/document.png',
		'alt'  => '',
		'size' => array( 16, 16 ),
	),
	'file_pk' => array(
		'ext'  => '\.(zip|rar)',
		'file' => $rsc_subdir.'icons/fileicons/pk.png',
		'alt'  => '',
		'size' => array( 16, 16 ),
	),


	'collapse' => array(
		'file' => $rsc_subdir.'icons/collapse.gif',
		'alt'  => T_('Close'),
		'size' => array( 16, 16 ),
	),
	'expand' => array(
		'file' => $rsc_subdir.'icons/expand.gif',
		'alt'  => T_('Open'),
		'size' => array( 16, 16 ),
	),
	'reload' => array(
		'file' => $rsc_subdir.'icons/reload.png',
		'alt'  => T_('Reload'),
		'size' => array( 16, 16 ),
	),
	'download' => array(
		'file' => $rsc_subdir.'icons/download_manager.png',
		'alt'  => T_('Download'),
		'size' => array( 16, 16 ),
	),


	'warning' => array(
		'file' => $rsc_subdir.'icons/warning.png', // TODO: not really transparent at its borders
		'alt'  => T_('Warning'),
		'size' => array( 16, 16 ),
	),

	'info' => array(
		'file' => $rsc_subdir.'icons/info.gif',
		'alt'  => T_('Info'),
		'size' => array( 16, 16 ),
	),
	'email' => array(
		'file' => $rsc_subdir.'icons/envelope.gif',
		'alt'  => T_('Email'),
		'size' => array( 13, 10 ),
	),
	'www' => array(   /* user's web site, plugin's help url */
		'file' => $rsc_subdir.'icons/url.gif',
		'alt'  => T_('WWW'),
		'legend' => T_('Website'),
		'size' => array( 34, 17 ),
	),

	'new' => array(
		'file' => $rsc_subdir.'icons/new.gif',
		'alt'  => T_('New'),
		'size' => array( 16, 15 ),
	),
	'copy' => array(
		'file' => $rsc_subdir.'icons/copy.gif',
		'alt'  => T_('Copy'),
		'size' => array( 14, 15 ),
	),
	'edit' => array(
		'file' => $rsc_subdir.'icons/edit.gif',
		'alt'  => T_('Edit'),
		'size' => array( 16, 15 ),
	),
	'properties' => array(
		'file' => $rsc_subdir.'icons/properties.png',
		'alt'  => T_('Properties'),
		'size' => array( 18, 13 ),
	),
	'publish' => array(
		'file' => $rsc_subdir.'icons/publish.gif',
		'alt'  => T_('Publish'),
		'size' => array( 12, 15 ),
	),
	'deprecate' => array(
		'file' => $rsc_subdir.'icons/deprecate.gif',
		'alt'  => T_('Deprecate'),
		'size' => array( 12, 15 ),
	),
	'locate' => array(
		'file' => $rsc_subdir.'icons/target.gif',
		'alt'  => T_('Locate'),
		'size' => array( 15, 15 ),
	),
	'delete' => array(
		'file' => $rsc_subdir.'icons/delete.gif',
		'alt'  => T_('Del'),
		'legend' => T_('Delete'),
		'size' => array( 15, 15 ),
	),
	'close' => array(
		'file' => $rsc_subdir.'icons/close.gif',
		'rollover' => true,
		'alt' => T_('Close'),
		'size' => array( 14, 14 ),
	),


	'increase' => array(
		'file' => $rsc_subdir.'icons/increase.gif',
		'rollover' => true,
		'alt' => T_('+'),
		'size' => array( 15, 15 ),
	),
	'decrease' => array(
		'file' => $rsc_subdir.'icons/decrease.gif',
		'rollover' => true,
		'alt' => T_('-'),
		'size' => array( 15, 15 ),
	),

	'bullet_full' => array(
		'file' => $rsc_subdir.'icons/bullet_full.png',
		'alt'  => '&bull;',
		'size' => array( 9, 9 ),
	),
	'bullet_empty' => array(
		'file' => $rsc_subdir.'icons/bullet_empty.png',
		'alt'  => '&nbsp;',
		'size' => array( 9, 9 ),
	),
	'bullet_red' => array(
		'file' => $rsc_subdir.'icons/bullet_red.gif',
		'alt'  => '&nbsp;',
		'size' => array( 9, 9 ),
	),

	'activate' => array(
		'file' => $rsc_subdir.'icons/bullet_activate.png',
		'alt'  => /* TRANS: Short for "Activate(d)" */ T_('Act.'),
		'legend' => T_('Activate'),
		'size' => array( 17, 17 ),
	),
	'deactivate' => array(
		'file' => $rsc_subdir.'icons/bullet_deactivate.png',
		'alt'  => /* TRANS: Short for "Deactivate(d)" */ T_('Deact.'),
		'legend' => T_('Deactivate'),
		'size' => array( 17, 17 ),
	),
	'enabled' => array(
		'file' => $rsc_subdir.'icons/bullet_full.png',
		'alt'  => /* TRANS: Short for "Activate(d)" */ T_('Act.'),
		'legend' => T_('Activated'),
		'size' => array( 9, 9 ),
	),
	'disabled' => array(
		'file' => $rsc_subdir.'icons/bullet_empty.png',
		'alt'  => /* TRANS: Short for "Deactivate(d)" */ T_('Deact.'),
		'legend' => T_('Deactivated'),
		'size' => array( 9, 9 ),
	),

	'link' => array(
		'file' => $rsc_subdir.'icons/chain_link.gif',
		/* TRANS: Link + space => verb (not noun) */ 'alt' => T_('Link '),
		'size' => array( 14, 14 ),
	),
	'unlink' => array(
		'file' => $rsc_subdir.'icons/chain_unlink.gif',
		'alt'  => T_('Unlink'),
		'size' => array( 14, 14 ),
	),

	'calendar' => array(
		'file' => $rsc_subdir.'icons/calendar.gif',
		'alt'  => T_('Calendar'),
		'size' => array( 16, 15 ),
	),

	'parent_childto_arrow' => array(
		'file' => $rsc_subdir.'icons/parent_childto_arrow.png',
		'alt'  => T_('+'),
		'size' => array( 14, 17 ),
	),

	'help' => array(
		'file' => $rsc_subdir.'icons/icon_question.gif',
		'alt'  => T_('Help'),
		'size' => array( 15, 15 ),
	),
	'webhelp' => array(
		'file' => $rsc_subdir.'icons/icon_help.gif',
		'alt'  => T_('Help'),
		'size' => array( 15, 15 ),
	),
	'permalink' => array(
		'file' => $rsc_subdir.'icons/minipost.gif',
		'alt'  => T_('Permalink'),
		'size' => array( 12, 9 ),
	),
	'history' => array(
		'file' => $rsc_subdir.'icons/clock.png',
		'alt'  => T_('History'),
		'size' => array( 15, 15 ),
	),

	'file_allowed' => array(
		'file' => $rsc_subdir.'icons/unlocked.gif',
		'alt'  => T_( 'Allowed' ),
		'size' => array( 16, 14 ),
	),
	'file_not_allowed' => array(
		'file' => $rsc_subdir.'icons/locked.gif',
		'alt'  => T_( 'Blocked' ),
		'size' => array( 16, 14 ),
	),

	'comments' => array(
		'file' => $rsc_subdir.'icons/comments.gif',
		'alt'  => T_('Comments'),
		'size' => array( 15, 16 ),
	),
	'nocomment' => array(
		'file' => $rsc_subdir.'icons/nocomment.gif',
		'alt'  => T_('Comments'),
		'size' => array( 15, 16 ),
	),

	'move_up' => array(
		'file' => $rsc_subdir.'icons/move_up.gif',
		'rollover' => true,
		'alt'  => T_( 'Up' ),
		'size' => array( 12, 13 ),
	),
	'move_down' => array(
		'file' => $rsc_subdir.'icons/move_down.gif',
		'rollover' => true,
		'alt'  => T_( 'Down'),
		'size' => array( 12, 13 ),
	),
	'nomove_up' => array(
		'file' => $rsc_subdir.'icons/nomove_up.gif',
		'alt'  => T_( 'Sort by order' ),
		'size' => array( 12, 13 ),
	),
	'nomove_down' => array(
		'file' => $rsc_subdir.'icons/nomove_down.gif',
		'alt'  => T_( 'Sort by order' ),
		'size' => array( 12, 13 ),
	),
	'nomove' => array(
		'file' => $rsc_subdir.'icons/nomove.gif',
		'size' => array( 12, 13 ),
	),

	'assign' => array(
		'file' => $rsc_subdir.'icons/handpoint13.gif',
		'alt'  => T_('Assigned to'),
		'size' => array( 27, 13 ),
	),
	'check_all' => array(
		'file' => $rsc_subdir.'icons/check_all.gif',
		'alt'  => T_('Check all'),
		'size' => array( 17, 17 ),
	),
	'uncheck_all' => array(
		'file' => $rsc_subdir.'icons/uncheck_all.gif',
		'alt'  => T_('Uncheck all'),
		'size' => array( 17, 17 ),
	),

	'reset_filters' => array(
		'file' => $rsc_subdir.'icons/reset_filter.gif',
		'alt'  => T_('Reset all filters'),
		'size' => array( 16, 16 ),
	),

	'allowback' => array(
		'file' => $rsc_subdir.'icons/tick.gif',
		'alt'	 => T_('Allow back'),
		'size' => array( 13, 13 ),
	),
	'ban' => array(
		'file' => $rsc_subdir.'icons/noicon.gif', // TODO: make this transparent
		'alt'  => /* TRANS: Abbrev. */ T_('Ban'),
		'size' => array( 13, 13 ),
	),
	'play' => array(
		'file' => $rsc_subdir.'icons/play.png',
		'alt'  => '&gt;',  // used to write an e-mail, visit site or contact through IM
		'size' => array( 14, 14 ),
	),
);

?>