<?php
/**
 * This file implements the Item history view
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $Collection, $Blog;

/**
 * @var Item
 */
global $edited_Item;

global $admin_url, $current_User;

if( $lastedit_User = & $edited_Item->get_lastedit_User() )
{	// Get login of last edit user
	$lastedit_user_login = $lastedit_User->get( 'login' );
}
else
{	// User was deleted
	$lastedit_user_login = T_('(deleted user)');
}

// SQL to get the proposed changes:
$proposed_changes_SQL = new SQL();
$proposed_changes_SQL->SELECT( 'iver_ID, CONCAT( "p", iver_ID ) AS param_ID, iver_edit_last_touched_ts, iver_edit_user_ID, iver_type, iver_status, iver_title, user_login, -iver_ID AS version_order' );
$proposed_changes_SQL->FROM( 'T_items__version' );
// LEFT JOIN users to display proposed changes by already deleted users
$proposed_changes_SQL->FROM_add( 'LEFT JOIN T_users ON iver_edit_user_ID = user_ID' );
$proposed_changes_SQL->WHERE( 'iver_itm_ID = '.$edited_Item->ID );
$proposed_changes_SQL->WHERE_and( 'iver_type = "proposed"' );

// SQL to get current version:
$current_sql = 'SELECT "current" AS iver_ID, "c" AS param_ID,
		"'.$edited_Item->last_touched_ts.'" AS iver_edit_last_touched_ts,
		"'.$edited_Item->lastedit_user_ID.'" AS iver_edit_user_ID,
		"current" AS iver_type,
		"'.$edited_Item->status.'" AS iver_status,
		"'.$edited_Item->title.'" AS iver_title,
		"'.str_replace( '"', '\"', $lastedit_user_login ).'" AS user_login,
		0 AS version_order';

// SQL to get old versions:
$old_versions_SQL = new SQL();
$old_versions_SQL->SELECT( 'iver_ID, CONCAT( "a", iver_ID ) as param_ID, iver_edit_last_touched_ts, iver_edit_user_ID, iver_type, iver_status, iver_title, user_login, -iver_ID AS version_order' );
$old_versions_SQL->FROM( 'T_items__version' );
// LEFT JOIN users to display versions edited by already deleted users
$old_versions_SQL->FROM_add( 'LEFT JOIN T_users ON iver_edit_user_ID = user_ID' );
$old_versions_SQL->WHERE( 'iver_itm_ID = '.$edited_Item->ID );
$old_versions_SQL->WHERE_and( 'iver_type = "archived"' );

// Get a count of ALL revisions:
$count_SQL = new SQL();
$count_SQL->SELECT( 'COUNT(*)+1' );
$count_SQL->FROM( 'T_items__version' );
$count_SQL->WHERE( 'iver_itm_ID = '.$edited_Item->ID );
$revisions_count = intval( $DB->get_var( $count_SQL->get() ) );

$default_order = $revisions_count > 1 ? '---D' : '-D';

// Create result set:
$history_sql = $proposed_changes_SQL->get()
	.' UNION '.$current_sql
	.' UNION '.$old_versions_SQL->get()
	.' ORDER BY iver_type DESC, version_order ASC';
$Results = new Results( $history_sql, 'iver_', $default_order, NULL, $revisions_count );

$Results->title = T_('Item history for:').' '.$edited_Item->get_title();

if( $revisions_count > 1 )
{	// Dispay the selectors to compare the revisions
	$Results->cols[] = array(
							'th' => '',
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '<input type="radio" name="r1" value="$param_ID$" />',
						);

	$Results->cols[] = array(
							'th' => '',
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '<input type="radio" name="r2" value="$param_ID$" />',
						);
}

$Results->cols[] = array(
						'th' => T_('Revision'),
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'td' => '~conditional( #iver_type# == "proposed", "+".#iver_ID#, #iver_ID# )~',
					);

$Results->cols[] = array(
						'th' => T_('Date'),
						'default_dir' => 'D',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'td' => '%mysql2localedatetime_spans( #iver_edit_last_touched_ts# )%',
					);

/**
 * Get item version editor login with link to user profile
 *
 * @param integer editor user ID
 * @return string user profile link or 'Deleted user' text if the user doesn't exist anymore
 */
function iver_editor_login( $user_ID )
{
	$r = get_user_identity_link( NULL, $user_ID );
	if( empty( $r ) )
	{
		return T_('(deleted user)');
	}
	return $r;
}
$Results->cols[] = array(
						'th' => T_('User'),
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'td' => '%iver_editor_login( #iver_edit_user_ID# )%',
					);

/**
 * Get post status label from DB value
 *
 * @param string Status value
 * @return string Status label
 */
function iver_status_label( $iver_status )
{
	$r = '';
	$statuses = get_visibility_statuses();
	$status = isset( $statuses[ $iver_status ] ) ? $statuses[ $iver_status ] : $iver_status;

	$r .= '<span class="note status_'.$iver_status.'">';
	$r .= '<span>'.$status.'</span>';
	$r .= '</span>';

	return $r;
}
$Results->cols[] = array(
						'th' => T_('Status'),
						'td' => '%iver_status_label( #iver_status# )%',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
					);

/**
 * Helper function to display a note column for post versions table
 *
 * @param string ID of version param
 * @param string Version type
 * @return string
 */
function iver_td_note( $param_ID, $iver_type )
{
	global $admin_url, $edited_Item;

	switch( $iver_type )
	{
		case 'proposed':
			$iver_type_title = T_('Proposed change');
			break;
		case 'current':
			$iver_type_title = T_('Current version');
			break;
		case 'archived':
			$iver_type_title = T_('Archived version');
			break;
	}

	return '<a href="'.$admin_url.'?ctrl=items&amp;action=history_details&amp;p='.$edited_Item->ID.'&amp;r='.$param_ID.'">'.$iver_type_title.'</a>';
}
$Results->cols[] = array(
						'th' => T_('Note'),
						'td' => '%iver_td_note( #param_ID#, #iver_type# )%</a>',
					);

$Results->cols[] = array(
						'th' => T_('Title'),
						'td' => '$iver_title$',
					);

/**
 * Helper function to display actions column for post versions table
 *
 * @param string Version ID
 * @param string Version type
 * @return string
 */
function iver_td_actions( $iver_ID, $iver_type )
{
	global $edited_Item, $current_User;
	$r = '';

	// Button to view the version:
	$permanent_url = $edited_Item->get_permanent_url();
	if( $iver_type != 'current' )
	{
		$permanent_url = url_add_param( $permanent_url, array( 'revision' => ( $iver_type == 'proposed' ? 'p' : '' ).$iver_ID ) );
	}
	$r .= '<a href="'.$permanent_url.'" class="action_icon btn btn-info btn-xs">'.T_('View').'</a>';

	if( $iver_type == 'archived' )
	{	// Button to restore the version:
		$r .= '<a href="'.regenerate_url( 'action', 'action=history_restore&amp;r='.$iver_ID.'&amp;'.url_crumb( 'item' ) ).'" class="action_icon btn btn-primary btn-xs">'.T_('Restore').'</a>';
	}

	if( $iver_type == 'proposed' )
	{	// Button to accept or reject the proposed versions:
		global $admin_url;
		$r .= '<a href="'.$admin_url.'?ctrl=items&amp;action=accept_propose&amp;post_ID='.$edited_Item->ID.'&amp;r=p'.$iver_ID.'&amp;'.url_crumb( 'item' ).'"'
			.' class="action_icon btn btn-success btn-xs"'
			.' onclick="return confirm( \''.sprintf( TS_('You are about to accept the proposed change #%s.\nPlease note all previous proposed changes will be also accepted.\nAre you sure?'), $iver_ID ).'\')">'
				.T_('Accept')
			.'</a>';
		$r .= '<a href="'.$admin_url.'?ctrl=items&amp;action=reject_propose&amp;post_ID='.$edited_Item->ID.'&amp;r=p'.$iver_ID.'&amp;'.url_crumb( 'item' ).'"'
			.' class="action_icon btn btn-danger btn-xs"'
			.' onclick="return confirm( \''.sprintf( TS_('You are about to reject the proposed change #%s.\nPlease note all newer proposed changes will be also rejected.\nAre you sure?'), $iver_ID ).'\')">'
				.T_('Reject')
			.'</a>';
	}

	return $r;
}
$Results->cols[] = array(
						'th' => T_('Actions'),
						'td' => '%iver_td_actions( #iver_ID#, #iver_type# )%',
						'td_class' => 'shrinkwrap left',
					);

$Form = new Form( NULL, '', 'get' );

$Form->hidden_ctrl();
$Form->hidden( 'p', get_param( 'p' ) );
$Form->hidden( 'action', 'history_compare' );

$Form->begin_form();

$Results->display();

$Form->buttonsstart = '';
$Form->buttonsend = '';

$buttons = array();
if( $revisions_count > 1 )
{	// Button to compare the revisions
	$buttons = array( array( 'submit', '', T_('Compare selected revisions'), 'SaveButton' ) );
	echo get_icon( 'multi_action', 'imgtag', array( 'style' => 'margin:0 10px 0 18px') );
}
$Form->end_form( $buttons );

// JS code for merge button:
echo_item_merge_js();

if( $revisions_count > 2 )
{	// Print JS code for selectors to compare the revisions
?>
<script>
jQuery( document ).ready( function()
{	// Set default selected revisions on page loading:
	var rows_num = jQuery( 'input[name=r1]' ).length,
	current_index = jQuery( 'input[name=r1]' ).index( jQuery( 'input[name=r1][value=c]' ) );
	if( current_index == rows_num - 1 )
	{	// Suggest to compare curent version with first proposed change if post has only the proposed changes without archived versions:
		jQuery( 'input[name=r1][value=c]' ).click();
		jQuery( 'input[name=r2]:eq( ' + ( current_index - 1 ) + ' )' ).click();
	}
	else
	{	// Suggest to compare curent version with last archived version if post has at least one archived version:
		jQuery( 'input[name=r1]:eq( ' + ( current_index + 1 ) + ' )' ).click();
		jQuery( 'input[name=r2][value=c]' ).click();
	}
} );

jQuery( 'input[name=r1]' ).click( function()
{
	var index = jQuery( 'input[name=r1]' ).index( jQuery( this ) );
	jQuery( 'input[name=r2]:lt(' + index + ')' ).show();
	jQuery( 'input[name=r2]:gt(' + ( index - 1 ) + ')' ).hide();
} );

jQuery( 'input[name=r2]' ).click( function()
{
	var index = jQuery( 'input[name=r2]' ).index( jQuery( this ) );
	jQuery( 'input[name=r1]:gt(' + index + ')' ).show();
	jQuery( 'input[name=r1]:lt(' + ( index + 1 ) + ')' ).hide();
} );
</script>
<?php
}
?>