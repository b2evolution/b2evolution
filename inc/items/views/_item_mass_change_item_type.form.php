<?php
/**
 * This file displays the Form to mass change Item Type
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$Form = new Form( $admin_url, 'item_mass_item_type_checkchanges', 'post' );

$Form->begin_form();

	$Form->add_crumb( 'item' );
	$Form->hidden( 'ctrl', 'items' );
	$Form->hidden( 'action', 'mass_change_item_type' );
	$Form->hidden( 'blog', $blog );
	foreach( $selected_items as $item_ID )
	{
		$Form->hidden( 'selected_items[]', $item_ID );
	}
	$Form->hidden( 'redirect_to', $redirect_to );
	$Form->add_crumb( 'items' );

	$SQL = new SQL( 'Get Item Types enabled for Collection #'.$blog.' and allowed for current User #'.$current_User->ID );
	$SQL->SELECT( 'ityp_ID, ityp_name, ityp_description' );
	$SQL->FROM( 'T_items__type' );
	$SQL->FROM_add( 'INNER JOIN T_items__type_coll ON itc_ityp_ID = ityp_ID AND itc_coll_ID = '.$blog );
	// Check what item types are allowed for current User and selected Collection:
	$item_type_perm_levels = array( 'standard', 'restricted', 'admin' );
	foreach( $item_type_perm_levels as $i => $item_type_perm_level )
	{
		if( ! check_user_perm( 'blog_item_type_'.$item_type_perm_level, 'edit', false, $blog ) )
		{
			unset( $item_type_perm_levels[ $i ] );
		}
	}
	$item_type_perm_levels[] = '-1'; // to restrict all item types if no one is allowed
	$SQL->WHERE( 'ityp_perm_level IN ( '.$DB->quote( $item_type_perm_levels ).' )' );
	$item_types = $DB->get_results( $SQL, ARRAY_N );
	$Form->radio( 'item_type_ID', '', $item_types, T_('Item Types'), true );

	$Form->buttons( array( array( 'submit', 'actionArray[mass_change_item_type]', TB_('Change Item Type'), 'SaveButton' ) ) );

$Form->end_form();
?>
