<?php
/**
 * This file provides icon definitions through a function.
 *
 * Will resolve translations at runtime and consume less memory than a table.
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Get icon according to an item.
 *
 * @param string icon name/key
 * @return array array( 'file', 'alt', 'size', 'class', 'rollover' )
 */
function get_icon_info($name)
{
	global $rsc_subdir;

	/*
fp> does this block really make sense? (was commented out already)
	global $Plugins;
	if( $r = $Plugins->trigger_event_first_return('GetIconInfo', array('name'=>$name)) )
	{
		return $r['plugin_return'];
	}
	*/

	switch($name)
	{
		case 'pixel': return array(
			'file' => $rsc_subdir.'icons/blank.gif',
			'alt'  => '',
			'size' => array( 1, 1 ),
		);

		case 'folder': return array( // icon for folders
			'file' => $rsc_subdir.'icons/fileicons/folder.gif',
			'alt'  => T_('Folder'),
			'size' => array( 16, 15 ),
		);
		case 'file_unknown': return array(  // icon for unknown files
			'file' => $rsc_subdir.'icons/fileicons/default.png',
			'alt'  => T_('Unknown file'),
			'size' => array( 16, 16 ),
		);
		case 'file_empty': return array(    // empty file
			'file' => $rsc_subdir.'icons/fileicons/empty.png',
			'alt'  => T_('Empty file'),
			'size' => array( 16, 16 ),
		);
		case 'folder_parent': return array( // go to parent directory
			'file' => $rsc_subdir.'icons/up.gif',
			'alt'  => T_('Parent folder'),
			'size' => array( 16, 15 ),
		);
		case 'folder_home': return array(   // home folder
			'file' => $rsc_subdir.'icons/folder_home2.png',
			'alt'  => T_('Home folder'),
			'size' => array( 16, 16 ),
		);
		case 'file_edit': return array(     // edit a file
			'file' => $rsc_subdir.'icons/edit.png',
			'alt'  => T_('Edit'),
			'size' => array( 16, 16 ),
		);
		case 'file_copy': return array(     // copy a file/folder
			'file' => $rsc_subdir.'icons/filecopy.png',
			'alt'  => T_('Copy'),
			'size' => array( 16, 16 ),
		);
		case 'file_move': return array(     // move a file/folder
			'file' => $rsc_subdir.'icons/filemove.png',
			'alt'  => T_('Move'),
			'size' => array( 16, 16 ),
		);
		case 'file_rename': return array(   // rename a file/folder
			'file' => $rsc_subdir.'icons/filerename.png',
			'alt'  => T_('Rename'),
			'size' => array( 16, 16 ),
		);
		case 'file_delete': return array(   // delete a file/folder
			'file' => $rsc_subdir.'icons/filedelete.png',
			'alt'  => T_('Del'),
			'legend'=>T_('Delete'),
			'size' => array( 16, 16 ),
		);
		case 'file_perms': return array(    // edit permissions of a file
			'file' => $rsc_subdir.'icons/fileperms.gif',
			'alt'  => T_('Permissions'),
			'size' => array( 16, 16 ),
		);


		case 'ascending': return array(     // ascending sort order
			'file' => $rsc_subdir.'icons/ascending.gif',
			'alt'  => /* TRANS: Short (alt tag) for "Ascending" */ T_('A'),
			'size' => array( 15, 15 ),
		);
		case 'descending': return array(    // descending sort order
			'file' => $rsc_subdir.'icons/descending.gif',
			'alt'  => /* TRANS: Short (alt tag) for "Descending" */ T_('D'),
			'size' => array( 15, 15 ),
		);

		case 'window_new': return array(    // open in a new window
			'file' => $rsc_subdir.'icons/window_new.png',
			'alt'  => T_('New window'),
			'size' => array( 15, 13 ),
		);


		case 'file_word': return array(
			'ext'  => '\.(s[txd]w|doc|rtf)',
			'file' => $rsc_subdir.'icons/fileicons/wordprocessing.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_image': return array(
			'ext'  => '\.(gif|png|jpe?g)',
			'file' => $rsc_subdir.'icons/fileicons/image2.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_www': return array(
			'ext'  => '\.html?',
			'file' => $rsc_subdir.'icons/fileicons/www.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_log': return array(
			'ext'  => '\.log',
			'file' => $rsc_subdir.'icons/fileicons/log.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_sound': return array(
			'ext'  => '\.(mp3|ogg|wav)',
			'file' => $rsc_subdir.'icons/fileicons/sound.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_video': return array(
			'ext'  => '\.(mpe?g|avi)',
			'file' => $rsc_subdir.'icons/fileicons/video.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_message': return array(
			'ext'  => '\.msg',
			'file' => $rsc_subdir.'icons/fileicons/message.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_document': return array(
			'ext'  => '\.pdf',
			'file' => $rsc_subdir.'icons/fileicons/pdf-document.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_php': return array(
			'ext'  => '\.php[34]?',
			'file' => $rsc_subdir.'icons/fileicons/php.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_encrypted': return array(
			'ext'  => '\.(pgp|gpg)',
			'file' => $rsc_subdir.'icons/fileicons/encrypted.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_tar': return array(
			'ext'  => '\.tar',
			'file' => $rsc_subdir.'icons/fileicons/tar.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_tgz': return array(
			'ext'  => '\.tgz',
			'file' => $rsc_subdir.'icons/fileicons/tgz.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_document': return array(
			'ext'  => '\.te?xt',
			'file' => $rsc_subdir.'icons/fileicons/document.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_pk': return array(
			'ext'  => '\.(zip|rar)',
			'file' => $rsc_subdir.'icons/fileicons/pk.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);


		case 'expand': return array(
			'file' => $rsc_subdir.'icons/expand.gif',
			'alt'  => '+',
			'legend' => T_('Expand'),
			'size' => array( 15, 15 ),
		);
		case 'collapse': return array(
			'file' => $rsc_subdir.'icons/collapse.gif',
			'alt'  => '-',
			'legend' => T_('Collapse'),
			'size' => array( 15, 15 ),
		);
		case 'noexpand': return array(
			'file' => $rsc_subdir.'icons/blank.gif',
			'alt'  => '&nbsp;',
			'size' => array( 15, 15 ),
		);

		case 'reload': return array(
			'file' => $rsc_subdir.'icons/reload.png',
			'alt'  => T_('Reload'),
			'size' => array( 16, 16 ),
		);
		case 'download': return array(
			'file' => $rsc_subdir.'icons/download_manager.png',
			'alt'  => T_('Download'),
			'size' => array( 16, 16 ),
		);


		case 'warning': return array(
			'file' => $rsc_subdir.'icons/warning.png', // TODO: not really transparent at its borders
			'alt'  => T_('Warning'),
			'size' => array( 16, 16 ),
		);

		case 'info': return array(
			'file' => $rsc_subdir.'icons/info.gif',
			'alt'  => T_('Info'),
			'size' => array( 16, 16 ),
		);
		case 'email': return array(
			'file' => $rsc_subdir.'icons/envelope.gif',
			'alt'  => T_('Email'),
			'size' => array( 13, 10 ),
		);
		case 'www': return array(   /* user's web site, plugin's help url */
			'file' => $rsc_subdir.'icons/url.gif',
			'alt'  => T_('WWW'),
			'legend' => T_('Website'),
			'size' => array( 34, 17 ),
		);

		case 'new': return array(
			'file' => $rsc_subdir.'icons/new.gif',
			'rollover' => true,
			'alt'  => T_('New'),
			'size' => array( 16, 15 ),
		);
		case 'copy': return array(
			'file' => $rsc_subdir.'icons/copy.gif',
			'alt'  => T_('Copy'),
			'size' => array( 14, 15 ),
		);
		case 'edit': return array(
			'file' => $rsc_subdir.'icons/edit.gif',
			'alt'  => T_('Edit'),
			'size' => array( 16, 15 ),
		);
		case 'properties': return array(
			'file' => $rsc_subdir.'icons/properties.png',
			'alt'  => T_('Properties'),
			'size' => array( 18, 13 ),
		);
		case 'publish': return array(
			'file' => $rsc_subdir.'icons/publish.gif',
			'alt'  => T_('Publish'),
			'size' => array( 12, 15 ),
		);
		case 'deprecate': return array(
			'file' => $rsc_subdir.'icons/deprecate.gif',
			'alt'  => T_('Deprecate'),
			'size' => array( 12, 15 ),
		);
		case 'locate': return array(
			'file' => $rsc_subdir.'icons/target.gif',
			'alt'  => T_('Locate'),
			'size' => array( 15, 15 ),
		);
		case 'delete': return array(
			'file' => $rsc_subdir.'icons/delete.gif',
			'alt'  => T_('Del'),
			'legend' => T_('Delete'),
			'size' => array( 15, 15 ),
		);
		case 'close': return array(
			'file' => $rsc_subdir.'icons/close.gif',
			'rollover' => true,
			'alt' => T_('Close'),
			'size' => array( 14, 14 ),
		);


		case 'increase': return array(
			'file' => $rsc_subdir.'icons/increase.gif',
			'rollover' => true,
			'alt' => T_('+'),
			'size' => array( 15, 15 ),
		);
		case 'decrease': return array(
			'file' => $rsc_subdir.'icons/decrease.gif',
			'rollover' => true,
			'alt' => T_('-'),
			'size' => array( 15, 15 ),
		);

		case 'bullet_full': return array(
			'file' => $rsc_subdir.'icons/bullet_full.png',
			'alt'  => '&bull;',
			'size' => array( 9, 9 ),
		);
		case 'bullet_empty': return array(
			'file' => $rsc_subdir.'icons/bullet_empty.png',
			'alt'  => '&nbsp;',
			'size' => array( 9, 9 ),
		);
		case 'bullet_red': return array(
			'file' => $rsc_subdir.'icons/bullet_red.gif',
			'alt'  => '&nbsp;',
			'size' => array( 9, 9 ),
		);

		case 'activate': return array(
			'file' => $rsc_subdir.'icons/bullet_activate.png',
			'alt'  => /* TRANS: Short for "Activate(d)" */ T_('Act.'),
			'legend' => T_('Activate'),
			'size' => array( 17, 17 ),
		);
		case 'deactivate': return array(
			'file' => $rsc_subdir.'icons/bullet_deactivate.png',
			'alt'  => /* TRANS: Short for "Deactivate(d)" */ T_('Deact.'),
			'legend' => T_('Deactivate'),
			'size' => array( 17, 17 ),
		);
		case 'enabled': return array(
			'file' => $rsc_subdir.'icons/bullet_full.png',
			'alt'  => /* TRANS: Short for "Activate(d)" */ T_('Act.'),
			'legend' => T_('Activated'),
			'size' => array( 9, 9 ),
		);
		case 'disabled': return array(
			'file' => $rsc_subdir.'icons/bullet_empty.png',
			'alt'  => /* TRANS: Short for "Deactivate(d)" */ T_('Deact.'),
			'legend' => T_('Deactivated'),
			'size' => array( 9, 9 ),
		);

		case 'link': return array(
			'file' => $rsc_subdir.'icons/chain_link.gif',
			/* TRANS: Link + space => verb (not noun) */ 'alt' => T_('Link '),
			'size' => array( 14, 14 ),
		);
		case 'unlink': return array(
			'file' => $rsc_subdir.'icons/chain_unlink.gif',
			'alt'  => T_('Unlink'),
			'size' => array( 14, 14 ),
		);

		case 'calendar': return array(
			'file' => $rsc_subdir.'icons/calendar.gif',
			'alt'  => T_('Calendar'),
			'size' => array( 16, 15 ),
		);

		case 'parent_childto_arrow': return array(
			'file' => $rsc_subdir.'icons/parent_childto_arrow.png',
			'alt'  => T_('+'),
			'size' => array( 14, 17 ),
		);

		case 'help': return array(
			'file' => $rsc_subdir.'icons/help-browser.png',
			'alt'  => T_('Help'),
			'size' => array( 16, 16 ),
		);
		case 'webhelp': return array(
			'file' => $rsc_subdir.'icons/icon_help.gif',
			'alt'  => T_('Help'),
			'size' => array( 15, 15 ),
		);
		case 'permalink': return array(
			'file' => $rsc_subdir.'icons/minipost.gif',
			'alt'  => T_('Permalink'),
			'size' => array( 12, 9 ),
		);
		case 'history': return array(
			'file' => $rsc_subdir.'icons/clock.png',
			'alt'  => T_('History'),
			'size' => array( 15, 15 ),
		);

		case 'file_allowed': return array(
			'file' => $rsc_subdir.'icons/unlocked.gif',
			'alt'  => T_( 'Allowed' ),
			'size' => array( 16, 14 ),
		);
		case 'file_not_allowed': return array(
			'file' => $rsc_subdir.'icons/locked.gif',
			'alt'  => T_( 'Blocked' ),
			'size' => array( 16, 14 ),
		);

		case 'comments': return array(
			'file' => $rsc_subdir.'icons/comments.gif',
			'alt'  => T_('Comments'),
			'size' => array( 15, 16 ),
		);
		case 'nocomment': return array(
			'file' => $rsc_subdir.'icons/nocomment.gif',
			'alt'  => T_('Comments'),
			'size' => array( 15, 16 ),
		);

		case 'move_up': return array(
			'file' => $rsc_subdir.'icons/move_up.gif',
			'rollover' => true,
			'alt'  => T_( 'Up' ),
			'size' => array( 12, 13 ),
		);
		case 'move_down': return array(
			'file' => $rsc_subdir.'icons/move_down.gif',
			'rollover' => true,
			'alt'  => T_( 'Down'),
			'size' => array( 12, 13 ),
		);
		case 'nomove_up': return array(
			'file' => $rsc_subdir.'icons/nomove_up.gif',
			'alt'  => T_( 'Sort by order' ),
			'size' => array( 12, 13 ),
		);
		case 'nomove_down': return array(
			'file' => $rsc_subdir.'icons/nomove_down.gif',
			'alt'  => T_( 'Sort by order' ),
			'size' => array( 12, 13 ),
		);
		case 'nomove': return array(
			'file' => $rsc_subdir.'icons/nomove.gif',
			'size' => array( 12, 13 ),
		);

		case 'assign': return array(
			'file' => $rsc_subdir.'icons/handpoint13.gif',
			'alt'  => T_('Assigned to'),
			'size' => array( 27, 13 ),
		);
		case 'check_all': return array(
			'file' => $rsc_subdir.'icons/check_all.gif',
			'alt'  => T_('Check all'),
			'size' => array( 17, 17 ),
		);
		case 'uncheck_all': return array(
			'file' => $rsc_subdir.'icons/uncheck_all.gif',
			'alt'  => T_('Uncheck all'),
			'size' => array( 17, 17 ),
		);

		case 'reset_filters': return array(
			'file' => $rsc_subdir.'icons/reset_filter.gif',
			'alt'  => T_('Reset all filters'),
			'size' => array( 16, 16 ),
		);

		case 'allowback': return array(
			'file' => $rsc_subdir.'icons/tick.gif',
			'alt'	 => T_('Allow back'),
			'size' => array( 13, 13 ),
		);
		case 'ban': return array(
			'file' => $rsc_subdir.'icons/noicon.gif', // TODO: make this transparent
			'alt'  => /* TRANS: Abbrev. */ T_('Ban'),
			'size' => array( 13, 13 ),
		);
		case 'play': return array( // used to write an e-mail, visit site or contact through IM
			'file' => $rsc_subdir.'icons/play.png',
			'alt'  => '&gt;',
			'size' => array( 14, 14 ),
		);
	}
}

/*
 * $Log$
 * Revision 1.48  2006/11/28 02:52:26  fplanque
 * doc
 *
 * Revision 1.47  2006/11/26 01:42:08  fplanque
 * doc
 *
 */
?>