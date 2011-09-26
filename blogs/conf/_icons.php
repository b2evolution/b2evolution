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
			'size' => array( 1, 1 )
		);

		case 'dropdown': return array(
			'alt'  => '&darr;',
			'size' => array( 11, 8 ),
			'xy' => array( 16, 0 )
		);
		case 'switch-to-admin': return array(
			'alt'  => /* TRANS: short for "Switch to _A_dmin" */ T_('Adm'),
			'size' => array( 13, 14 ),
			'xy' => array( 32, 0 )
		);
		case 'switch-to-blog': return array(
			'alt'  => /* TRANS: short for "Switch to _B_log" */ T_('Blg'),
			'size' => array( 13, 14 ),
			'xy' => array( 48, 0 )
		);

		case 'folder': return array( // icon for folders
			'alt'  => T_('Folder'),
			'size' => array( 16, 15 ),
			'xy' => array( 0, 16 )
		);
		case 'file_unknown': return array(  // icon for unknown files
			'alt'  => T_('Unknown file'),
			'size' => array( 16, 16 ),
			'xy' => array( 16, 16 )
		);
		case 'file_empty': return array(    // empty file
			'alt'  => T_('Empty file'),
			'size' => array( 16, 16 ),
			'xy' => array( 32, 16 )
		);
		case 'folder_parent': return array( // go to parent directory
			'alt'  => T_('Parent folder'),
			'size' => array( 16, 15 ),
			'xy' => array( 48, 16 )
		);
		case 'folder_home': return array(   // home folder
			'alt'  => T_('Home folder'),
			'size' => array( 16, 16 ),
			'xy' => array( 64, 16 )
		);
		case 'file_edit': return array(     // edit a file
			'alt'  => T_('Edit'),
			'size' => array( 16, 16 ),
			'xy' => array( 80, 16 )
		);
		case 'file_copy': return array(     // copy a file/folder
			'alt'  => T_('Copy'),
			'size' => array( 16, 16 ),
			'xy' => array( 96, 16 )
		);
		case 'file_move': return array(     // move a file/folder
			'alt'  => T_('Move'),
			'size' => array( 16, 16 ),
			'xy' => array( 112, 16 )
		);
		case 'file_delete': return array(   // delete a file/folder
			'alt'  => T_('Del'),
			'legend'=>T_('Delete'),
			'size' => array( 16, 16 ),
			'xy' => array( 128, 16 )
		);
		case 'file_perms': return array(    // edit permissions of a file
			'alt'  => T_('Permissions'),
			'size' => array( 16, 16 ),
			'xy' => array( 144, 16 )
		);


		case 'ascending': return array(     // ascending sort order
			'alt'  => /* TRANS: Short (alt tag) for "Ascending" */ T_('A'),
			'size' => array( 15, 15 ),
			'xy' => array( 64, 0 )
		);
		case 'descending': return array(    // descending sort order
			'alt'  => /* TRANS: Short (alt tag) for "Descending" */ T_('D'),
			'size' => array( 15, 15 ),
			'xy' => array( 80, 0 )
		);

		case 'window_new': return array(    // open in a new window
			'alt'  => T_('New window'),
			'size' => array( 15, 13 ),
			'xy' => array( 144, 0 )
		);


		case 'file_word': return array(
			'ext'  => '\.(s[txd]w|doc|rtf)',
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 0, 32 )
		);
		case 'file_image': return array(
			'ext'  => '\.(gif|png|jpe?g)',
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 16, 32 )
		);
		case 'file_www': return array(
			'ext'  => '\.html?',
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 32, 32 )
		);
		case 'file_log': return array(
			'ext'  => '\.log',
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 48, 32 )
		);
		case 'file_sound': return array(
			'ext'  => '\.(mp3|ogg|wav)',
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 64, 32 )
		);
		case 'file_video': return array(
			'ext'  => '\.(mpe?g|avi)',
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 80, 32 )
		);
		case 'file_message': return array(
			'ext'  => '\.msg',
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 96, 32 )
		);
		case 'file_document': return array(
			'ext'  => '\.pdf',
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 112, 32 )
		);
		case 'file_php': return array(
			'ext'  => '\.php[34]?',
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 128, 32 )
		);
		case 'file_encrypted': return array(
			'ext'  => '\.(pgp|gpg)',
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 144, 32 )
		);
		case 'file_tar': return array(
			'ext'  => '\.tar',
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 0, 48 )
		);
		case 'file_tgz': return array(
			'ext'  => '\.tgz',
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 16, 48 )
		);
		case 'file_document': return array(
			'ext'  => '\.te?xt',
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 32, 48 )
		);
		case 'file_pk': return array(
			'ext'  => '\.(zip|rar)',
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 48, 48 )
		);


		case 'expand': return array(
			'alt'  => '+',
			'legend' => T_('Expand'),
			'size' => array( 15, 15 ),
			'xy' => array( 96, 0 )
		);
		case 'collapse': return array(
			'alt'  => '-',
			'legend' => T_('Collapse'),
			'size' => array( 15, 15 ),
			'xy' => array( 112, 0 )
		);

		case 'refresh': return array(
			'alt'  => T_('Refresh'),
			'size' => array( 16, 16 ),
			'xy' => array( 64, 48 )
		);
		case 'reload': return array(
			'alt'  => T_('Reload'),
			'size' => array( 15, 15 ),
			'xy' => array( 80, 48 )
		);

		case 'download': return array(
			'alt'  => T_('Download'),
			'size' => array( 16, 16 ),
			'xy' => array( 128, 0 )
		);


		case 'warning': return array( // TODO: not really transparent at its borders
			'alt'  => T_('Warning'),
			'size' => array( 16, 16 ),
			'xy' => array( 96, 48 )
		);

		case 'info': return array(
			'alt'  => T_('Info'),
			'size' => array( 16, 16 ),
			'xy' => array( 112, 48 )
		);
		case 'email': return array(
			'alt'  => T_('Email'),
			'size' => array( 13, 10 ),
			'xy' => array( 128, 48 )
		);
		case 'www': return array(   /* user's web site, plugin's help url */
			'alt'  => T_('WWW'),
			'legend' => T_('Website'),
			'size' => array( 32, 16 ),
			'xy' => array( 128, 128 )
		);

		case 'new': return array(
			'rollover' => true,
			'alt'  => T_('New'),
			'size' => array( 16, 15 ),
			'xy' => array( 0, 64 )
		);
		case 'copy': return array(
			'alt'  => T_('Copy'),
			'size' => array( 14, 15 ),
			'xy' => array( 32, 64 )
		);
		case 'edit': return array(
			'alt'  => T_('Edit'),
			'size' => array( 16, 15 ),
			'xy' => array( 48, 64 )
		);
		case 'properties': return array(
			'alt'  => T_('Properties'),
			'size' => array( 16, 13 ),
			'xy' => array( 64, 64 )
		);
		case 'publish': return array(
			'alt'  => T_('Publish'),
			'size' => array( 12, 15 ),
			'xy' => array( 80, 64 )
		);
		case 'deprecate': return array(
			'alt'  => T_('Deprecate'),
			'size' => array( 12, 15 ),
			'xy' => array( 96, 64 )
		);
		case 'locate': return array(
			'alt'  => T_('Locate'),
			'size' => array( 15, 15 ),
			'xy' => array( 112, 64 )
		);
		case 'delete': return array(
			'alt'  => T_('Del'),
			'legend' => T_('Delete'),
			'size' => array( 15, 15 ),
			'xy' => array( 128, 64 )
		);
		case 'close': return array(
			'rollover' => true,
			'alt' => T_('Close'),
			'size' => array( 14, 14 ),
			'xy' => array( 0, 96 )
		);


		case 'increase': return array(
			'rollover' => true,
			'alt' => T_('+'),
			'size' => array( 15, 15 ),
			'xy' => array( 0, 80 )
		);
		case 'decrease': return array(
			'rollover' => true,
			'alt' => T_('-'),
			'size' => array( 15, 15 ),
			'xy' => array( 32, 80 )
		);

		case 'bullet_full': return array(
			'alt'  => '&bull;',
			'size' => array( 9, 9 ),
			'xy' => array( 32, 96 )
		);
		case 'bullet_empty': return array(
			'alt'  => '&nbsp;',
			'size' => array( 9, 9 ),
			'xy' => array( 48, 96 )
		);
		case 'bullet_red': return array(
			'file' => 'icons/bullet_red.gif',
			'alt'  => '&nbsp;',
			'size' => array( 9, 9 ),
		);

		case 'activate': return array(
			'alt'  => /* TRANS: Short for "Activate(d)" */ T_('Act.'),
			'legend' => T_('Activate'),
			'size' => array( 16, 16 ),
			'xy' => array( 64, 96 )
		);
		case 'deactivate': return array(
			'alt'  => /* TRANS: Short for "Deactivate(d)" */ T_('Deact.'),
			'legend' => T_('Deactivate'),
			'size' => array( 16, 16 ),
			'xy' => array( 80, 96 )
		);
		case 'enabled': return array(
			'alt'  => /* TRANS: Short for "Activate(d)" */ T_('Act.'),
			'legend' => T_('Activated'),
			'size' => array( 9, 9 ),
			'xy' => array( 32, 96 )
		);
		case 'disabled': return array(
			'alt'  => /* TRANS: Short for "Deactivate(d)" */ T_('Deact.'),
			'legend' => T_('Deactivated'),
			'size' => array( 9, 9 ),
			'xy' => array( 48, 96 )
		);

		case 'link': return array(
			/* TRANS: Link + space => verb (not noun) */ 'alt' => T_('Link '),
			'size' => array( 14, 14 ),
			'xy' => array( 96, 96 )
		);
		case 'unlink': return array(
			'alt'  => T_('Unlink'),
			'size' => array( 14, 14 ),
			'xy' => array( 112, 96 )
		);

		case 'calendar': return array(
			'alt'  => T_('Calendar'),
			'size' => array( 16, 15 ),
			'xy' => array( 144, 64 )
		);

		case 'parent_childto_arrow': return array(
			'alt'  => T_('+'),
			'size' => array( 14, 16 ),
			'xy' => array( 16, 128 )
		);

		case 'help': return array(
			'alt'  => T_('Help'),
			'size' => array( 16, 16 ),
			'xy' => array( 32, 128 )
		);
		case 'manual': return array(
			'rollover' => true,
			'alt'  => T_('Help'),
			'legend' => T_('Online Manual'),
			'size' => array( 16, 15 ),
			'xy' => array( 128, 96 )
		);
		case 'permalink': return array(
			'alt'  => T_('Permalink'),
			'size' => array( 11, 13 ),
			'xy' => array( 0, 128 )
		);
		case 'history': return array(
			'alt'  => T_('History'),
			'size' => array( 15, 15 ),
			'xy' => array( 144, 48 )
		);

		case 'file_allowed': return array(
			'alt'  => T_( 'Allowed' ),
			'size' => array( 16, 14 ),
			'xy' => array( 96, 112 )
		);
		case 'file_allowed_registered': return array(
			'alt'  => T_( 'Allowed for registered users' ),
			'size' => array( 16, 14 ),
			'xy' => array( 112, 112 )
		);
		case 'file_not_allowed': return array(
			'alt'  => T_( 'Blocked' ),
			'size' => array( 16, 14 ),
			'xy' => array( 128, 112 )
		);

		case 'comments': return array(
			'alt'  => T_('Comments'),
			'size' => array( 15, 16 ),
			'xy' => array( 0, 112 )
		);
		case 'nocomment': return array(
			'alt'  => T_('Comments'),
			'size' => array( 15, 16 ),
			'xy' => array( 0, 112 )
		);

		case 'move_up': return array(
			'rollover' => true,
			'alt'  => T_( 'Up' ),
			'size' => array( 12, 13 ),
			'xy' => array( 96, 80 )
		);
		case 'move_down': return array(
			'rollover' => true,
			'alt'  => T_( 'Down'),
			'size' => array( 12, 13 ),
			'xy' => array( 64, 80 )
		);
		case 'nomove_up': return array(
			'alt'  => T_( 'Sort by order' ),
			'size' => array( 12, 13 ),
			'xy' => array( 144, 80 )
		);
		case 'nomove_down': return array(
			'alt'  => T_( 'Sort by order' ),
			'size' => array( 12, 13 ),
			'xy' => array( 128, 80 )
		);
		case 'nomove': return array(
			'size' => array( 12, 13 ),
			'xy' => array( 0, 0 )
		);

		case 'assign': return array(
			'alt'  => T_('Assigned to'),
			'size' => array( 27, 13 ),
			'xy' => array( 96, 128 )
		);
		case 'check_all': return array(
			'alt'  => T_('Check all'),
			'size' => array( 16, 16 ),
			'xy' => array( 32, 112 )
		);
		case 'uncheck_all': return array(
			'alt'  => T_('Uncheck all'),
			'size' => array( 16, 16 ),
			'xy' => array( 48, 112 )
		);

		case 'reset_filters': return array(
			'alt'  => T_('Reset all filters'),
			'size' => array( 16, 16 ),
			'xy' => array( 144, 112 )
		);

		case 'allowback': return array(
			'alt'	 => T_('Allow back'),
			'size' => array( 13, 13 ),
			'xy' => array( 48, 128 )
		);
		case 'ban': return array( // TODO: make this transparent
			'alt'  => /* TRANS: Abbrev. */ T_('Ban'),
			'size' => array( 13, 13 ),
			'xy' => array( 64, 128 )
		);
		case 'play': return array( // used to write an e-mail, visit site or contact through IM
			'alt'  => '&gt;',
			'size' => array( 14, 14 ),
			'xy' => array( 80, 128 )
		);

		case 'feed': return array(
			'alt'	 => T_('XML Feed'),
			'size' => array( 16, 16 ),
			'xy' => array( 96, 144 )
		);

		case 'recycle_full': return array(
			'alt'  => T_('Open recycle bin'),
			'size' => array( 16, 16 ),
			'xy' => array( 64, 112 )
		);
		case 'recycle_empty': return array(
			'alt'  => T_('Empty recycle bin'),
			'size' => array( 16, 16 ),
			'xy' => array( 80, 112 )
		);

		case 'vote_spam': return array(
			'alt'  => T_('Mark this comment as spam!'),
			'size' => array( 15, 15 ),
			'xy' => array( 16, 144 )
		);
		case 'vote_spam_disabled': return array(
			'alt'  => T_('Mark this comment as spam!'),
			'size' => array( 15, 15 ),
			'xy' => array( 0, 144 )
		);
		case 'vote_notsure': return array(
			'alt'  => T_('Mark this comment as not sure!'),
			'size' => array( 15, 15 ),
			'xy' => array( 48, 144 )
		);
		case 'vote_notsure_disabled': return array(
			'alt'  => T_('Mark this comment as not sure!'),
			'size' => array( 15, 15 ),
			'xy' => array( 32, 144 )
		);
		case 'vote_ok': return array(
			'alt'  => T_('Mark this comment as OK!'),
			'size' => array( 15, 15 ),
			'xy' => array( 80, 144 )
		);
		case 'vote_ok_disabled': return array(
			'alt'  => T_('Mark this comment as OK!'),
			'size' => array( 15, 15 ),
			'xy' => array( 64, 144 )
		);
	}
}

/*
 * $Log$
 * Revision 1.83  2011/09/26 05:58:56  efy-yurybakh
 * fix icon style in IE
 *
 * Revision 1.82  2011/09/24 13:27:36  efy-yurybakh
 * Change voting buttons
 *
 * Revision 1.81  2011/09/24 05:30:18  efy-yurybakh
 * fp>yura
 *
 * Revision 1.80  2011/09/23 22:37:09  fplanque
 * minor / doc
 *
 * Revision 1.79  2011/09/23 14:01:57  fplanque
 * Quick/temporary fixes so we can work in the meantime
 *
 * Revision 1.78  2011/09/23 11:30:50  efy-yurybakh
 * Make a big sprite with all backoffice icons
 *
 * Revision 1.77  2011/09/22 05:18:46  efy-yurybakh
 * fix ratings CSS
 * remove no longer used star icons
 *
 * Revision 1.76  2011/09/07 00:28:26  sam2kb
 * Replace non-ASCII character in regular expressions with ~
 *
 * Revision 1.75  2011/09/06 18:38:39  sam2kb
 * minor
 *
 * Revision 1.74  2011/09/06 18:33:52  sam2kb
 * minor/removed weird character
 *
 * Revision 1.73  2011/03/10 14:54:18  efy-asimo
 * Allow file types modification & add m4v file type
 *
 * Revision 1.72  2011/02/24 13:11:28  efy-asimo
 * Change recycle icons size
 *
 * Revision 1.71  2011/02/24 07:42:26  efy-asimo
 * Change trashcan to Recycle bin
 *
 * Revision 1.70  2010/01/22 20:20:16  efy-asimo
 * Remove File manager rename file
 *
 * Revision 1.69  2008/03/31 21:13:47  fplanque
 * Reverted ubergeekyness
 *
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
