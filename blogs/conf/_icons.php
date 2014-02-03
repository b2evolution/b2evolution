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
			'alt'  => '',
			'size' => array( 1, 1 ),
			'xy' => array( 0, 0 )
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

		case 'sort_desc_on': return array(
			'alt'  => T_('Descending order'),
			'size' => array( 12, 11 ),
			'xy' => array( 64, 208 )
		);
		case 'sort_asc_on': return array(
			'alt'  => T_('Ascending order'),
			'size' => array( 12, 11 ),
			'xy' => array( 80, 208 )
		);
		case 'sort_desc_off': return array(
			'alt'  => T_('Descending order'),
			'size' => array( 12, 11 ),
			'xy' => array( 96, 208 )
		);
		case 'sort_asc_off': return array(
			'alt'  => T_('Ascending order'),
			'size' => array( 12, 11 ),
			'xy' => array( 112, 208 )
		);

		case 'window_new': return array(    // open in a new window
			'alt'  => T_('New window'),
			'size' => array( 15, 13 ),
			'xy' => array( 144, 0 )
		);


		case 'file_image': return array(
			'ext'  => '\.(gif|png|jpe?g)',
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 16, 32 )
		);
		case 'file_document': return array(
			'ext'  => '\.(txt)',
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 32, 48 )
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
		case 'file_pdf': return array(
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
		case 'file_pk': return array(
			'ext'  => '\.(zip|rar)',
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 48, 48 )
		);
		case 'file_doc': return array(
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 64, 48 )
		);
		case 'file_xls': return array(
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 80, 48 )
		);
		case 'file_ppt': return array(
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 96, 48 )
		);
		case 'file_pps': return array(
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 112, 48 )
		);
		case 'file_zip': return array(
			'alt'  => '',
			'size' => array( 16, 16 ),
			'xy' => array( 128, 48 )
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

		case 'filters_show': return array(
			'alt'  => T_('Expand'),
			'size' => array( 15, 15 ),
			'xy' => array( 64, 16 )
		);
		case 'filters_hide': return array(
			'alt'  => T_('Collapse'),
			'size' => array( 15, 15 ),
			'xy' => array( 80, 16 )
		);

		case 'refresh': return array(
			'alt'  => T_('Refresh'),
			'size' => array( 16, 16 ),
			'xy' => array( 128, 208 )
		);
		case 'reload': return array(
			'alt'  => T_('Reload'),
			'size' => array( 15, 15 ),
			'xy' => array( 144, 208 )
		);

		case 'download': return array(
			'alt'  => T_('Download'),
			'size' => array( 16, 16 ),
			'xy' => array( 128, 0 )
		);
		case 'arrow-down-green': return array(
			'alt'  => T_('Download'),
			'size' => array( 24, 24 ),
			'xy' => array( 96, 272 )
		);


		case 'warning': return array( // TODO: not really transparent at its borders
			'alt'  => T_('Warning'),
			'size' => array( 16, 16 ),
			'xy' => array( 64, 176 )
		);
		case 'warning_yellow': return array(
			'alt'  => T_('Warning'),
			'size' => array( 16, 16 ),
			'xy' => array( 48, 176 )
		);

		case 'info': return array(
			'alt'  => T_('Info'),
			'size' => array( 16, 16 ),
			'xy' => array( 80, 176 )
		);
		case 'email': return array(
			'alt'  => T_('Email'),
			'size' => array( 16, 12 ),
			'xy' => array( 32, 176 )
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
			'xy' => array( 0, 224 )
		);
		case 'xross': return array(
			'alt'  => T_('Del'),
			'size' => array( 13, 13 ),
			'xy' => array( 144, 64 )
		);

		case 'bullet_black':
		case 'bullet_full': return array(
			'alt'  => '&bull;',
			'size' => array( 9, 9 ),
			'xy' => array( 96, 176 )
		);
		case 'bullet_empty': return array(
			'alt'  => '&nbsp;',
			'size' => array( 9, 9 ),
			'xy' => array( 112, 176 )
		);
		case 'bullet_blue': return array(
			'alt'  => '&bull;',
			'size' => array( 9, 9 ),
			'xy' => array( 32, 192 )
		);
		case 'bullet_red': return array(
			'alt'  => '&bull;',
			'size' => array( 9, 9 ),
			'xy' => array( 48, 192 )
		);
		case 'bullet_orange': return array(
			'alt'  => '&bull;',
			'size' => array( 9, 9 ),
			'xy' => array( 64, 192 )
		);
		case 'bullet_green': return array(
			'alt'  => '&bull;',
			'size' => array( 9, 9 ),
			'xy' => array( 80, 192 )
		);
		case 'bullet_yellow': return array(
			'alt'  => '&bull;',
			'size' => array( 9, 9 ),
			'xy' => array( 96, 192 )
		);
		case 'bullet_brown': return array(
			'alt'  => '&bull;',
			'size' => array( 9, 9 ),
			'xy' => array( 112, 192 )
		);
		case 'bullet_white': return array(
			'alt'  => '&bull;',
			'size' => array( 9, 9 ),
			'xy' => array( 0, 192 )
		);
		case 'bullet_magenta': return array(
			'alt'  => '&bull;',
			'size' => array( 9, 9 ),
			'xy' => array( 16, 192 )
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
			'xy' => array( 96, 176 )
		);
		case 'disabled': return array(
			'alt'  => /* TRANS: Short for "Deactivate(d)" */ T_('Deact.'),
			'legend' => T_('Deactivated'),
			'size' => array( 9, 9 ),
			'xy' => array( 112, 176 )
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
			'size' => array( 12, 16 ),
			'xy' => array( 112, 112 )
		);
		case 'file_not_allowed': return array(
			'alt'  => T_( 'Blocked' ),
			'size' => array( 11, 14 ),
			'xy' => array( 128, 112 )
		);

		case 'comments': return array(
			'alt'  => T_('Comments'),
			'size' => array( 15, 16 ),
			'xy' => array( 0, 112 )
		);
		case 'nocomment': return array(
			'alt'  => T_('No comment'),
			'size' => array( 15, 16 ),
			'xy' => array( 16, 112 )
		);

		case 'move_up_blue':
		case 'move_up': return array(
			'rollover' => true,
			'alt'  => T_( 'Up' ),
			'size' => array( 12, 13 ),
			'xy' => array( 96, 80 )
		);
		case 'move_down_blue':
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
		case 'move_left': return array(
			'rollover' => true,
			'alt'  => T_( 'Left' ),
			'size' => array( 13, 12 ),
			'xy' => array( 0, 96 )
		);
		case 'move_right': return array(
			'rollover' => true,
			'alt'  => T_( 'Right'),
			'size' => array( 13, 12 ),
			'xy' => array( 32, 96 )
		);
		case 'move_down_orange': return array(
			'alt'  => T_('Down'),
			'size' => array( 12, 13 ),
			'xy' => array( 80, 80 )
		);
		case 'move_up_orange': return array(
			'alt'  => T_('Up'),
			'size' => array( 12, 13 ),
			'xy' => array( 112, 80 )
		);
		case 'move_down_green': return array(
			'alt'  => T_('Down'),
			'size' => array( 12, 13 ),
			'xy' => array( 64, 240 )
		);
		case 'move_up_green': return array(
			'alt'  => T_('Up'),
			'size' => array( 12, 13 ),
			'xy' => array( 80, 240 )
		);
		case 'move_down_magenta': return array(
			'alt'  => T_('Down'),
			'size' => array( 12, 13 ),
			'xy' => array( 96, 240 )
		);
		case 'move_up_magenta': return array(
			'alt'  => T_('Up'),
			'size' => array( 12, 13 ),
			'xy' => array( 112, 240 )
		);
		case 'move_down_grey': return array(
			'alt'  => T_('Down'),
			'size' => array( 12, 13 ),
			'xy' => array( 128, 240 )
		);
		case 'move_up_grey': return array(
			'alt'  => T_('Up'),
			'size' => array( 12, 13 ),
			'xy' => array( 144, 240 )
		);
		case 'arrow_left_white': return array(
			'alt'  => T_('Previous'),
			'size' => array( 10, 14 ),
			'xy' => array( 128, 256 )
		);
		case 'arrow_right_white': return array(
			'alt'  => T_('Next'),
			'size' => array( 10, 14 ),
			'xy' => array( 144, 256 )
		);
		case 'arrow_left_grey': return array(
			'alt'  => T_('Previous'),
			'size' => array( 10, 14 ),
			'xy' => array( 128, 224 )
		);
		case 'arrow_right_grey': return array(
			'alt'  => T_('Next'),
			'size' => array( 10, 14 ),
			'xy' => array( 144, 224 )
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
		case 'ban': return array(
			'alt'  => /* TRANS: Abbrev. */ T_('Ban'),
			'size' => array( 13, 13 ),
			'xy' => array( 112, 128 )
		);
		case 'ban_disabled': return array(
			'alt'  => T_('Ban'),
			'size' => array( 13, 13 ),
			'xy' => array( 96, 128 )
		);
		case 'play': return array( // used to write an e-mail, visit site or contact through IM
			'alt'  => '&gt;',
			'size' => array( 14, 14 ),
			'xy' => array( 80, 128 )
		);

		case 'feed': return array(
			'alt'	 => T_('XML Feed'),
			'size' => array( 16, 16 ),
			'xy' => array( 0, 176 )
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

		case 'thumb_up': return array(
			'alt'  => T_('Thumb Up'),
			'size' => array( 15, 15 ),
			'xy' => array( 112, 144 )
		);
		case 'thumb_up_disabled': return array(
			'alt'  => T_('Thumb Up'),
			'size' => array( 15, 15 ),
			'xy' => array( 96, 144 )
		);
		case 'thumb_down': return array(
			'alt'  => T_('Thumb Down'),
			'size' => array( 15, 15 ),
			'xy' => array( 144, 144 )
		);
		case 'thumb_down_disabled': return array(
			'alt'  => T_('Thumb Down'),
			'size' => array( 15, 15 ),
			'xy' => array( 128, 144 )
		);

		case 'login': return array(
			'alt'  => T_('Login'),
			'size' => array( 24, 24 ),
			'xy' => array( 0, 272 )
		);
		case 'register': return array(
			'alt'  => T_('Register'),
			'size' => array( 24, 24 ),
			'xy' => array( 0, 296 )
		);

		case 'magnifier': return array(
			'alt'  => T_('Log as a search instead'),
			'size' => array( 14, 13 ),
			'xy' => array( 16, 176 )
		);

		case 'add': return array(
			'alt'  => T_('Add'),
			'size' => array( 16, 16 ),
			'xy' => array( 32, 224 )
		);
		case 'remove': return array(
			'alt'  => T_('Remove'),
			'size' => array( 16, 16 ),
			'xy' => array( 48, 224 )
		);

		case 'multi_action': return array(
			'alt'  => T_('Action for selected elements'),
			'size' => array( 16, 16 ),
			'xy' => array( 112, 224 )
		);

		case 'rotate_right': return array(
			'alt'  => T_('Rotate this picture 90&deg; to the right'),
			'size' => array( 15, 16 ),
			'xy' => array( 64, 224 )
		);
		case 'rotate_left': return array(
			'alt'  => T_('Rotate this picture 90&deg; to the left'),
			'size' => array( 15, 16 ),
			'xy' => array( 80, 224 )
		);
		case 'rotate_180': return array(
			'alt'  => T_('Rotate this picture 180&deg;'),
			'size' => array( 14, 16 ),
			'xy' => array( 96, 224 )
		);

		case 'notification': return array(
			'alt'  => T_('Email notification'),
			'size' => array( 15, 12 ),
			'xy' => array( 16, 0 )
		);

		case 'width_increase': return array(
			'alt'  => T_('Increase width'),
			'size' => array( 32, 32 ),
			'xy' => array( 0, 240 )
		);
		case 'width_decrease': return array(
			'alt'  => T_('Decrease width'),
			'size' => array( 32, 32 ),
			'xy' => array( 32, 240 )
		);

		case 'post': return array(
			'alt'  => T_('Post'),
			'size' => array( 15, 15 ),
			'xy' => array( 144, 16 )
		);

		case 'stop': return array(
			'alt'  => T_('Stop'),
			'size' => array( 16, 16 ),
			'xy' => array( 64, 128 )
		);

		case 'arrow-btn': return array(
			'alt'  => T_('More info'),
			'size' => array( 8, 12 ),
			'xy' => array( 128, 160 ),
			'rollover' => true,
		);
	}
}
?>