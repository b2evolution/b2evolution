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


// Create query
$SQL = new SQL();
$SQL->SELECT( '*' );
$SQL->FROM( 'T_items__type' );

// Create result set:
$Results = new Results( $SQL->get(), 'ityp_' );

$Results->title = T_('Item/Post/Page types');

// get reserved and default ids
global $default_ids;
$default_ids = ItemType::get_default_ids();

/**
 * Callback to build possible actions depending on post type id
 *
 */
function get_actions_for_itemtype( $id )
{
	global $default_ids;
	$action = action_icon( T_('Duplicate this Post Type...'), 'copy',
										regenerate_url( 'action', 'ityp_ID='.$id.'&amp;action=new') );

	if( ! ItemType::is_reserved( $id ) )
	{ // Edit all post types except of not reserved post type
		$action = action_icon( T_('Edit this Post Type...'), 'edit',
										regenerate_url( 'action', 'ityp_ID='.$id.'&amp;action=edit') )
							.$action;
	}

	if( ! ItemType::is_special( $id ) && ! in_array( $id, $default_ids ) )
	{ // Delete only the not reserved and not default post types
		$action .= action_icon( T_('Delete this Post Type!'), 'delete',
									regenerate_url( 'action', 'ityp_ID='.$id.'&amp;action=delete&amp;'.url_crumb('itemtype').'') );
	}
	return $action;
}

/**
 * Callback to make post type name depending on post type id
 *
 */
function get_name_for_itemtype( $id, $name )
{
	global $current_User;

	if( ! ItemType::is_reserved( $id ) && $current_User->check_perm( 'options', 'edit' ) )
	{ // Not reserved id AND current User has permission to edit the global settings
		$ret_name = '<a href="'.regenerate_url( 'action,ID', 'ityp_ID='.$id.'&amp;action=edit' ).'">'.$name.'</a>';
	}
	else
	{
		$ret_name = $name;
	}

	return '<strong>'.$ret_name.'</strong>';
}


$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'ityp_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => '$ityp_ID$',
	);

$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'ityp_name',
		'td' => '%get_name_for_itemtype(#ityp_ID#, #ityp_name#)%',
	);

$Results->cols[] = array(
		'th' => T_('Back-office tab'),
		'order' => 'ityp_backoffice_tab',
		'td' => '$ityp_backoffice_tab$',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
	);

$Results->cols[] = array(
		'th' => T_('Template name'),
		'order' => 'ityp_template_name',
		'td' => '%conditional( #ityp_template_name# == "", "", #ityp_template_name#.".*.php" )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
	);

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%get_actions_for_itemtype( #ityp_ID# )%',
						);

	$Results->global_icon( T_('Create a new element...'), 'new',
				regenerate_url( 'action', 'action=new' ), T_('New Post Type').' &raquo;', 3, 4  );
}

// Display results:
$Results->display();

?>