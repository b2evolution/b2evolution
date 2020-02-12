<?php
/**
 * This file implements the advanced collection user and group perms form functions.
 * 
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * 
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 * 
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Get SQL for collection user permissions
 *
 * @param string Keywords
 * @param object Collection
 * @param boolean TRUE to use mask in ORDER BY for Results ordering
 * @return object SQL 
 */
function get_coll_user_perms_SQL( $Blog, $keywords = '', $use_order_mask = true )
{
	$SQL = new SQL( 'Get user permissions for collection #'.$Blog->ID );
	$SQL->SELECT( $Blog->ID.' AS blog_ID, user_ID, user_login, user_level, bloguser_perm_item_propose, bloguser_perm_poststatuses + 0 as perm_poststatuses, bloguser_perm_item_type, bloguser_perm_edit,'
		.' bloguser_can_be_assignee, bloguser_workflow_status, bloguser_workflow_user, bloguser_workflow_priority,'
		. 'bloguser_perm_delcmts, bloguser_perm_recycle_owncmts, bloguser_perm_vote_spam_cmts, bloguser_perm_cmtstatuses + 0 as perm_cmtstatuses, bloguser_perm_edit_cmt,'
		. 'bloguser_perm_delpost, bloguser_perm_edit_ts, bloguser_perm_meta_comment, bloguser_perm_cats,'
		. 'bloguser_perm_properties, bloguser_perm_admin, bloguser_perm_media_upload,'
		. 'bloguser_perm_media_browse, bloguser_perm_media_change, bloguser_perm_analytics,'
		. 'IF( ( user_ID = "'.$Blog->owner_user_ID.'" OR grp_perm_blogs = "viewall" OR grp_perm_blogs = "editall" ), 1, bloguser_ismember ) AS bloguser_ismember,'
		. 'IF( user_ID = "'.$Blog->owner_user_ID.'", 1, 0 ) AS bloguser_is_owner' );
	$SQL->FROM( 'T_users' );
	$SQL->FROM_add( 'LEFT JOIN T_coll_user_perms ON ( user_ID = bloguser_user_ID AND bloguser_blog_ID = '.$Blog->ID.' )' );
	$SQL->FROM_add( 'INNER JOIN T_groups ON user_grp_ID = grp_ID' );
	$SQL->ORDER_BY( 'bloguser_is_owner DESC, bloguser_ismember DESC, '.( $use_order_mask ? '*, ' : '' ).'user_login, user_ID' );

	if( ! empty( $keywords ) )
	{
		$SQL->add_search_field( 'user_login' );
		$SQL->add_search_field( 'user_firstname' );
		$SQL->add_search_field( 'user_lastname' );
		$SQL->add_search_field( 'user_nickname' );
		$SQL->add_search_field( 'user_email' );
		$SQL->WHERE_kw_search( $keywords, 'AND' );
	}

	return $SQL;
}


/**
 * Get SQL for collection group permissions
 *
 * @param string Keywords
 * @param object Collection
 * @param boolean TRUE to use mask in ORDER BY for Results ordering
 * @return object SQL 
 */
function get_coll_group_perms_SQL( $Blog, $keywords = '', $use_order_mask = true )
{
	$SQL = new SQL( 'Get group permissions for collection #'.$Blog->ID );
	$SQL->SELECT( $Blog->ID.' AS blog_ID, grp_ID, grp_name, grp_usage, grp_level, bloggroup_perm_item_propose, bloggroup_perm_poststatuses + 0 as perm_poststatuses, bloggroup_perm_item_type, bloggroup_perm_edit,'
		. 'bloggroup_can_be_assignee, bloggroup_workflow_status, bloggroup_workflow_user, bloggroup_workflow_priority,'
		. 'bloggroup_perm_delcmts, bloggroup_perm_recycle_owncmts, bloggroup_perm_vote_spam_cmts, bloggroup_perm_cmtstatuses + 0 as perm_cmtstatuses, bloggroup_perm_edit_cmt,'
		. 'bloggroup_perm_delpost, bloggroup_perm_edit_ts, bloggroup_perm_meta_comment, bloggroup_perm_cats,'
		. 'bloggroup_perm_properties, bloggroup_perm_admin, bloggroup_perm_media_upload,'
		. 'bloggroup_perm_media_browse, bloggroup_perm_media_change, bloggroup_perm_analytics,'
		. 'IF( ( grp_perm_blogs = "viewall" OR grp_perm_blogs = "editall" ), 1, bloggroup_ismember ) AS bloggroup_ismember' );
	$SQL->FROM( 'T_groups' );
	$SQL->FROM_add( 'LEFT JOIN T_coll_group_perms ON ( grp_ID = bloggroup_group_ID AND bloggroup_blog_ID = '.$Blog->ID.' )' );
	$SQL->ORDER_BY( 'bloggroup_ismember DESC, '.( $use_order_mask ? '*, ' : '' ).'grp_name, grp_ID' );

	if( ! empty( $keywords ) )
	{
		$SQL->add_search_field( 'grp_name' );
		$SQL->WHERE_kw_search( $keywords, 'AND' );
	}

	return $SQL;
}


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
			global $edited_Group;
			return empty( $edited_Group ) ? 'grp_ID' : 'blog_ID';

		default:
			debug_die('Invalid table prefix on advanced collection perms form!');
	}
}


/**
 * Check if permission is always enabled
 *
 * @param object the db row
 * @param string the prefix of the db row: 'bloguser_' or 'bloggroup_'
 * @param string permission name
 * @param string Collection owner user ID
 * @return boolean
 */
function is_always_coll_perm_enabled( $row, $prefix, $perm, $coll_owner_user_ID )
{
	if( $prefix == 'bloguser_' && $perm != 'perm_admin' && $coll_owner_user_ID == $row->user_ID )
	{	// Collection owner has almost all permissions by default (One exception is "admin" perm to edit advanced/administrative coll properties):
		return true;
	}

	// Check if permission is always enabled by group setting:
	if( ! empty( $row->user_ID ) )
	{	// User perm:
		$UserCache = & get_UserCache();
		if( $User = & $UserCache->get_by_ID( $row->user_ID, false, false ) )
		{	// Get user group:
			$perm_Group = & $User->get_Group();
		}
	}
	elseif( ! empty( $row->grp_ID ) )
	{	// Group perm:
		$GroupCache = & get_GroupCache();
		$perm_Group = & $GroupCache->get_by_ID( $row->grp_ID, false, false );
	}

	if( ! empty( $perm_Group ) )
	{	// Check global group setting permission:
		$group_perm_blogs = $perm_Group->get( 'perm_blogs' );
		if( $group_perm_blogs == 'editall' )
		{	// If the group has a global permission to edit ALL collections:
			return true;
		}
		elseif( $perm == 'ismember' && $group_perm_blogs == 'viewall' )
		{	// If the group has a global permission to view or edit ALL collections:
			return true;
		}
	}

	return false;
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
	global $current_User;

	$BlogCache = & get_BlogCache();
	$row_Blog = & $BlogCache->get_by_ID( $row->blog_ID, false, false );

	if( ! $row_Blog->get( 'advanced_perms' ) )
	{	// Don't display if advanced permissions are not enabled for the collection:
		return '';
	}

	$permission_to_change_admin = $current_User->check_perm( 'blog_admin', 'edit', false, $row->blog_ID );

	$row_id_coll = get_id_coll_from_prefix( $prefix );

	$r = '<input type="checkbox"';
	if( !empty($id) )
	{
		$r .= ' id="'.$id.'"';
	}
	$r .= ' name="blog_'.$perm.'_'.$row->{$row_id_coll}.'"';

	if( is_always_coll_perm_enabled( $row, $prefix, $perm, $row_Blog->owner_user_ID ) )
	{	// This perm option is always enabled:
		$r .= ' checked="checked" disabled="disabled"';
	}
	else
	{	// Check if perm option is enabled or/and disabled:
		if( !empty( $row->{$prefix.$perm} ) )
		{
			$r .= ' checked="checked"';
		}
		if( ! $permission_to_change_admin
				&& ($row->{$prefix.'perm_admin'} || $perm == 'perm_admin' ) )
		{ // No permission to touch nOR create admins
			$r .= ' disabled="disabled"';
		}
	}
	$r .= ' class="checkbox" value="1" title="'.$title.'" />';

	if( $perm == 'perm_meta_comment' )
	{	// Add class to easily identify meta status checkbox with matching color:
		$r = '<span class="evo_checkbox_status evo_checkbox_status__meta">'.$r.'</span>';
	}

	return $r;
}


/**
 * Check if the current comment statuses perm value contains at least as much perms as anonymous users have
 * If anonymous users have no permission to post comments, then this will automatically return true;
 *
 * @param integer Collection ID
 * @param integer statuses perm value for the checked user/group
 * @return boolean true if the minimum required permission is granted, false otherwise
 */
function check_default_create_comment_perm( $blog_ID, $perm_statuses )
{
	$BlogCache = & get_BlogCache();
	$row_Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );

	if( $row_Blog->get_setting( 'allow_comments' ) != 'any' )
	{ // Anonymous users are not allowed to post comments
		return true;
	}

	$default_status = $row_Blog->get_setting( 'new_feedback_status' );
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
	global $current_User;

	$BlogCache = & get_BlogCache();
	$row_Blog = & $BlogCache->get_by_ID( $row->blog_ID, false, false );

	if( ! $row_Blog->get( 'advanced_perms' ) )
	{	// Don't display if advanced permissions are not enabled for the collection:
		return '';
	}

	$permission_to_change_admin = $current_User->check_perm( 'blog_admin', 'edit', false, $row->blog_ID );

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
			if( ! check_default_create_comment_perm( $row_Blog->ID, $row->{$perm_statuses} ) )
			{ // Doesn't have at least as high comment create permission as anonymous users have
				$default_status = $row_Blog->get_setting( 'new_feedback_status' );
			}
			$type_param = 'cmt_';
			break;

		default:
			debug_die('Invalid $type param on advanced perms form!');
	}

	$always_enabled = is_always_coll_perm_enabled( $row, $prefix, 'perm_'.$perm_status, $row_Blog->owner_user_ID );

	$r = '<input type="checkbox"';
	if( !empty($id) )
	{
		$r .= ' id="'.$id.'"';
	}
	$r .= ' name="blog_perm_'.$perm_status.'_'.$type_param.$row->{$row_id_coll}.'"';

	$always_disabled = false;
	if( ( $perm_status == 'published' || $perm_status == 'community' ) &&
	    $row_Blog->get_setting( 'allow_access' ) == 'members' )
	{	// If collection is for members only then Published and Community statuses are not allowed:
		$always_disabled = true;
	}
	elseif( $perm_status == 'published' &&
	        $row_Blog->get_setting( 'allow_access' ) == 'users' )
	{	// If collection is for logged-in users only then Published status is not allowed:
		$always_disabled = true;
	}

	if( $always_disabled )
	{	// This perm option is always disabled:
		$r .= ' disabled="disabled"';
	}
	elseif( $always_enabled )
	{	// This perm option is always enabled:
		$r .= ' checked="checked" disabled="disabled"';
	}
	else
	{	// Check if perm option is enabled or/and disabled:
		if( get_status_permvalue( $perm_status ) & $row->{$perm_statuses} )
		{
			$r .= ' checked="checked"';
		}
		if( ! $permission_to_change_admin && $row->{$prefix.'perm_admin'} )
		{
			$r .= ' disabled="disabled"';
		}
	}

	if( $perm_status == $default_status && ! $always_enabled )
	{
		$title .= "\n".T_('Note: Anonymous users may create comments with this status. You will probably want to give the same permission to this user/group.');
	}
	$r .= ' class="checkbox" value="1" title="'.$title.'" />';

	// Add class to easily identify status checkbox with matching color:
	$r = '<span class="evo_checkbox_status evo_checkbox_status__'.$perm_status.'">'.$r.'</span>';

	if( $perm_status == $default_status && ! $always_enabled )
	{	// This is the default comment status checkbox, and user has no permission to create comment with this status ( like anonymous users ) or a higher status:
		$r = '<span class="evo_checkbox_status evo_checkbox_status__default">'.$r.'</span>';
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
	global $current_User;

	$BlogCache = & get_BlogCache();
	$row_Blog = & $BlogCache->get_by_ID( $row->blog_ID, false, false );

	if( ! $row_Blog->get( 'advanced_perms' ) )
	{	// Don't display if advanced permissions are not enabled for the collection:
		return '';
	}

	$permission_to_change_admin = $current_User->check_perm( 'blog_admin', 'edit', false, $row->blog_ID );

	$row_id_coll = get_id_coll_from_prefix( $prefix );

	$r = '<select id="blog_perm_edit_'.$row->{$row_id_coll}.'" name="blog_perm_edit_'.$row->{$row_id_coll}.'"';
	if( is_always_coll_perm_enabled( $row, $prefix, 'perm_edit', $row_Blog->owner_user_ID ) )
	{	// This perm option is always enabled:
		$r .= ' disabled="disabled"';
		$perm_edit_value = 'all';
	}
	else
	{	// Check if perm option is enabled or/and disabled:
		if( ! $permission_to_change_admin && $row->{$prefix.'perm_admin'} )
		{
			$r .= ' disabled="disabled"';
		}
		$perm_edit_value = $row->{$prefix.'perm_edit'};
	}
	$r .= ' >';
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
	global $current_User;

	$BlogCache = & get_BlogCache();
	$row_Blog = & $BlogCache->get_by_ID( $row->blog_ID, false, false );

	if( ! $row_Blog->get( 'advanced_perms' ) )
	{	// Don't display if advanced permissions are not enabled for the collection:
		return '';
	}

	$permission_to_change_admin = $current_User->check_perm( 'blog_admin', 'edit', false, $row->blog_ID );

	$row_id_coll = get_id_coll_from_prefix( $prefix );

	$r = '<select id="blog_perm_edit_cmt'.$row->{$row_id_coll}.'" name="blog_perm_edit_cmt_'.$row->{$row_id_coll}.'"';
	if( is_always_coll_perm_enabled( $row, $prefix, 'perm_edit_cmt', $row_Blog->owner_user_ID ) )
	{	// This perm option is always enabled:
		$r .= ' disabled="disabled"';
		$perm_edit_cmt_value = 'all';
	}
	else
	{	// Check if perm option is enabled or/and disabled:
		if( ! $permission_to_change_admin && $row->{$prefix.'perm_admin'} )
		{
			$r .= ' disabled="disabled"';
		}
		$perm_edit_cmt_value = $row->{$prefix.'perm_edit_cmt'};
	}
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
 * Get the post type edit permission select input for user/group
 * 
 * @param object db row
 * @param string the prefix of the db row: 'bloguser_' or 'bloggroup_'
 */
function coll_perm_item_type( $row, $prefix )
{
	global $current_User;

	$BlogCache = & get_BlogCache();
	$row_Blog = & $BlogCache->get_by_ID( $row->blog_ID, false, false );

	if( ! $row_Blog->get( 'advanced_perms' ) )
	{	// Don't display if advanced permissions are not enabled for the collection:
		return '';
	}

	$permission_to_change_admin = $current_User->check_perm( 'blog_admin', 'edit', false, $row->blog_ID );

	$row_id_coll = get_id_coll_from_prefix( $prefix );

	$r = '<select id="blog_perm_item_type_'.$row->{$row_id_coll}.'" name="blog_perm_item_type_'.$row->{$row_id_coll}.'"';
	if( is_always_coll_perm_enabled( $row, $prefix, 'perm_item_type', $row_Blog->owner_user_ID ) )
	{	// This perm option is always enabled:
		$r .= ' disabled="disabled"';
		$perm_edit_value = 'admin';
	}
	else
	{	// Check if perm option is enabled or/and disabled:
		if( ! $permission_to_change_admin && $row->{$prefix.'perm_admin'} )
		{
			$r .= ' disabled="disabled"';
		}
		$perm_edit_value = $row->{$prefix.'perm_item_type'};
	}
	$r .= ' >';
	$r .= '<option value="standard" '.( $perm_edit_value == 'standard' ? 'selected="selected"' : '' ).'>'.T_('Standard').'</option>';
	$r .= '<option value="restricted" '.( $perm_edit_value == 'restricted' ? 'selected="selected"' : '' ).'>'.T_('Restricted').'</option>';
	$r .= '<option value="admin" '.( $perm_edit_value == 'admin' ? 'selected="selected"' : '' ).'>'.T_('Admin').'</option>';
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
	global $current_User;

	$BlogCache = & get_BlogCache();
	$row_Blog = & $BlogCache->get_by_ID( $row->blog_ID, false, false );

	if( ! $row_Blog->get( 'advanced_perms' ) )
	{	// Don't display if advanced permissions are not enabled for the collection:
		return '';
	}

	$permission_to_change_admin = $current_User->check_perm( 'blog_admin', 'edit', false, $row->blog_ID );

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


/**
 * Get the user login
 * 
 * @param integer User ID
 * @param string User login
 */
function coll_perm_login( $user_ID, $user_login )
{
	global $Collection, $Blog;

	$user_login = get_user_identity_link( $user_login, NULL, 'profile', 'avatar_login' );

	if( $Blog->owner_user_ID == $user_ID )
	{
		$r = $user_login.' ('.T_('Owner').')';
	}
	else
	{
		$r = $user_login;
	}

	return $r;
}


/**
 * Get group name for table cell
 *
 * @param integer Group ID
 * @param string Group name
 * @param string Group usage
 * @return string
 */
function coll_grp_perm_col_name( $grp_ID, $grp_name, $grp_usage )
{
	global $admin_url;

	if( $grp_usage == 'primary' )
	{	// Primary group
		$grp_class = 'label-primary';
		$grp_title = T_('Primary Group');
	}
	else
	{	// Secondary group
		$grp_class = 'label-info';
		$grp_title = T_('Secondary Group');
	}

	return '<a href="'.$admin_url.'?ctrl=users&amp;filter=new&amp;'.( $grp_usage == 'primary' ? 'group' : 'group2' ).'='.$grp_ID
			.'" title="'.format_to_output( $grp_title, 'htmlattr' ).'" class="label '.$grp_class.'">'
			.get_icon( 'contacts', 'imgtag', array( 'style' => 'top:1px;position:relative' ) ).' '.$grp_name
		.'</a>';
}


/**
 * Get checkboxes for table cell "Member"
 *
 * @param object Row
 * @return string
 */
function coll_grp_perm_col_member( $row )
{
	$BlogCache = & get_BlogCache();
	$row_Blog = & $BlogCache->get_by_ID( $row->blog_ID, false, false );

	if( ! $row_Blog->get( 'advanced_perms' ) )
	{	// Don't display if advanced permissions are not enabled for the collection:
		return T_('Advanced permissions are not enabled for this collection');
	}

	$r = coll_perm_checkbox( $row, 'bloggroup_', 'ismember', format_to_output( T_('Permission to read members posts'), 'htmlattr' ), 'checkallspan_state_'.$row->grp_ID );

	if( $row_Blog->get_setting( 'use_workflow' ) )
	{	// If the collection uses workflow:
		$r .= ' '.coll_perm_checkbox( $row, 'bloggroup_', 'can_be_assignee', format_to_output( T_('Workflow Member (Items can be assigned to members of this Group)'), 'htmlattr' ), 'checkallspan_state_'.$row->grp_ID );
		$r .= ' '.coll_perm_checkbox( $row, 'bloggroup_', 'workflow_status', format_to_output( T_('Members of this Group can change task status'), 'htmlattr' ), 'checkallspan_state_'.$row->grp_ID );
		$r .= ' '.coll_perm_checkbox( $row, 'bloggroup_', 'workflow_user', format_to_output( T_('Members of this Group can assign items to others'), 'htmlattr' ), 'checkallspan_state_'.$row->grp_ID );
		$r .= ' '.coll_perm_checkbox( $row, 'bloggroup_', 'workflow_priority', format_to_output( T_('Members of this Group can set priority / deadline'), 'htmlattr' ), 'checkallspan_state_'.$row->grp_ID );
	}

	return $r;
}


/**
 * Initialize Results object for collections list
 *
 * @param object Results
 */
function colls_groups_perms_results( & $Results, $params = array() )
{
	$params = array_merge( array(
			'type'   => 'collection', // 'collection' OR 'group'
			'object' => NULL,
		), $params );

	if( $params['type'] == 'collection' )
	{	// Collection:
		$edited_Blog = & $params['object'];
	}
	else
	{	// Group:
		$edited_Group = & $params['object'];
	}

	/*
	 * Grouping params:
	 */
	if( $params['type'] == 'collection' )
	{	// Collection:
		$Results->group_by = 'bloggroup_ismember';
		$Results->ID_col = 'grp_ID';
	}
	else
	{	// Group:
		$Results->ID_col = 'blog_ID';
	}

	/*
	 * Group columns:
	 */
	$Results->grp_cols[] = array(
			'td_colspan' => 0,  // nb_cols
			'td' => '~conditional( #bloggroup_ismember#, \''.format_to_output( T_('Members'), 'htmlattr' ).'\', \''.format_to_output( T_('Non members'), 'htmlattr' ).'\' )~',
		);

	/*
	 * Colmun definitions:
	 */
	$Results->cols[] = array(
			'th' => T_('ID'),
			'order' => ( $params['type'] == 'collection' ) ? 'grp_ID' : 'blog_ID',
			'td' => ( $params['type'] == 'collection' ) ? '$grp_ID$' : '$blog_ID$',
			'th_class' => 'shrinkwrap',
			'td_class' => 'right',
		);

	if( $params['type'] == 'collection' )
	{	// Collection:
		$Results->cols[] = array(
				'th' => T_('Group'),
				'order' => 'grp_name',
				'td' => '%coll_grp_perm_col_name( #grp_ID#, #grp_name#, #grp_usage# )%',
			);

		$Results->cols[] = array(
				'th' => /* TRANS: Group Level */ T_('L'),
				'order' => 'grp_level',
				'td' => '$grp_level$',
				'td_class' => 'center',
			);
	}
	else
	{	// Group:
		$Results->cols[] = array(
				'th' => T_('Collection'),
				'order' => 'blog_shortname',
				'td' => '$blog_shortname$',
			);
	}

	$col_member = array(
			'th' => $params['type'] == 'collection' ?
					/* TRANS: SHORT table header on TWO lines */ sprintf( T_('Member of<br />%s'), $edited_Blog->get( 'shortname' ) ) :
					T_('Member'),
			'th_class' => 'checkright',
			'td' => '%coll_grp_perm_col_member( {row} )%',
			'td_class' => 'center',
		);
	if( $params['type'] == 'group' )
	{	// Group columns of collection wihtout enabled advanced permissions:
		$col_member['td_colspan'] = '~conditional( #blog_advanced_perms# == 1, 1, -2 )~';
	}
	$Results->cols[] = $col_member;

	$Results->cols[] = array(
			'th_group' => T_('Permissions on Posts'),
			'th' => T_('Propose changes'),
			'th_class' => 'center',
			'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_item_propose\', \''.format_to_output( T_('Permission to propose a change for Item'), 'htmlattr' ).'\' )%',
			'td_class' => 'shrinkwrap',
		);

	$Results->cols[] = array(
			'th_group' => T_('Permissions on Posts'),
			'th' => T_('Post Statuses'),
			'th_class' => 'checkright',
			'td' => '%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'published\', \''.format_to_output( T_('Permission to post into this blog with published status'), 'htmlattr' ).'\', \'post\' )%'.
					'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'community\', \''.format_to_output( T_('Permission to post into this blog with community status'), 'htmlattr' ).'\', \'post\' )%'.
					'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'protected\', \''.format_to_output( T_('Permission to post into this blog with members status'), 'htmlattr' ).'\', \'post\' )%'.
					'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'private\', \''.format_to_output( T_('Permission to post into this blog with private status'), 'htmlattr' ).'\', \'post\' )%'.
					'<span style="display: inline-block; min-width: 5px;"></span>'.
					'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'review\', \''.format_to_output( T_('Permission to post into this blog with review status'), 'htmlattr' ).'\', \'post\' )%'.
					'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'draft\', \''.format_to_output( T_('Permission to post into this blog with draft status'), 'htmlattr' ).'\', \'post\' )%'.
					'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'deprecated\', \''.format_to_output( T_('Permission to post into this blog with deprecated status'), 'htmlattr' ).'\', \'post\' )%'.
					'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'redirected\', \''.format_to_output( T_('Permission to post into this blog with redirected status'), 'htmlattr' ).'\', \'post\' )%',
			'td_class' => 'center nowrap',
		);

	$Results->cols[] = array(
			'th_group' => T_('Permissions on Posts'),
			'th' => T_('Post Types'),
			'th_class' => 'checkright',
			'td' => '%coll_perm_item_type( {row}, \'bloggroup_\' )%',
			'td_class' => 'center',
		);

	$Results->cols[] = array(
			'th_group' => T_('Permissions on Posts'),
			'th' => /* TRANS: SHORT table header on TWO lines */ T_('Edit posts<br />/user level'),
			'th_class' => 'checkright',
			'default_dir' => 'D',
			'td' => '%coll_perm_edit( {row}, \'bloggroup_\' )%',
			'td_class' => 'center',
		);

	$Results->cols[] = array(
			'th_group' => T_('Permissions on Posts'),
			'th' => /* TRANS: SHORT table header on TWO lines */ T_('Delete<br />posts'),
			'th_class' => 'checkright',
			'order' => 'bloggroup_perm_delpost',
			'default_dir' => 'D',
			'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_delpost\', \''.format_to_output( T_('Permission to delete posts in this blog'), 'htmlattr' ).'\' )%',
			'td_class' => 'center',
		);

	$Results->cols[] = array(
			'th_group' => T_('Permissions on Posts'),
			'th' => /* TRANS: SHORT table header on TWO lines */ T_('Adv.<br />Edit'),
			'th_class' => 'checkright',
			'order' => 'bloggroup_perm_edit_ts',
			'default_dir' => 'D',
			'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_edit_ts\', \''.format_to_output( T_('Permission to edit timestamp on posts and comments in this blog'), 'htmlattr' ).'\' )%',
			'td_class' => 'center',
		);

	$Results->cols[] = array(
			'th_group' => T_('Permissions on Comments'),
			'th' => /* TRANS: SHORT table header on TWO lines */ T_('Comment<br />statuses'),
			'th_class' => 'checkright',
			'td' => '%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'published\', \''.format_to_output( T_('Permission to comment into this blog with published status'), 'htmlattr' ).'\', \'comment\' )%'.
					'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'community\', \''.format_to_output( T_('Permission to comment into this blog with community status'), 'htmlattr' ).'\', \'comment\' )%'.
					'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'protected\', \''.format_to_output( T_('Permission to comment into this blog with members status'), 'htmlattr' ).'\', \'comment\' )%'.
					'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'private\', \''.format_to_output( T_('Permission to comment into this blog with private status'), 'htmlattr' ).'\', \'comment\' )%'.
					'<span style="display: inline-block; min-width: 5px;"></span>'.
					'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'review\', \''.format_to_output( T_('Permission to comment into this blog with review status'), 'htmlattr' ).'\', \'comment\' )%'.
					'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'draft\', \''.format_to_output( T_('Permission to comment into this blog with draft status'), 'htmlattr' ).'\', \'comment\' )%'.
					'%coll_perm_status_checkbox( {row}, \'bloggroup_\', \'deprecated\', \''.format_to_output( T_('Permission to comment into this blog with deprecated status'), 'htmlattr' ).'\', \'comment\' )%'.
					'<span style="display: inline-block; min-width: 5px;"></span>'.
					'%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_meta_comment\', \''.format_to_output( T_('Permission to post internal comments into this collection'), 'htmlattr' ).'\' )%',
			'td_class' => 'center nowrap',
		);

	$Results->cols[] = array(
			'th_group' => T_('Permissions on Comments'),
			'th' => /* TRANS: SHORT table header on TWO lines */ T_('Edit cmts<br />/user level'),
			'th_class' => 'checkright',
			'default_dir' => 'D',
			'td' => '%coll_perm_edit_cmt( {row}, \'bloggroup_\' )%',
			'td_class' => 'center',
		);

	$Results->cols[] = array(
			'th_group' => T_('Permissions on Comments'),
			'th' => /* TRANS: SHORT table header on TWO lines */ T_('Delete<br />cmts'),
			'th_class' => 'checkright',
			'order' => 'bloggroup_perm_delcmts',
			'default_dir' => 'D',
			'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_delcmts\', \''.format_to_output( T_('Permission to delete comments on this blog'), 'htmlattr' ).'\' )%&nbsp;'.
					'%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_recycle_owncmts\', \''.format_to_output( T_('Permission to recycle comments on their own posts'), 'htmlattr' ).'\' )%&nbsp;'.
					'%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_vote_spam_cmts\', \''.format_to_output( T_('Permission to give a spam vote on any comment'), 'htmlattr' ).'\' )%&nbsp;',
			'td_class' => 'center nowrap',
		);

	$Results->cols[] = array(
			'th_group' => T_('Perms on Coll.'),
			'th' => T_('Cats'),
			'th_title' => T_('Categories'),
			'th_class' => 'checkright',
			'order' => 'bloggroup_perm_cats',
			'default_dir' => 'D',
			'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_cats\', \''.format_to_output( T_('Permission to edit categories for this blog'), 'htmlattr' ).'\' )%',
			'td_class' => 'center',
		);

	$Results->cols[] = array(
			'th_group' => T_('Perms on Coll.'),
			'th' => /* TRANS: Short for blog features */  T_('Feat.'),
			'th_title' => T_('Features'),
			'th_class' => 'checkright',
			'order' => 'bloggroup_perm_properties',
			'default_dir' => 'D',
			'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_properties\', \''.format_to_output( T_('Permission to edit blog features'), 'htmlattr' ).'\' )%',
			'td_class' => 'center',
		);

	$Results->cols[] = array(
			'th_group' => T_('Perms on Coll.'),
			'th' => get_admin_badge( 'coll', '#', T_('Coll.<br />Admin'), T_('Check this to give Collection Admin permission.') ),
			'th_title' => T_('Advanced/Administrative blog properties'),
			'th_class' => 'checkright',
			'order' => 'bloggroup_perm_admin',
			'default_dir' => 'D',
			'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_admin\', \''.format_to_output( T_('Permission to edit advanced/administrative blog properties'), 'htmlattr' ).'\' )%',
			'td_class' => 'center',
		);

	// Media Directory:
	$Results->cols[] = array(
			'th' => /* TRANS: SHORT table header on TWO lines */ T_('Media<br />Dir'),
			'th_class' => 'checkright',
			'order' => 'bloggroup_perm_media_upload',
			'default_dir' => 'D',
			'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_media_upload\', \''.format_to_output( T_('Permission to upload into blog\'s media folder'), 'htmlattr' ).'\' )%'.
					'%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_media_browse\', \''.format_to_output( T_('Permission to browse blog\'s media folder'), 'htmlattr' ).'\' )%'.
					'%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_media_change\', \''.format_to_output( T_('Permission to change the blog\'s media folder content'), 'htmlattr' ).'\' )%',
			'td_class' => 'center',
		);

	// Analytics:
	$Results->cols[] = array(
			'th' => T_('Analytics'),
			'th_class' => 'checkright',
			'order' => 'bloggroup_perm_analytics',
			'default_dir' => 'D',
			'td' => '%coll_perm_checkbox( {row}, \'bloggroup_\', \'perm_analytics\', \''.format_to_output( T_('Permission to view collection\'s analytics'), 'htmlattr' ).'\' )%',
			'td_class' => 'center',
		);

	$Results->cols[] = array(
			'th' => '&nbsp;',
			'td' => '%perm_check_all( {row}, \'bloggroup_\' )%',
			'td_class' => 'center',
		);
}


/**
 * Get CSV content for collection permissions
 *
 * @param string Prefix: 'bloguser_', 'bloggroup_'
 * @param array Rows
 * @param object Collection
 * @return string
 */
function get_csv_coll_perms( $prefix, $perm_rows, $perm_Blog )
{
	$columns = array( $prefix == 'bloguser_' ? 'login' : 'group', 'level', 'member' );
	if( $perm_Blog->get_setting( 'use_workflow' ) )
	{
		$columns[] = 'assignee';
	}
	$columns[] = 'propose changes';
	$post_statuses = get_visibility_statuses( 'keys' );
	foreach( $post_statuses as $post_status )
	{
		$columns[] = 'post status: '.$post_status;
	}
	$columns[] = 'post types';
	$columns[] = 'edit posts';
	$columns[] = 'delete posts';
	$columns[] = 'edit ts';
	$comment_statuses = get_visibility_statuses( 'keys', array( 'redirected', 'trash' ) );
	foreach( $comment_statuses as $comment_status )
	{
		$columns[] = 'comment status: '.$comment_status;
	}
	$columns[] = 'comment status: meta';
	$columns[] = 'edit cmts';
	$columns[] = 'delete cmts';
	$columns[] = 'recycle cmts';
	$columns[] = 'spam vote cmts';
	$columns[] = 'cats';
	$columns[] = 'feat.';
	$columns[] = 'coll. admin';
	$columns[] = 'media upload';
	$columns[] = 'media browse';
	$columns[] = 'media change';
	$columns[] = 'analytics';

	$r = get_csv_line( $columns );

	foreach( $perm_rows as $perm )
	{
		$perm_row = array(
				$prefix == 'bloguser_' ? $perm->user_login : $perm->grp_name,
				$prefix == 'bloguser_' ? $perm->user_level : $perm->grp_level,
				is_always_coll_perm_enabled( $perm, $prefix, 'ismember', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'ismember'} ),
			);
		if( $perm_Blog->get_setting( 'use_workflow' ) )
		{
			$perm_row[] = is_always_coll_perm_enabled( $perm, $prefix, 'can_be_assignee', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'can_be_assignee'} );
			$perm_row[] = is_always_coll_perm_enabled( $perm, $prefix, 'workflow_status', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'workflow_status'} );
			$perm_row[] = is_always_coll_perm_enabled( $perm, $prefix, 'workflow_user', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'workflow_user'} );
			$perm_row[] = is_always_coll_perm_enabled( $perm, $prefix, 'workflow_priority', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'workflow_priority'} );
		}
		$perm_row[] = is_always_coll_perm_enabled( $perm, $prefix, 'perm_item_propose', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'perm_item_propose'} );
		foreach( $post_statuses as $post_status )
		{
			$perm_row[] = is_always_coll_perm_enabled( $perm, $prefix, 'perm_'.$post_status, $perm_Blog->owner_user_ID ) ? 1 : ( get_status_permvalue( $post_status ) & $perm->perm_poststatuses ? 1 : 0 );
		}
		$perm_row = array_merge( $perm_row, array(
				is_always_coll_perm_enabled( $perm, $prefix, 'perm_item_type', $perm_Blog->owner_user_ID ) ? 'admin' : ( empty( $perm->{$prefix.'perm_item_type'} ) ? 'standard' : $perm->{$prefix.'perm_item_type'} ),
				is_always_coll_perm_enabled( $perm, $prefix, 'perm_edit', $perm_Blog->owner_user_ID ) ? 'all' : ( empty( $perm->{$prefix.'perm_edit'} ) ? 'no' : $perm->{$prefix.'perm_edit'} ),
				is_always_coll_perm_enabled( $perm, $prefix, 'perm_delpost', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'perm_delpost'} ),
				is_always_coll_perm_enabled( $perm, $prefix, 'perm_edit_ts', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'perm_edit_ts'} ),
			) );
		foreach( $comment_statuses as $comment_status )
		{
			$perm_row[] = is_always_coll_perm_enabled( $perm, $prefix, 'perm_'.$comment_status, $perm_Blog->owner_user_ID ) ? 1 : ( get_status_permvalue( $comment_status ) & $perm->perm_cmtstatuses ? 1 : 0 );
		}
		$perm_row = array_merge( $perm_row, array(
				is_always_coll_perm_enabled( $perm, $prefix, 'perm_meta_comment', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'perm_meta_comment'} ),
				is_always_coll_perm_enabled( $perm, $prefix, 'perm_edit_cmt', $perm_Blog->owner_user_ID ) ? 'all' : ( empty( $perm->{$prefix.'perm_edit_cmt'} ) ? 'no' : $perm->{$prefix.'perm_edit_cmt'} ),
				is_always_coll_perm_enabled( $perm, $prefix, 'perm_delcmts', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'perm_delcmts'} ),
				is_always_coll_perm_enabled( $perm, $prefix, 'perm_recycle_owncmts', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'perm_recycle_owncmts'} ),
				is_always_coll_perm_enabled( $perm, $prefix, 'perm_vote_spam_cmts', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'perm_vote_spam_cmts'} ),
				is_always_coll_perm_enabled( $perm, $prefix, 'perm_cats', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'perm_cats'} ),
				is_always_coll_perm_enabled( $perm, $prefix, 'perm_properties', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'perm_properties'} ),
				is_always_coll_perm_enabled( $perm, $prefix, 'perm_admin', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'perm_admin'} ),
				is_always_coll_perm_enabled( $perm, $prefix, 'perm_media_upload', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'perm_media_upload'} ),
				is_always_coll_perm_enabled( $perm, $prefix, 'perm_media_browse', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'perm_media_browse'} ),
				is_always_coll_perm_enabled( $perm, $prefix, 'perm_media_change', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'perm_media_change'} ),
				is_always_coll_perm_enabled( $perm, $prefix, 'perm_analytics', $perm_Blog->owner_user_ID ) ? 1 : intval( $perm->{$prefix.'perm_analytics'} ),
			) );
		$r .= get_csv_line( $perm_row );
	}

	return $r;
}

?>