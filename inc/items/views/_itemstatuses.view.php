<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $admin_url;

function item_status_order( $item_status_order, $item_status_id )
{
	if( check_user_perm( 'options', 'edit', true ) )
	{
		return '<a href="#" rel="'.$item_status_id.'"'.'>'.( $item_status_order === NULL ? '-' : $item_status_order ).'</a>';
	}
	else
	{
		return $item_status_order;
	}

}

// Create query
$SQL = new SQL();
$SQL->SELECT( '*' );
$SQL->FROM( 'T_items__status' );

// Create result set:
$Results = new Results( $SQL->get(), 'pst_' );

$Results->title = T_('Item Statuses').get_manual_link( 'managing-item-statuses' );


$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'pst_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => '$pst_ID$',
	);


$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'pst_name',
		'td' => '<strong><a href="'.$admin_url.'?ctrl=itemstatuses&amp;pst_ID=$pst_ID$&amp;action=edit">$pst_name$</a></strong>',
	);

$Results->cols[] = array(
		'th' => T_('Order'),
		'th_class' => 'shrinkwrap hidden-xs',
		'order' => 'pst_order',
		'td_class' => 'right jeditable_cell item_status_order_edit hidden-xs',
		'td' => '%item_status_order( #pst_order#, #pst_ID# )%',
		'extra' => array( 'rel' => '#pst_ID#' ),
	);

if( check_user_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
			'th' => T_('Actions'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'td' => action_icon( T_('Edit this item status...'), 'edit', $admin_url.'?ctrl=itemstatuses&amp;pst_ID=$pst_ID$&amp;action=edit' )
					.action_icon( T_('Duplicate this item status...'), 'copy', $admin_url.'?ctrl=itemstatuses&amp;pst_ID=$pst_ID$&amp;action=new' )
					.action_icon( T_('Delete this item status!'), 'delete', regenerate_url( 'pst_ID,action', 'pst_ID=$pst_ID$&amp;action=delete&amp;'.url_crumb( 'itemstatus' ) ) ),
		);

	$Results->global_icon( T_('Create a new item status...'), 'new',
		regenerate_url( 'action', 'action=new' ), T_('New item status').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}

// Display results:
$Results->display();

?>

<script>
jQuery(document).ready( function()
{	
<?php

// Print JS to edit an item status order:
echo_editable_column_js( array(
	'column_selector' => '.item_status_order_edit',
	'ajax_url'        => get_htsrv_url().'async.php?action=item_status_order_edit&'.url_crumb( 'itemstatus' ),
	'field_type'      => 'text',
	'new_field_name'  => 'new_item_status_order',
	'ID_value'        => 'jQuery( this ).attr( "rel" )',
	'ID_name'         => 'pst_ID',
	'print_init_tags' => false
) );
?>
});
</script>