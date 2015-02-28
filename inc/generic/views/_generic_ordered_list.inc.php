<?php
/**
 * This file implements the ordered list editor list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $perm_name, $perm_level;

global $result_fadeout;

global $GenericElementCache;

global $list_title, $default_col_order;


// EXPERIMENTAL
if ( !isset( $default_col_order ) )
{ // The default order column is not set, so the default is the name column
	$default_col_order = '-A-';
}

// Create result set:
$SQL = new SQL();
$SQL->SELECT( $GenericElementCache->dbIDname . ', '
	. $GenericElementCache->dbprefix . 'name, '
	. $GenericElementCache->dbprefix . 'order' );
$SQL->FROM( $GenericElementCache->dbtablename );

$Results = new Results( $SQL->get(), $GenericElementCache->dbprefix, $default_col_order );

if( isset( $list_title ) )
{
	$Results->title = $list_title;
}

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => $GenericElementCache->dbIDname,
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => "\$$GenericElementCache->dbIDname\$",
	);

function link_name( $title , $ID )
{
	global $GenericElementCache;

	global $locked_IDs, $perm_name, $perm_level, $current_User;

	if( !in_array( $ID, $locked_IDs )
			&& ( !isset( $perm_name ) || $current_User->check_perm( $perm_name, $perm_level, false ) ) )
	{	// The element is not locked and we have permission permission to edit:
		return '<strong><a href="'.regenerate_url( 'action,ID', $GenericElementCache->dbIDname.'='.$ID.'&amp;action=edit' ).'">'.$title.'</a></strong>';
	}
	else
	{
		return '<strong>'.$title.'</strong>';
	}
}
$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => $GenericElementCache->dbprefix.'name',
 		'td' => '%link_name( #'.$GenericElementCache->dbprefix.'name#, #'.$GenericElementCache->dbIDname.'# )%',
	);


if( !isset( $perm_name ) || $current_User->check_perm( $perm_name, $perm_level, false ) )
{	// We have permission permission to edit:

	$Results->cols[] = array(
			'th' => T_('Move'),
			'th_class' => 'shrinkwrap',
			'order' => $GenericElementCache->dbprefix.'order',
			'td_class' => 'shrinkwrap',
			'td' => '{move}',
		);

	function edit_actions( $ID )
	{
		global $locked_IDs, $GenericElementCache;

		$r = action_icon( T_('Duplicate...'), 'copy', regenerate_url( 'action,'.$GenericElementCache->dbIDname, $GenericElementCache->dbIDname.'='.$ID.'&amp;action=copy' ) );

		if( empty( $locked_IDs ) || !in_array( $ID, $locked_IDs ) )
		{ // This element is NOT locked:
			$r = action_icon( T_('Edit...'), 'edit', regenerate_url( 'action,'.$GenericElementCache->dbIDname, $GenericElementCache->dbIDname.'='.$ID.'&amp;action=edit' ) )
						.$r
						.action_icon( T_('Delete!'), 'delete', regenerate_url( 'action,'.$GenericElementCache->dbIDname, $GenericElementCache->dbIDname.'='.$ID.'&amp;action=delete&amp;'.url_crumb('element') ) );

		}

		return $r;
	}

	$Results->cols[] = array(
			'th' => T_('Actions'),
			'td_class' => 'shrinkwrap',
			'td' => '%edit_actions( #'.$GenericElementCache->dbIDname.'# )%',
		);

}

if( !isset( $perm_name ) || $current_User->check_perm( $perm_name, $perm_level, false ) )
{	// We have permission permission to edit:
	$Results->global_icon( T_('Create a new element...'), 'new', regenerate_url( 'action,'.$GenericElementCache->dbIDname, 'action=new' ), T_('New element').' &raquo;', 3, 4 );
}

// EXPERIMENTAL
// $Results->display();
$Results->display( NULL, $result_fadeout );

?>