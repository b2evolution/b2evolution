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


global $admin_url, $posttypes_reserved_IDs, $Blog, $edited_Item;

// Create query
$SQL = new SQL();
$SQL->SELECT( 'it.*' );
$SQL->FROM( 'T_items__type AS it' );
$SQL->FROM_add( 'INNER JOIN T_items__type_coll ON itc_ityp_ID = ityp_ID AND itc_coll_ID = '.$Blog->ID );
if( ! empty( $posttypes_reserved_IDs ) )
{ // Exclude the reserved post types
	$SQL->WHERE( 'ityp_ID NOT IN ( '.implode( ', ', $posttypes_reserved_IDs ).' )' );
}
// Check what item types are allowed for current user and selected blog
$item_type_perm_levels = array( 'standard', 'restricted', 'admin' );
foreach( $item_type_perm_levels as $i => $item_type_perm_level )
{
	if( ! $current_User->check_perm( 'blog_item_type_'.$item_type_perm_level, 'edit', false, $Blog->ID ) )
	{
		unset( $item_type_perm_levels[ $i ] );
	}
}
$item_type_perm_levels[] = '-1'; // to restrict all item types if no one is allowed
$SQL->WHERE( 'ityp_perm_level IN ( '.$DB->quote( $item_type_perm_levels ).' )' );

// Create result set:
$Results = new Results( $SQL->get(), 'editityp_' );

$Results->title = T_('Change Post Type');

if( $edited_Item->ID > 0 )
{
	$close_url = $admin_url.'?ctrl=items&amp;action=edit&amp;blog='.$Blog->ID.'&amp;restore=1&amp;p='.$edited_Item->ID;
}
else
{
	$close_url = $admin_url.'?ctrl=items&amp;action=new&amp;blog='.$Blog->ID.'&amp;restore=1';
}
$Results->global_icon( T_('Do NOT change the type'), 'close', $close_url );


/**
 * Callback to make post type name depending on post type id
 */
function get_name_for_itemtype( $ityp_ID, $name )
{
	global $admin_url, $edited_Item, $from_tab;

	$current = $edited_Item->ityp_ID == $ityp_ID ? ' '.T_('(current)') : '';

	$from_tab_param = empty( $from_tab ) ? '' : '&amp;from_tab='.$from_tab;

	$duplicated_item_param = get_param( 'p' ) > 0 ? '&amp;p='.get_param( 'p' ) : '';

	return '<strong><a href="'.$admin_url.'?ctrl=items&amp;action=update_type&amp;post_ID='.$edited_Item->ID.'&amp;ityp_ID='.$ityp_ID.$from_tab_param.$duplicated_item_param.'&amp;'.url_crumb( 'item' ).'">'
		.$name.'</a></strong>'
		.$current;
}

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'ityp_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => '%conditional( "'.$edited_Item->ityp_ID.'" == #ityp_ID#, "info shrinkwrap", "shrinkwrap" )%',
		'td' => '$ityp_ID$',
	);

$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'ityp_name',
		'td' => '%get_name_for_itemtype( #ityp_ID#, #ityp_name# )%',
		'td_class' => '%conditional( "'.$edited_Item->ityp_ID.'" == #ityp_ID#, "info", "" )%',
	);

$Results->cols[] = array(
		'th' => T_('Template name'),
		'order' => 'ityp_template_name',
		'td' => '%conditional( #ityp_template_name# == "", "", #ityp_template_name#.".*.php" )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center %conditional( "'.$edited_Item->ityp_ID.'" == #ityp_ID#, " info", "" )%'
	);

// Display results:
$Results->display();

?>