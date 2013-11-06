<?php
/**
 * This file implements Link handling functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'links/model/_linkowner.class.php', 'LinkOwner' );
load_class( 'links/model/_linkcomment.class.php', 'LinkComment' );
load_class( 'links/model/_linkitem.class.php', 'LinkItem' );
load_class( 'links/model/_linkuser.class.php', 'LinkUser' );

/**
 * Get a link owner object from link_type and object ID
 * 
 * @param string link type ( item, comment, ... )
 * @param integer the corresponding object ID
 */
function get_link_owner( $link_type, $object_ID )
{
	switch( $link_type )
	{
		case 'item':
			// create LinkItem object
			$ItemCache = & get_ItemCache();
			$Item = $ItemCache->get_by_ID( $object_ID, false );
			$LinkOwner = new LinkItem( $Item );
			break;

		case 'comment':
			// create LinkComment object
			$CommentCache = & get_CommentCache();
			$Comment = $CommentCache->get_by_ID( $object_ID, false );
			$LinkOwner = new LinkComment( $Comment );
			break;

		case 'user':
			// create LinkUser object
			$UserCache = & get_UserCache();
			$User = $UserCache->get_by_ID( $object_ID, false );
			$LinkOwner = new LinkUser( $User );
			break;

		default:
			$LinkOwner = NULL;
	}
	return $LinkOwner;
}


/**
 * Compose screen: display link files iframe
 * 
 * @param object Form
 * @param object LinkOwner object
 * @param string iframe name
 * @param boolean true if creating new owner object, false otherwise
 */
function attachment_iframe( & $Form, & $LinkOwner, $iframe_name = NULL, $creating = false )
{
	global $admin_url, $dispatcher;
	global $current_User;

	if( ! isset( $GLOBALS[ 'files_Module' ] ) )
		return;

	$fieldset_title = T_( 'Images &amp; Attachments' ).get_manual_link( $LinkOwner->type.'_attachments_fieldset' );

	if( $creating )
	{	// Creating new Item
		$fieldset_title .= ' - <a id="title_file_add" href="#" >'.get_icon( 'folder', 'imgtag' ).' '.T_('Add/Link files').'</a> <span class="note">(popup)</span>';

		$Form->begin_fieldset( $fieldset_title, array( 'id' => 'itemform_createlinks' ) );
		$Form->hidden( 'is_attachments', 'false' );

		echo '<table cellspacing="0" cellpadding="0"><tr><td>';
		$Form->submit( array( 'actionArray[create_edit]', /* TRANS: This is the value of an input submit button */ T_('Save & start attaching files'), 'SaveEditButton' ) );
		echo '</td></tr></table>';

		$Form->end_fieldset();

		return;
	}

	// Editing link owner
	$Blog = & $LinkOwner->get_Blog();

	if( $iframe_name == NULL )
	{
		$iframe_name = 'attach_'.generate_random_key( 16 );
	}

	$fieldset_title .= ' - <a href="'.$admin_url.'?ctrl=links&amp;action=edit_links&amp;link_type='.$LinkOwner->type.'&amp;mode=iframe&amp;iframe_name='.$iframe_name.'&amp;link_object_ID='.$LinkOwner->get_ID()
				.'" target="'.$iframe_name.'">'.get_icon( 'refresh', 'imgtag' ).' '.T_('Refresh').'</a>';

	if( $current_User->check_perm( 'files', 'view', false, $Blog->ID )
		&& $LinkOwner->check_perm( 'edit', false ) )
	{	// Check that we have permission to edit owner:
		$fieldset_title .= ' - <a href="'.$dispatcher.'?ctrl=links&amp;link_type='.$LinkOwner->type.'&amp;fm_mode=link_object&amp;link_object_ID='.$LinkOwner->get_ID()
					.'" onclick="return pop_up_window( \''.$dispatcher.'?ctrl=files&amp;mode=upload&amp;iframe_name='
					.$iframe_name.'&amp;fm_mode=link_object&amp;link_type='.$LinkOwner->type.'&amp;link_object_ID='.$LinkOwner->get_ID().'\', \'fileman_upload\', 1000 )">'
					.get_icon( 'folder', 'imgtag' ).' '.T_('Add/Link files').'</a> <span class="note">(popup)</span>';
	}

	$Form->begin_fieldset( $fieldset_title, array( 'id' => 'itemform_links' ) );

	echo '<iframe src="'.$admin_url.'?ctrl=links&amp;link_type='.$LinkOwner->type.'&amp;action=edit_links&amp;mode=iframe&amp;iframe_name='.$iframe_name.'&amp;link_object_ID='.$LinkOwner->get_ID()
				.'" name="'.$iframe_name.'" width="100%" marginwidth="0" height="160" marginheight="0" align="top" scrolling="auto" frameborder="0" id="attachmentframe"></iframe>';

	$Form->end_fieldset();
}


/**
 * Display a table with the attached files
 * 
 * @param object LinkOwner
 * @param array display params
 */
function display_attachments( & $LinkOwner, $params = array() )
{
	global $current_User, $samedomain_htsrv_url;

	$params = array_merge( array(
			'block_start' => '<div class="attachment_list">',
			'block_end' => '</div>',
		), $params );

	$links = $LinkOwner->get_Links();

	if( count( $links ) < 1 )
	{ // there are no attachments
		return;
	}

	echo $params[ 'block_start' ];
	echo '<table class="grouped" cellspacing="0" cellpadding="0">';
	echo '<thead>';
	echo '<th class="firstcol shrinkwrap"><span>'.T_('Icon/Type').'</span></th>';
	echo '<th class="nowrap"><span>'.T_('Path').'</span></th>';
	echo '<th class="lastcol shrinkwrap"><span>'.T_('Actions').'</span></th>';
	echo '</thead><tbody>';
	$row_style = '';
	foreach( $links as $Link )
	{ // display each link attachment in a row
		$row_style = ( $row_style == 'even' ) ? 'odd' : 'even';
		echo '<tr class="'.$row_style.'"><td class="firstcol">';
		$link_File = & $Link->get_File();
		echo $link_File->get_preview_thumb( 'fulltype' );
		echo '</td><td class="nowrap left">';
		echo $link_File->get_view_link();
		echo '</td><td class="shrinkwrap">';
		if( $current_User->check_perm( 'files', 'edit' ) )
		{ // display delete link action
			$redirect_to = urlencode( regenerate_url( '', '', '', '&' ) );
			$delete_url = $samedomain_htsrv_url.'action.php?mname=collections&amp;action=unlink&amp;link_ID='.$Link->ID.'&amp;crumb_collections_unlink='.get_crumb( 'collections_unlink' ).'&amp;redirect_to='.$redirect_to;
			echo action_icon( T_( 'Delete' ), 'delete', $delete_url );
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
	echo $params[ 'block_end' ];
}


/**
 * Display link actions
 * 
 * @param $link_ID
 * @param $cur_idx
 * @param $total_rows
 */
function link_actions( $link_ID, $cur_idx = 0, $total_rows = 2 )
{
	/**
	 * @var File
	 */
	global $current_File;
	global $LinkOwner, $current_User;
	global $iframe_name, $admin_url;

	$r = '';

	// Change order.
	if( $LinkOwner->check_perm( 'edit' ) )
	{	// Check that we have permission to edit LinkOwner object:
		if( $cur_idx > 0 )
		{
			$r .= action_icon( T_('Move upwards'), 'move_up',
				regenerate_url( 'ctrl,link_object_ID,action', 'ctrl=links&amp;link_ID='.$link_ID.'&amp;action=link_move_up&amp;'.url_crumb('link') ) );
		}
		else
		{
			$r .= get_icon( 'nomove' ).' ';
		}

		if( $cur_idx < $total_rows-1 )
		{
			$r .= action_icon( T_('Move down'), 'move_down',
				regenerate_url( 'ctrl,p,itm_ID,action', 'ctrl=links&amp;link_ID='.$link_ID.'&amp;action=link_move_down&amp;'.url_crumb('link') ) );
		}
		else
		{
			$r .= get_icon( 'nomove' ).' ';
		}
	}

	if( isset($current_File) && $current_User->check_perm( 'files', 'view', false, $current_File->get_FileRoot() ) )
	{
		if( $current_File->is_dir() )
			$title = T_('Locate this directory!');
		else
			$title = T_('Locate this file!');
		$url = $current_File->get_linkedit_url( $LinkOwner->type, $LinkOwner->get_ID() );
		$r .= ' <a href="'.$url.'" onclick="return pop_up_window( \''
					.url_add_param( $url, 'mode=upload&amp;iframe_name='.$iframe_name.'' ).'\', \'fileman_upload\', 1000 )" target="_parent" title="'.$title.'">'
					.get_icon( 'locate', 'imgtag', array( 'title'=>$title ) ).'</a> ';
	}

	// Delete link.
	if( $LinkOwner->check_perm( 'edit' ) )
	{ // Check that we have permission to edit LinkOwner object:
		$r .= action_icon( T_('Delete this link!'), 'unlink',
		                  regenerate_url( 'ctrl,p,itm_ID,action', 'ctrl=links&amp;link_ID='.$link_ID.'&amp;action=unlink&amp;'.url_crumb('link') ) );
	}

	if( isset( $current_File ) && $current_File->is_image() )
	{	// Display icon to insert image into post inline
		$r .= ' '.get_icon( 'add', 'imgtag', array(
				'title'   => T_('Insert image into the post'),
				'onclick' => 'insert_image_link( '.$link_ID.', \''.format_to_output( addslashes( $current_File->get( 'desc' ) ), 'htmlspecialchars' ).'\' )',
				'style'   => 'cursor:default;'
			) );
	}

	return $r;
}


/**
 * Display link position edit action
 * 
 * @param $row
 */
function display_link_position( & $row )
{
	global $LinkOwner, $htsrv_url;
	// TODO: fp>dh: can you please implement cumbs in here? I don't clearly understand your code.
	// TODO: dh> only handle images

	$id = 'display_position_'.$row->link_ID;

	// NOTE: dh> using method=get so that we can use regenerate_url (for non-JS).
	$r = '<form action="" method="post">
		<select id="'.$id.'" name="link_position">'
		.Form::get_select_options_string( $LinkOwner->get_positions(), $row->link_position, true).'</select>'
		.'<script type="text/javascript">jQuery("#'.$id.'").change( { url: "'.$htsrv_url.'", crumb: "'.get_crumb( 'link' ).'" }, function( event ) {
			evo_display_position_onchange( this, event.data.url, event.data.crumb ) } );</script>';

	$r .= '<noscript>';
	// Add hidden fields for non-JS
	$url = regenerate_url( 'p,itm_ID,action', 'link_ID='.$row->link_ID.'&action=set_link_position&'.url_crumb('link'), '', '&' );
	$params = explode('&', substr($url, strpos($url, '?')+1));

	foreach($params as $param)
	{
		list($k, $v) = explode('=', $param);
		$r .= '<input type="hidden" name="'.htmlspecialchars($k).'" value="'.htmlspecialchars($v).'" />';
	}
	$r .= '<input class="SaveButton" type="submit" value="&raquo;" />';
	$r .= '</noscript>';
	$r .= '</form>';

	return $r;
}


/**
 * Get all links where file is used
 *
 * @param integer File ID
 * @param array Params
 * @return string The links to that posts, comments and users where the file is used
 */
function get_file_links( $file_ID, $params = array() )
{
	global $DB, $current_User, $baseurl, $admin_url;

	$params = array_merge( array( 
			'separator' => '<br />',
			'post_prefix' => T_('Post').' - ',
			'comment_prefix' => T_('Comment on').' - ',
			'user_prefix' => T_('Profile picture').' - ',
		), $params );

	// Create result array
	$attached_to = array();

	// Get all links with posts and comments
	$links_SQL = new SQL();
	$links_SQL->SELECT( 'link_itm_ID, link_cmt_ID' );
	$links_SQL->FROM( 'T_links' );
	$links_SQL->WHERE( 'link_file_ID = '.$DB->quote( $file_ID ) );
	$links = $DB->get_results( $links_SQL->get() );

	if( !empty( $links ) )
	{	// File is linked with some posts or comments
		$ItemCache = & get_ItemCache();
		$CommentCache = & get_CommentCache();
		foreach( $links as $link )
		{
			if( !empty( $link->link_itm_ID ) )
			{	// File is linked to a post
				if( $Item = & $ItemCache->get_by_ID( $link->link_itm_ID, false ) )
				{
					$Blog = $Item->get_Blog();
					if( $current_User->check_perm( 'item_post!CURSTATUS', 'view', false, $Item ) )
					{	// Current user can edit the linked post
						$attached_to[] = $params['post_prefix'].'<a href="'.url_add_param( $admin_url, 'ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$link->link_itm_ID ).'">'.$Item->get( 'title' ).'</a>';
					}
					else
					{	// No access to edit the linked post
						$attached_to[] = $params['post_prefix'].$Item->get( 'title' );
					}
				}
			}
			if( !empty( $link->link_cmt_ID ) )
			{	// File is linked to a comment
				if( $Comment = & $CommentCache->get_by_ID( $link->link_cmt_ID, false ) )
				{
					$Item = $Comment->get_Item();
					if( $current_User->check_perm( 'comment!CURSTATUS', 'moderate', false, $Comment ) )
					{	// Current user can edit the linked Comment
						$attached_to[] = $params['comment_prefix'].'<a href="'.url_add_param( $admin_url, 'ctrl=comments&amp;action=edit&amp;comment_ID='.$link->link_cmt_ID ).'">'.$Item->get( 'title' ).'</a>';
					}
					else
					{	// No access to edit the linked Comment
						$attached_to[] = $params['comment_prefix'].$Item->get( 'title' );
					}
				}
			}
		}
	}

	// Get all links with profile pictures
	$profile_links_SQL = new SQL();
	$profile_links_SQL->SELECT( 'user_ID, user_login' );
	$profile_links_SQL->FROM( 'T_users' );
	$profile_links_SQL->WHERE( 'user_avatar_file_ID = '.$DB->quote( $file_ID ) );
	$profile_links = $DB->get_results( $profile_links_SQL->get() );
	if( !empty( $profile_links ) )
	{
		foreach( $profile_links as $link )
		{
			if( $current_User->ID != $link->user_ID && !$current_User->check_perm( 'users', 'view' ) )
			{ // No permission to view other users in admin form
				$attached_to[] = $params['user_prefix'].'<a href="'.url_add_param( $baseurl, 'disp=user&amp;user_ID='.$link->user_ID ).'">'.$link->user_login.'</a>';
			}
			else
			{ // Build a link to display a user in admin form
				$attached_to[] = $params['user_prefix'].'<a href="?ctrl=user&amp;user_tab=profile&amp;user_ID='.$link->user_ID.'">'.$link->user_login.'</a>';
			}
		}
	}

	return implode( $params['separator'], $attached_to );
}
?>