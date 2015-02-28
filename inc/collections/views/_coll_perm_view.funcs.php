<?php
/**
 * This file implements the advanced collection user and group perms form functions.
 * 
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * 
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * 
 * @package evocore
 * 
 * @version $Id: _coll_perm_view.funcs.php 8373 2015-02-28 21:44:37Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Filter collectiion user/group perms list by user/group
 * 
 * @param object $Form
 */
function filter_collobjectlist( & $Form )
{
	static $count = 0;

	$count++;
	$Form->switch_layout( 'blockspan' );
	// TODO: javascript update other input fields (for other layouts):
	$Form->text( 'keywords'.$count, get_param('keywords'.$count), 20, T_('Keywords'), T_('Separate with space'), 50 );
	$Form->switch_layout( NULL ); // Restor previously saved
}


/**
 * Get the ID which correspond to table prefix
 * @param string prefix
 * @return string row id field name
 */
function get_id_coll_from_prefix( $prefix )
{
	switch( $prefix )
	{
		case 'bloguser_':
			return 'user_ID';

		case 'bloggroup_':
			return 'grp_ID';

		default:
			debug_die('Invalid table prefix on advanced collection perms form!');
	}
}


/**
 * Get collection perm checkbox
 * 
 * @param object the db row
 * @param string the prefix of the db row: 'bloguser_' or 'bloggroup_'
 * @param string permission name
 * @param string checkbox title
 * @param string the Id of the checkbox item
 */
function coll_perm_checkbox( $row, $prefix, $perm, $title, $id = NULL )
{
	global $permission_to_change_admin;

	$row_id_coll = get_id_coll_from_prefix( $prefix );

	$r = '<input type="checkbox"';
	if( !empty($id) )
	{
		$r .= ' id="'.$id.'"';
	}
	$r .= ' name="blog_'.$perm.'_'.$row->{$row_id_coll}.'"';
	if( !empty( $row->{$prefix.$perm} ) )
	{
	 	$r .= ' checked="checked"';
	}
	if( ! $permission_to_change_admin
			&& ($row->{$prefix.'perm_admin'} || $perm == 'perm_admin' ) )
	{ // No permission to touch nOR create admins
	 	$r .= ' disabled="disabled"';
	}
	$r .= ' class="checkbox" value="1" title="'.$title.'" />';
	return $r;
}


/**
 * Check if the current comment statuses perm value contains at least as much perms as anonymous users have
 * If anonymous users have no permission to post comments, then this will automatically return true;
 *
 * @param integer statuses perm value for the checked user/group
 * @return boolean true if the minimum required permission is granted, false otherwise
 */
function check_default_create_comment_perm( $perm_statuses )
{
	global $edited_Blog;

	if( $edited_Blog->get_setting( 'allow_comments' ) != 'any' )
	{ // Anonymous users are not allowed to post comments
		return true;
	}

	$default_status = $edited_Blog->get_setting( 'new_feedback_status' );
	$default_status_perm_value = get_status_permvalue( $default_status );
	if( $perm_statuses & $default_status_perm_value )
	{ // Posting comments with default status is allowed
		return true;
	}

	$published_perm_value = get_status_permvalue( 'published' );
	// Remove hihger perm vlaues then 'published' status perm value ( 'deprecated' and 'redirected' values are not important in this context )
	$perm_statuses = $perm_statuses & ( $published_perm_value + $published_perm_value - 1 );
	$review_perm_value = get_status_permvalue( 'review' );
	if( ( $perm_statuses > $default_status_perm_value ) || ( ( $default_status == 'draft' ) && (  $perm_statuses & $review_perm_value ) ) )
	{
		return true;
	}

	return false;
}


/**
 * Get perm post/comment statuses for a user or group
 * 
 * @param object db row
 * @param string the prefix of the db row: 'bloguser_' or 'bloggroup_'
 * @param string current perm status
 * @param string the title of the chekbox
 * @param string the type of the permission: 'post' or 'comment'
 */
function coll_perm_status_checkbox( $row, $prefix, $perm_status, $title, $type )
{
	global $edited_Blog, $permission_to_change_admin;

	$row_id_coll = get_id_coll_from_prefix( $prefix );
	$default_status = NULL;

	switch( $type )
	{
		case 'post':
			$perm_statuses = 'perm_poststatuses';
			$type_param = '';
			break;

		case 'comment':
			$perm_statuses = 'perm_cmtstatuses';
			if( ! check_default_create_comment_perm( $row->{$perm_statuses} ) )
			{ // Doesn't have at least as high comment create permission as anonymous users have
				$default_status = $edited_Blog->get_setting( 'new_feedback_status' );
			}
			$type_param = 'cmt_';
			break;

		default:
			debug_die('Invalid $type param on advanced perms form!');
	}

	$r = '<input type="checkbox"';
	if( !empty($id) )
	{
		$r .= ' id="'.$id.'"';
	}
	$r .= ' name="blog_perm_'.$perm_status.'_'.$type_param.$row->{$row_id_coll}.'"';
	if( get_status_permvalue( $perm_status ) & $row->{$perm_statuses} )
	{
	 	$r .= ' checked="checked"';
	}
	if( ! $permission_to_change_admin && $row->{$prefix.'perm_admin'} )
	{
	 	$r .= ' disabled="disabled"';
	}
	if( $perm_status == $default_status )
	{
		$title .= "\n".T_('Note: Anonymous users may create comments with this status. You will probably want to give the same permission to this user/group.');
	}
	$r .= ' class="checkbox" value="1" title="'.$title.'" />';
	if( $perm_status == $default_status )
	{ // This is the default comment status checkbox, and user has no permission to create comment with this status ( like anonymous users ) or a higher status
		$r = '<span class="red-bordered-checkbox">'.$r.'</span>';
	}
	return $r;
}


/**
 * Get the post edit permission select input for user/group
 * 
 * @param object db row
 * @param string the prefix of the db row: 'bloguser_' or 'bloggroup_'
 */
function coll_perm_edit( $row, $prefix )
{
	global $permission_to_change_admin;

	$row_id_coll = get_id_coll_from_prefix( $prefix );

	$r = '<select id="blog_perm_edit_'.$row->{$row_id_coll}.'" name="blog_perm_edit_'.$row->{$row_id_coll}.'"';
	if( ! $permission_to_change_admin && $row->{$prefix.'perm_admin'} )
	{
	 	$r .= ' disabled="disabled"';
	}
	$r .= ' >';
	$perm_edit_value = $row->{$prefix.'perm_edit'};
	$r .= '<option value="no" '.( $perm_edit_value == 'no' ? 'selected="selected"' : '' ).'>No editing</option>';
	$r .= '<option value="own" '.( $perm_edit_value == 'own' ? 'selected="selected"' : '' ).'>Own posts</option>';
	$r .= '<option value="lt" '.( $perm_edit_value == 'lt' ? 'selected="selected"' : '' ).'>&lt; own level</option>';
	$r .= '<option value="le" '.( $perm_edit_value == 'le' ? 'selected="selected"' : '' ).'>&le; own level</option>';
	$r .= '<option value="all" '.( $perm_edit_value == 'all' ? 'selected="selected"' : '' ).'>All posts</option>';
	$r .= '</select>';
	return $r;
}


/**
 * Get the comment edit permission select input for user/group
 * 
 * @param object db row
 * @param string the prefix of the db row: 'bloguser_' or 'bloggroup_'
 */
function coll_perm_edit_cmt( $row, $prefix )
{
	global $permission_to_change_admin;

	$row_id_coll = get_id_coll_from_prefix( $prefix );

	$r = '<select id="blog_perm_edit_cmt'.$row->{$row_id_coll}.'" name="blog_perm_edit_cmt_'.$row->{$row_id_coll}.'"';
	if( ! $permission_to_change_admin && $row->{$prefix.'perm_admin'} )
	{
	 	$r .= ' disabled="disabled"';
	}
	$perm_edit_cmt_value = $row->{$prefix.'perm_edit_cmt'};
	$r .= ' >';
	$r .= '<option value="no" '.( $perm_edit_cmt_value == 'no' ? 'selected="selected"' : '' ).'>No editing</option>';
	$r .= '<option value="own" '.( $perm_edit_cmt_value == 'own' ? 'selected="selected"' : '' ).'>Own cmts</option>';
	$r .= '<option value="anon" '.( $perm_edit_cmt_value == 'anon' ? 'selected="selected"' : '' ).'>Annon cmts</option>';
	$r .= '<option value="lt" '.( $perm_edit_cmt_value == 'lt' ? 'selected="selected"' : '' ).'>&lt; own level</option>';
	$r .= '<option value="le" '.( $perm_edit_cmt_value == 'le' ? 'selected="selected"' : '' ).'>&le; own level</option>';
	$r .= '<option value="all" '.( $perm_edit_cmt_value == 'all' ? 'selected="selected"' : '' ).'>All cmts</option>';
	$r .= '</select>';
	return $r;
}


/**
 * Return link to check/uncheck all permission in a row
 * 
 * @param object db row
 * @param string the prefix of the db row: 'bloguser_' or 'bloggroup_'
 * @return string the link element
 */
function perm_check_all( $row, $prefix )
{
	global $permission_to_change_admin;

	$row_id_coll = get_id_coll_from_prefix( $prefix );

	if( ! $permission_to_change_admin && $row->{$prefix.'perm_admin'} )
	{
	 	return '&nbsp;';
	}

	$row_id_value = $row->{$row_id_coll};
	return '<a href="javascript:toggleall_perm(document.getElementById(\'blogperm_checkchanges\'), '.$row_id_value.' );setcheckallspan('.$row_id_value.');" title="'.TS_('(un)selects all checkboxes using Javascript').'">
				<span id="checkallspan_'.$row_id_value.'">'.TS_('(un)check all').'</span>
			</a>';
}

?>