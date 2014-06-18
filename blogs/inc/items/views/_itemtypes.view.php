<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2014 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-sergey: Evo Factory / Sergey.
 * @author fplanque: Francois Planque.
 *
 * @version $Id: _itemtypes.view.php 6135 2014-03-08 07:54:05Z manuel $
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