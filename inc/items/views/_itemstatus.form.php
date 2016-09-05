<?php
/**
 * This file display the item status form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var ItemStatus
 */
global $edited_ItemStatus;

global $action;

// Determine if we are creating or updating...
$creating = is_create_action( $action );

$Form = new Form( NULL, 'itemstatus_checkchanges', 'post' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', ( $creating ?  T_('New post status') : T_('Post status') ).get_manual_link( 'managing-item-statuses-form' ) );

	$Form->add_crumb( 'itemstatus' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',pst_ID' : '' ) ) );

$Form->begin_fieldset( T_('General') );
	$Form->text_input( 'pst_name', $edited_ItemStatus->get( 'name' ), 30, T_('Name'), '', array( 'required' => true ) );
$Form->end_fieldset();

$SQL = new SQL();
if( $edited_ItemStatus->ID )
{
	$SQL->SELECT( 'ityp_ID, ityp_name, its_pst_ID' );
	$SQL->FROM( 'T_items__type' );
	$SQL->FROM_add( 'JOIN T_items__status' );
	$SQL->FROM_add( 'LEFT JOIN T_items__status_type ON its_ityp_ID = ityp_ID AND its_pst_ID = pst_ID' );
	$SQL->WHERE_or( 'pst_ID = '.$edited_ItemStatus->ID );
}
else
{
	$SQL->SELECT( 'ityp_ID, ityp_name, NULL AS its_pst_ID' );
	$SQL->FROM( 'T_items__type' );
}

$Results = new Results( $SQL->get(), 'ityp_' );
$Results->title = T_('Item Types allowed for this Item Status').get_manual_link( 'item-statuses-allowed-per-item-type' );
$Results->cols[] = array(
		'th' => T_('ID'),
		'th_class' => 'shrinkwrap',
		'td' => '$ityp_ID$',
		'td_class' => 'center'
	);

function item_status_type_checkbox( $row )
{
	$title = $row->ityp_name;
	$r = '<input type="checkbox"';
	$r .= ' name="type_'.$row->ityp_ID.'"';

	if( isset( $row->its_pst_ID ) && ! empty( $row->its_pst_ID ) )
	{
		$r .= ' checked="checked"';
	}

	$r .= ' class="checkbox" value="1" title="'.$title.'" />';

	return $r;
}

$Results->cols[] = array(
		'th' => T_('Allowed Item Type'),
		'th_class' => 'shrinkwrap',
		'td' => '%item_status_type_checkbox( {row} )%',
		'td_class' => 'center'
	);

function get_name_for_itemtype( $id, $name )
{
	global $current_User;

	if( $current_User->check_perm( 'options', 'edit' ) )
	{ // Not reserved id AND current User has permission to edit the global settings
		$ret_name = '<a href="'.regenerate_url( 'ctrl,action,ID,pst_ID', 'ctrl=itemtypes&amp;ityp_ID='.$id.'&amp;action=edit' ).'">'.$name.'</a>';
	}
	else
	{
		$ret_name = $name;
	}

	return '<strong>'.$ret_name.'</strong>';
}

$Results->cols[] = array(
		'th' => T_('Name'),
		'td' => '%get_name_for_itemtype( #ityp_ID#, #ityp_name# )%'
	);

$display_params = array(
		'page_url' => 'admin.php?ctrl=itemstatuses&pst_ID='.$edited_ItemStatus->ID.'&action=edit'
	);

$Results->display( $display_params );


$item_type_IDs = array();
foreach( $Results->rows as $row )
{
	$item_type_IDs[] = $row->ityp_ID;
}
$Form->hidden( 'item_type_IDs', implode( ',', $item_type_IDs ) );

if( $creating )
{
	$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_new]', T_('Record, then Create New'), 'SaveButton' ),
													array( 'submit', 'actionArray[create_copy]', T_('Record, then Create Similar'), 'SaveButton' ) ) );
}
else
{
	$Form->end_form( array( array( 'submit', 'actionArray[update]', T_('Save Changes!'), 'SaveButton' ) ) );
}
?>