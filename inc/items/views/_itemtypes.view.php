<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'items/model/_itemtype.class.php', 'ItemType' );

global $dispatcher;

// Create query
$SQL = new SQL();
$SQL->SELECT( '*' );
$SQL->FROM( 'T_items__type' );

// Create result set:
$Results = new Results( $SQL->get(), 'ptyp_' );

$Results->title = T_('Item/Post/Page types');

// get reserved ids
global $reserved_ids;
$reserved_ids = ItemType::get_reserved_ids();

/**
 * Callback to build possible actions depending on item type id
 *
 */
function get_actions_for_itemtype( $id )
{
	global $reserved_ids;
	$action = action_icon( T_('Duplicate this item type...'), 'copy',
										regenerate_url( 'action', 'ptyp_ID='.$id.'&amp;action=new') );
	if( ($id < $reserved_ids[0]) || ($id > $reserved_ids[1]) )
	{ // not reserved id
		$action = action_icon( T_('Edit this item type...'), 'edit',
										regenerate_url( 'action', 'ptyp_ID='.$id.'&amp;action=edit') )
							.$action
							.action_icon( T_('Delete this item type!'), 'delete',
										regenerate_url( 'action', 'ptyp_ID='.$id.'&amp;action=delete&amp;'.url_crumb('itemtype').'') );
	}
	return $action;
}

/**
 * Callback to make item type name depending on item type id
 *
 */
function get_name_for_itemtype( $id, $name )
{
	global $reserved_ids;

	if( ($id < $reserved_ids[0]) || ($id > $reserved_ids[1]) )
	{	// not reserved id
		$ret_name = '<strong><a href="'.regenerate_url( 'action,ID', 'ptyp_ID='.$id.'&amp;action=edit' ).'">'.$name.'</a></strong>';
	}
	else
	{
		$ret_name = '<strong>'.$name.'</strong>';
	}
	return $ret_name;
}


$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'ptyp_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => '$ptyp_ID$',
	);

$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'ptyp_name',
		'td' => '%get_name_for_itemtype(#ptyp_ID#, #ptyp_name#)%',
	);

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%get_actions_for_itemtype( #ptyp_ID# )%',
						);

	$Results->global_icon( T_('Create a new element...'), 'new',
				regenerate_url( 'action', 'action=new' ), T_('New item type').' &raquo;', 3, 4  );
}

// Display results:
$Results->display();

?>