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
 * @return array array( 'file' (relative to $rsc_path/$rsc_url), 'alt', 'size', 'class', 'rollover' )
 */
function get_icon_info($name)
{
	/*
	 * dh> Idea:
	* fp> does not make sense to me. Plugins should do their own icons without a bloated event. Also if we allow something to replace existing icons it should be a skin (either front or admin skin) and some overloaded/overloadable get_skin_icon()/get_admin_icon() should be provided there.
	global $Plugins;
	if( $r = $Plugins->trigger_event_first_return('GetIconInfo', array('name'=>$name)) )
	{
		return $r['plugin_return'];
	}
	*/

	switch($name)
	{
		case 'pixel': return array(
			'file' => 'icons/blank.gif',
			'alt'  => '',
			'size' => array( 1, 1 ),
		);

		case 'dropdown': return array(
			'file' => 'icons/dropdown.gif',
			'alt'  => '¤',
			'size' => array( 11, 8 ),
		);
		case 'switch-to-admin': return array(
			'file' => 'icons/switch-to-admin.gif',
			'alt'  => /* TRANS: short for "Switch to _A_dmin" */ T_('Adm'),
			'size' => array( 13, 14 ),
		);
		case 'switch-to-blog': return array(
			'file' => 'icons/switch-to-blog.gif',
			'alt'  => /* TRANS: short for "Switch to _B_log" */ T_('Blg'),
			'size' => array( 13, 14 ),
		);

		case 'folder': return array( // icon for folders
			'file' => 'icons/fileicons/folder.gif',
			'alt'  => T_('Folder'),
			'size' => array( 16, 15 ),
		);
		case 'file_unknown': return array(  // icon for unknown files
			'file' => 'icons/fileicons/default.png',
			'alt'  => T_('Unknown file'),
			'size' => array( 16, 16 ),
		);
		case 'file_empty': return array(    // empty file
			'file' => 'icons/fileicons/empty.png',
			'alt'  => T_('Empty file'),
			'size' => array( 16, 16 ),
		);
		case 'folder_parent': return array( // go to parent directory
			'file' => 'icons/up.gif',
			'alt'  => T_('Parent folder'),
			'size' => array( 16, 15 ),
		);
		case 'folder_home': return array(   // home folder
			'file' => 'icons/folder_home2.png',
			'alt'  => T_('Home folder'),
			'size' => array( 16, 16 ),
		);
		case 'file_edit': return array(     // edit a file
			'file' => 'icons/edit.png',
			'alt'  => T_('Edit'),
			'size' => array( 16, 16 ),
		);
		case 'file_copy': return array(     // copy a file/folder
			'file' => 'icons/filecopy.png',
			'alt'  => T_('Copy'),
			'size' => array( 16, 16 ),
		);
		case 'file_move': return array(     // move a file/folder
			'file' => 'icons/filemove.png',
			'alt'  => T_('Move'),
			'size' => array( 16, 16 ),
		);
		case 'file_rename': return array(   // rename a file/folder
			'file' => 'icons/filerename.png',
			'alt'  => T_('Rename'),
			'size' => array( 16, 16 ),
		);
		case 'file_delete': return array(   // delete a file/folder
			'file' => 'icons/filedelete.png',
			'alt'  => T_('Del'),
			'legend'=>T_('Delete'),
			'size' => array( 16, 16 ),
		);
		case 'file_perms': return array(    // edit permissions of a file
			'file' => 'icons/fileperms.gif',
			'alt'  => T_('Permissions'),
			'size' => array( 16, 16 ),
		);


		case 'ascending': return array(     // ascending sort order
			'file' => 'icons/ascending.gif',
			'alt'  => /* TRANS: Short (alt tag) for "Ascending" */ T_('A'),
			'size' => array( 15, 15 ),
		);
		case 'descending': return array(    // descending sort order
			'file' => 'icons/descending.gif',
			'alt'  => /* TRANS: Short (alt tag) for "Descending" */ T_('D'),
			'size' => array( 15, 15 ),
		);

		case 'window_new': return array(    // open in a new window
			'file' => 'icons/window_new.png',
			'alt'  => T_('New window'),
			'size' => array( 15, 13 ),
		);


		case 'file_word': return array(
			'ext'  => '\.(s[txd]w|doc|rtf)',
			'file' => 'icons/fileicons/wordprocessing.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_image': return array(
			'ext'  => '\.(gif|png|jpe?g)',
			'file' => 'icons/fileicons/image2.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_www': return array(
			'ext'  => '\.html?',
			'file' => 'icons/fileicons/www.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_log': return array(
			'ext'  => '\.log',
			'file' => 'icons/fileicons/log.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_sound': return array(
			'ext'  => '\.(mp3|ogg|wav)',
			'file' => 'icons/fileicons/sound.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_video': return array(
			'ext'  => '\.(mpe?g|avi)',
			'file' => 'icons/fileicons/video.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_message': return array(
			'ext'  => '\.msg',
			'file' => 'icons/fileicons/message.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_document': return array(
			'ext'  => '\.pdf',
			'file' => 'icons/fileicons/pdf-document.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_php': return array(
			'ext'  => '\.php[34]?',
			'file' => 'icons/fileicons/php.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_encrypted': return array(
			'ext'  => '\.(pgp|gpg)',
			'file' => 'icons/fileicons/encrypted.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_tar': return array(
			'ext'  => '\.tar',
			'file' => 'icons/fileicons/tar.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_tgz': return array(
			'ext'  => '\.tgz',
			'file' => 'icons/fileicons/tgz.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_document': return array(
			'ext'  => '\.te?xt',
			'file' => 'icons/fileicons/document.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);
		case 'file_pk': return array(
			'ext'  => '\.(zip|rar)',
			'file' => 'icons/fileicons/pk.png',
			'alt'  => '',
			'size' => array( 16, 16 ),
		);


		case 'expand': return array(
			'file' => 'icons/expand.gif',
			'alt'  => '+',
			'legend' => T_('Expand'),
			'size' => array( 15, 15 ),
		);
		case 'collapse': return array(
			'file' => 'icons/collapse.gif',
			'alt'  => '-',
			'legend' => T_('Collapse'),
			'size' => array( 15, 15 ),
		);

		case 'refresh': return array(
			'file' => 'icons/refresh.png',
			'alt'  => T_('Refresh'),
			'size' => array( 16, 16 ),
		);
		case 'reload': return array(
			'file' => 'icons/reload.gif',
			'alt'  => T_('Reload'),
			'size' => array( 15, 15 ),
		);

		case 'download': return array(
			'file' => 'icons/download_manager.png',
			'alt'  => T_('Download'),
			'size' => array( 16, 16 ),
		);


		case 'warning': return array(
			'file' => 'icons/warning.png', // TODO: not really transparent at its borders
			'alt'  => T_('Warning'),
			'size' => array( 16, 16 ),
		);

		case 'info': return array(
			'file' => 'icons/info.gif',
			'alt'  => T_('Info'),
			'size' => array( 16, 16 ),
		);
		case 'email': return array(
			'file' => 'icons/envelope.gif',
			'alt'  => T_('Email'),
			'size' => array( 13, 10 ),
		);
		case 'www': return array(   /* user's web site, plugin's help url */
			'file' => 'icons/url.gif',
			'alt'  => T_('WWW'),
			'legend' => T_('Website'),
			'size' => array( 34, 17 ),
		);

		case 'new': return array(
			'file' => 'icons/new.gif',
			'rollover' => true,
			'alt'  => T_('New'),
			'size' => array( 16, 15 ),
		);
		case 'copy': return array(
			'file' => 'icons/copy.gif',
			'alt'  => T_('Copy'),
			'size' => array( 14, 15 ),
		);
		case 'edit': return array(
			'file' => 'icons/edit.gif',
			'alt'  => T_('Edit'),
			'size' => array( 16, 15 ),
		);
		case 'properties': return array(
			'file' => 'icons/properties.png',
			'alt'  => T_('Properties'),
			'size' => array( 18, 13 ),
		);
		case 'publish': return array(
			'file' => 'icons/publish.gif',
			'alt'  => T_('Publish'),
			'size' => array( 12, 15 ),
		);
		case 'deprecate': return array(
			'file' => 'icons/deprecate.gif',
			'alt'  => T_('Deprecate'),
			'size' => array( 12, 15 ),
		);
		case 'locate': return array(
			'file' => 'icons/target.gif',
			'alt'  => T_('Locate'),
			'size' => array( 15, 15 ),
		);
		case 'delete': return array(
			'file' => 'icons/delete.gif',
			'alt'  => T_('Del'),
			'legend' => T_('Delete'),
			'size' => array( 15, 15 ),
		);
		case 'close': return array(
			'file' => 'icons/close.gif',
			'rollover' => true,
			'alt' => T_('Close'),
			'size' => array( 14, 14 ),
		);


		case 'increase': return array(
			'file' => 'icons/increase.gif',
			'rollover' => true,
			'alt' => T_('+'),
			'size' => array( 15, 15 ),
		);
		case 'decrease': return array(
			'file' => 'icons/decrease.gif',
			'rollover' => true,
			'alt' => T_('-'),
			'size' => array( 15, 15 ),
		);

		case 'bullet_full': return array(
			'file' => 'icons/bullet_full.png',
			'alt'  => '&bull;',
			'size' => array( 9, 9 ),
		);
		case 'bullet_empty': return array(
			'file' => 'icons/bullet_empty.png',
			'alt'  => '&nbsp;',
			'size' => array( 9, 9 ),
		);
		case 'bullet_red': return array(
			'file' => 'icons/bullet_red.gif',
			'alt'  => '&nbsp;',
			'size' => array( 9, 9 ),
		);

		case 'activate': return array(
			'file' => 'icons/bullet_activate.png',
			'alt'  => /* TRANS: Short for "Activate(d)" */ T_('Act.'),
			'legend' => T_('Activate'),
			'size' => array( 17, 17 ),
		);
		case 'deactivate': return array(
			'file' => 'icons/bullet_deactivate.png',
			'alt'  => /* TRANS: Short for "Deactivate(d)" */ T_('Deact.'),
			'legend' => T_('Deactivate'),
			'size' => array( 17, 17 ),
		);
		case 'enabled': return array(
			'file' => 'icons/bullet_full.png',
			'alt'  => /* TRANS: Short for "Activate(d)" */ T_('Act.'),
			'legend' => T_('Activated'),
			'size' => array( 9, 9 ),
		);
		case 'disabled': return array(
			'file' => 'icons/bullet_empty.png',
			'alt'  => /* TRANS: Short for "Deactivate(d)" */ T_('Deact.'),
			'legend' => T_('Deactivated'),
			'size' => array( 9, 9 ),
		);

		case 'link': return array(
			'file' => 'icons/chain_link.gif',
			/* TRANS: Link + space => verb (not noun) */ 'alt' => T_('Link '),
			'size' => array( 14, 14 ),
		);
		case 'unlink': return array(
			'file' => 'icons/chain_unlink.gif',
			'alt'  => T_('Unlink'),
			'size' => array( 14, 14 ),
		);

		case 'calendar': return array(
			'file' => 'icons/calendar.gif',
			'alt'  => T_('Calendar'),
			'size' => array( 16, 15 ),
		);

		case 'parent_childto_arrow': return array(
			'file' => 'icons/parent_childto_arrow.png',
			'alt'  => T_('+'),
			'size' => array( 14, 17 ),
		);

		case 'help': return array(
			'file' => 'icons/help-browser.png',
			'alt'  => T_('Help'),
			'size' => array( 16, 16 ),
		);
		case 'manual': return array(
			'file' => 'icons/manual.gif',
			'rollover' => true,
			'alt'  => T_('Help'),
			'legend' => T_('Online Manual'),
			'size' => array( 16, 15 ),
		);
		case 'permalink': return array(
			'file' => 'icons/minipost.gif',
			'alt'  => T_('Permalink'),
			'size' => array( 11, 13 ),
		);
		case 'history': return array(
			'file' => 'icons/clock.png',
			'alt'  => T_('History'),
			'size' => array( 15, 15 ),
		);

		case 'file_allowed': return array(
			'file' => 'icons/unlocked.gif',
			'alt'  => T_( 'Allowed' ),
			'size' => array( 16, 14 ),
		);
		case 'file_not_allowed': return array(
			'file' => 'icons/locked.gif',
			'alt'  => T_( 'Blocked' ),
			'size' => array( 16, 14 ),
		);

		case 'comments': return array(
			'file' => 'icons/comments.gif',
			'alt'  => T_('Comments'),
			'size' => array( 15, 16 ),
		);
		case 'nocomment': return array(
			'file' => 'icons/nocomment.gif',
			'alt'  => T_('Comments'),
			'size' => array( 15, 16 ),
		);

		case 'move_up': return array(
			'file' => 'icons/move_up.gif',
			'rollover' => true,
			'alt'  => T_( 'Up' ),
			'size' => array( 12, 13 ),
		);
		case 'move_down': return array(
			'file' => 'icons/move_down.gif',
			'rollover' => true,
			'alt'  => T_( 'Down'),
			'size' => array( 12, 13 ),
		);
		case 'nomove_up': return array(
			'file' => 'icons/nomove_up.gif',
			'alt'  => T_( 'Sort by order' ),
			'size' => array( 12, 13 ),
		);
		case 'nomove_down': return array(
			'file' => 'icons/nomove_down.gif',
			'alt'  => T_( 'Sort by order' ),
			'size' => array( 12, 13 ),
		);
		case 'nomove': return array(
			'file' => 'icons/nomove.gif',
			'size' => array( 12, 13 ),
		);

		case 'assign': return array(
			'file' => 'icons/handpoint13.gif',
			'alt'  => T_('Assigned to'),
			'size' => array( 27, 13 ),
		);
		case 'check_all': return array(
			'file' => 'icons/check_all.gif',
			'alt'  => T_('Check all'),
			'size' => array( 17, 17 ),
		);
		case 'uncheck_all': return array(
			'file' => 'icons/uncheck_all.gif',
			'alt'  => T_('Uncheck all'),
			'size' => array( 17, 17 ),
		);

		case 'reset_filters': return array(
			'file' => 'icons/reset_filter.gif',
			'alt'  => T_('Reset all filters'),
			'size' => array( 16, 16 ),
		);

		case 'allowback': return array(
			'file' => 'icons/tick.gif',
			'alt'	 => T_('Allow back'),
			'size' => array( 13, 13 ),
		);
		case 'ban': return array(
			'file' => 'icons/noicon.gif', // TODO: make this transparent
			'alt'  => /* TRANS: Abbrev. */ T_('Ban'),
			'size' => array( 13, 13 ),
		);
		case 'play': return array( // used to write an e-mail, visit site or contact through IM
			'file' => 'icons/play.png',
			'alt'  => '&gt;',
			'size' => array( 14, 14 ),
		);

		case 'feed': return array(
			'file' => 'icons/feed-icon-16x16.gif',
			'alt'	 => T_('XML Feed'),
			'size' => array( 16, 16 ),
		);

		case 'star_on': return array(
			'file' => 'icons/star_small.gif',
			'alt'	 => '*',
			'size' => array( 12, 12 ),
		);
		case 'star_half': return array(
			'file' => 'icons/star_small_half.gif',
			'alt'	 => '+',
			'size' => array( 12, 12 ),
		);
		case 'star_off': return array(
			'file' => 'icons/star_small_gray.gif',
			'alt'	 => '-',
			'size' => array( 12, 12 ),
		);
	}
}

/*
 * $Log$
 * Revision 1.67  2008/02/14 02:19:50  fplanque
 * cleaned up stats
 *
 * Revision 1.66  2008/01/17 17:42:09  fplanque
 * minor
 *
 * Revision 1.65  2008/01/16 23:55:48  blueyed
 * todo about trans conflict!
 *
 * Revision 1.64  2007/11/24 15:23:13  fplanque
 * minor
 *
 * Revision 1.63  2007/11/22 22:53:14  blueyed
 * get_icon_info(): relative to $rsc_url/$rsc_path (instead of $rsc_subdir)
 *
 * Revision 1.62  2007/11/02 01:42:16  fplanque
 * comment ratings
 *
 * Revision 1.61  2007/09/12 21:00:30  fplanque
 * UI improvements
 *
 * Revision 1.60  2007/09/08 23:20:14  fplanque
 * gettext update
 *
 * Revision 1.59  2007/09/08 19:31:28  fplanque
 * cleanup of XML feeds for comments on individual posts.
 *
 * Revision 1.58  2007/05/23 22:45:07  blueyed
 * TRANS comments
 *
 * Revision 1.57  2007/03/24 20:35:57  fplanque
 * minor
 *
 * Revision 1.56  2007/03/04 05:24:52  fplanque
 * some progress on the toolbar menu
 *
 * Revision 1.55  2007/01/29 09:24:41  fplanque
 * icon stuff
 *
 * Revision 1.54  2007/01/23 22:30:14  fplanque
 * empty icons cleanup
 *
 * Revision 1.53  2007/01/07 18:42:35  fplanque
 * cleaned up reload/refresh icons & links
 *
 * Revision 1.52  2006/12/26 00:55:58  fplanque
 * wording
 *
 * Revision 1.51  2006/12/07 20:03:31  fplanque
 * Woohoo! File editing... means all skin editing.
 *
 * Revision 1.50  2006/12/02 22:58:12  fplanque
 * minor
 *
 */
?>
