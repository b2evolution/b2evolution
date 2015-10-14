<?php
/**
 * This file display the tags list
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $current_User, $admin_url;

$SQL = new SQL();
$SQL->SELECT( 'tag_ID, tag_name, COUNT( it.itag_itm_ID ) AS tag_count' );
$SQL->FROM( 'T_items__tag' );
$SQL->FROM_add( 'LEFT JOIN T_items__itemtag AS it ON it.itag_tag_ID = tag_ID' );
$SQL->GROUP_BY( 'tag_ID' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'COUNT( tag_ID )' );
$count_SQL->FROM( 'T_items__tag' );

// filters
$list_is_filtered = false;
param( 'tag_filter', 'string', '', true );
param( 'tag_item_ID', 'string', '', true );
if( get_param( 'tag_filter' ) )
{ // add tag_name filter
	$sql_name_where = 'LOWER( tag_name ) LIKE '.$DB->quote( '%'.utf8_strtolower( get_param( 'tag_filter' ) ).'%' );
	$SQL->WHERE_and( $sql_name_where );
	$count_SQL->WHERE_and( $sql_name_where );
	$list_is_filtered = true;
}
if( $filter_item_ID = get_param( 'tag_item_ID' ) )
{ // add filter for item ID
	if( is_number( $filter_item_ID ) )
	{
		$sql_item_left_join = 'LEFT JOIN T_items__itemtag AS it2 ON it2.itag_tag_ID = tag_ID';
		$sql_item_where = 'it2.itag_itm_ID = '.$DB->quote( $filter_item_ID );
		$SQL->FROM_add( $sql_item_left_join );
		$SQL->WHERE_and( $sql_item_where );
		$count_SQL->FROM_add( $sql_item_left_join );
		$count_SQL->WHERE_and( $sql_item_where );
		$list_is_filtered = true;
	}
}

// Create result set:
$Results = new Results( $SQL->get(), 'tag_', 'A', NULL, $count_SQL->get() );

$Results->title = T_('Tags').' ('.$Results->get_total_rows().')'.get_manual_link( 'item-tags-list' );
$Results->Cache = get_ItemTagCache();

if( $list_is_filtered )
{ // List is filtered, offer option to reset filters:
	$Results->global_icon( T_('Reset all filters!'), 'reset_filters', $admin_url.'?ctrl=itemtags', T_('Reset filters'), 3, 3, array( 'class' => 'action_icon btn-warning' ) );
}

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_tags( & $Form )
{
	$Form->text_input( 'tag_filter', get_param( 'tag_filter' ), 24, T_('Tag'), '', array( 'maxlength' => 50 ) );

	$item_ID_filter_note = '';
	if( $filter_item_ID = get_param( 'tag_item_ID' ) )
	{ // check item_Id filter. It must be a number
		if( ! is_number( $filter_item_ID ) )
		{ // It is not a number
			$item_ID_filter_note = T_('Must be a number');
		}
	}
	$Form->text_input( 'tag_item_ID', $filter_item_ID, 9, T_('Post ID'), $item_ID_filter_note, array( 'maxlength' => 9 ) );
}
$Results->filter_area = array(
	'callback' => 'filter_tags',
	'url_ignore' => 'tag_filter,results_tag_page',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=itemtags' ),
		)
	);

$Results->cols[] = array(
		'th'       => T_('Tag'),
		'order'    => 'tag_name COLLATE utf8_general_ci',
		'td'       => $current_User->check_perm( 'options', 'edit' ) ?
									'<a href="'.$admin_url.'?ctrl=itemtags&amp;tag_ID=$tag_ID$&amp;action=edit"><b>$tag_name$</b></a>' :
									'$tag_name$',
	);

$Results->cols[] = array(
		'th'          => T_('Used'),
		'th_class'    => 'shrinkwrap',
		'td_class'    => 'shrinkwrap',
		'order'       => 'tag_count',
		'default_dir' => 'D',
		'td'          => '$tag_count$',
	);


if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Results->cols[] = array(
				'th' => T_('Actions'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'td' => action_icon( T_('Edit this tag...'), 'edit', $admin_url.'?ctrl=itemtags&amp;tag_ID=$tag_ID$&amp;action=edit' )
					.action_icon( T_('Delete this tag!'), 'delete', regenerate_url( 'tag_ID,action,tag_filter', 'tag_ID=$tag_ID$&amp;action=delete&amp;'.url_crumb( 'tag' ) ) ),
		);

	if( $current_User->check_perm( 'options', 'edit' ) )
	{	// Allow to clean up tags only if current user has a permission to edit tags:
		$Results->global_icon( T_('Cleanup orphans'), 'cleanup', regenerate_url( 'action', 'action=cleanup' ).'&amp;'.url_crumb( 'tag' ), T_('Cleanup orphans'), 3, 4 );
	}
	$Results->global_icon( T_('Add a new tag...'), 'new', regenerate_url( 'action', 'action=new' ), T_('New tag').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}

$Results->display();

?>