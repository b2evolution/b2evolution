<?php
/**
 * This file display the user tags list
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $current_User, $admin_url;

$SQL = new SQL();
$SQL->SELECT( 'utag_ID, utag_name, COUNT( ut.uutg_user_ID ) AS tag_count' );
$SQL->FROM( 'T_users__tag' );
$SQL->FROM_add( 'LEFT JOIN T_users__usertag AS ut ON ut.uutg_emtag_ID = utag_ID' );
$SQL->GROUP_BY( 'utag_ID' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'COUNT( utag_ID )' );
$count_SQL->FROM( 'T_users__tag' );

// filters
$list_is_filtered = false;
if( get_param( 'utag_filter' ) )
{ // add tag_name filter
	$sql_name_where = 'LOWER( utag_name ) LIKE '.$DB->quote( '%'.utf8_strtolower( get_param( 'utag_filter' ) ).'%' );
	$SQL->WHERE_and( $sql_name_where );
	$count_SQL->WHERE_and( $sql_name_where );
	$list_is_filtered = true;
}
if( $filter_user_ID = get_param( 'tag_user_ID' ) )
{ // add filter for user ID
	if( is_number( $filter_user_ID ) )
	{
		$sql_user_left_join = 'LEFT JOIN T_users__usertag AS ut2 ON ut2.uutg_emtag_ID = utag_ID';
		$sql_user_where = 'ut2.uutg_user_ID = '.$DB->quote( $filter_user_ID );
		$SQL->FROM_add( $sql_user_left_join );
		$SQL->WHERE_and( $sql_user_where );
		$count_SQL->FROM_add( $sql_user_left_join );
		$count_SQL->WHERE_and( $sql_user_where );
		$list_is_filtered = true;
	}
}

// Create result set:
$Results = new Results( $SQL->get(), 'utag_', 'A', NULL, $count_SQL->get() );

$Results->title = T_('User Tags').' ('.$Results->get_total_rows().')'.get_manual_link( 'user-tags-list' );
$Results->Cache = get_UserTagCache();

if( $list_is_filtered )
{ // List is filtered, offer option to reset filters:
	$Results->global_icon( T_('Reset all filters!'), 'reset_filters', $admin_url.'?ctrl=usertags', T_('Reset filters'), 3, 3, array( 'class' => 'action_icon btn-warning' ) );
}

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_tags( & $Form )
{
	$Form->text_input( 'utag_filter', get_param( 'utag_filter' ), 24, /* TRANS: noun */ T_('Tag'), '', array( 'maxlength' => 50 ) );

	$user_ID_filter_note = '';
	if( $filter_user_ID = get_param( 'tag_user_ID' ) )
	{ // check user_Id filter. It must be a number
		if( ! is_number( $filter_user_ID ) )
		{ // It is not a number
			$user_ID_filter_note = T_('Must be a number');
		}
	}
	$Form->text_input( 'tag_user_ID', $filter_user_ID, 9, T_('User ID'), $user_ID_filter_note, array( 'maxlength' => 9 ) );
}
$Results->filter_area = array(
	'callback' => 'filter_tags',
	'url_ignore' => 'utag_filter,results_tag_page',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=usertags' ),
		)
	);

function tag_td_name( $utag_ID, $utag_name )
{
	global $current_User, $admin_url;

	if( $current_User->check_perm( 'options', 'edit' ) )
	{	// Display tag name as link to edit form only if current user has a perm:
		$utag_name = '<a href="'.$admin_url.'?ctrl=usertags&amp;utag_ID='.$utag_ID
				.'&amp;action=edit&amp;return_to='.urlencode( regenerate_url( 'action', '', '', '&' ) ).'">'
			.'<b>'.$utag_name.'</b></a>';
	}

	return $utag_name;
}
$Results->cols[] = array(
		'th'       => /* TRANS: noun */ T_('Tag'),
		'order'    => 'utag_name COLLATE utf8_general_ci',
		'td'       => '%tag_td_name( #utag_ID#, #utag_name# )%',
	);

function tag_td_count( $utag_name, $utag_count )
{
	global $admin_url;
	$r = '<a href="'.$admin_url.'?ctrl=users&amp;user_tag='.$utag_name.'&amp;filter=new'.'">'.$utag_count.'</a>';
	return $r;
}
$Results->cols[] = array(
		'th'          => T_('Used'),
		'th_class'    => 'shrinkwrap',
		'td_class'    => 'shrinkwrap',
		'order'       => 'tag_count',
		'default_dir' => 'D',
		'td'          => '%tag_td_count( #utag_name#, #tag_count# )%',
	);


if( $current_User->check_perm( 'options', 'edit' ) )
{
	function tag_td_actions( $utag_ID )
	{
		global $admin_url;
		return action_icon( T_('Edit this tag...'), 'edit', $admin_url.'?ctrl=usertags&amp;utag_ID='.$utag_ID.'&amp;action=edit&amp;return_to='.urlencode( regenerate_url( 'action', '', '', '&' ) ) )
		      .action_icon( T_('Delete this tag!'), 'delete', regenerate_url( 'utag_ID,action', 'utag_ID='.$utag_ID.'&amp;action=delete&amp;return_to='.urlencode( regenerate_url( 'action', '', '', '&' ) ).'&amp;'.url_crumb( 'usertag' ) ) );
	}
	$Results->cols[] = array(
				'th' => T_('Actions'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'td' => '%tag_td_actions( #utag_ID# )%',
		);

	if( $current_User->check_perm( 'options', 'edit' ) )
	{	// Allow to clean up tags only if current user has a permission to edit tags:
		$Results->global_icon( T_('Cleanup orphans'), 'cleanup', regenerate_url( 'action', 'action=cleanup&amp;return_to='.urlencode( regenerate_url( 'action', '', '', '&' ) ) ).'&amp;'.url_crumb( 'usertag' ), T_('Cleanup orphans'), 3, 4 );
	}
	$Results->global_icon( T_('Add a new tag...'), 'new', regenerate_url( 'action', 'action=new&amp;return_to='.urlencode( regenerate_url( 'action', '', '', '&' ) ) ), T_('New tag').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}

$Results->display();

?>