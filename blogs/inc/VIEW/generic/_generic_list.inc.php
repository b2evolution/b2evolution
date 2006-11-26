<?php
/**
 * This file implements the element list editor list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $perm_name, $perm_level;

global $result_fadeout;

global $GenericElementCache;

global $list_title, $default_col_order, $form_below_list;


// EXPERIMENTAL
if ( !isset( $default_col_order ) )
{ // The default order column is not set, so the default is the name column
	$default_col_order = '-A-';
}

// Create result set:
$sql = "SELECT $GenericElementCache->dbIDname, {$GenericElementCache->dbprefix}name
  			 	FROM $GenericElementCache->dbtablename";

$Results = & new Results(	$sql, $GenericElementCache->dbprefix, $default_col_order );

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
	
	if( ( empty( $locked_IDs ) || !in_array( $ID, $locked_IDs ) )
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

	function edit_actions( $ID )
	{
		global $locked_IDs, $GenericElementCache;

		$r = action_icon( T_('Duplicate...'), 'copy', regenerate_url( 'action,'.$GenericElementCache->dbIDname, $GenericElementCache->dbIDname.'='.$ID.'&amp;action=copy' ) );

		if( empty( $locked_IDs ) || !in_array( $ID, $locked_IDs ) )
		{ // This element is NOT locked:
			$r = action_icon( T_('Edit...'), 'edit', regenerate_url( 'action,'.$GenericElementCache->dbIDname, $GenericElementCache->dbIDname.'='.$ID.'&amp;action=edit' ) )
						.$r
						.action_icon( T_('Delete!'), 'delete', regenerate_url( 'action,'.$GenericElementCache->dbIDname, $GenericElementCache->dbIDname.'='.$ID.'&amp;action=delete' ) );

		}

		return $r;
	}

	$Results->cols[] = array(
			'th' => T_('Actions'),
			'td_class' => 'shrinkwrap',
			'td' => '%edit_actions( #'.$GenericElementCache->dbIDname.'# )%',
		);

}

if( !$form_below_list )
{	// Need to dispaly global icon to add new geenric element:
	if( !isset( $perm_name ) || $current_User->check_perm( $perm_name, $perm_level, false ) )
	{	// We have permission permission to edit:
		$Results->global_icon( T_('Add an element...'), 'new', regenerate_url( 'action,'.$GenericElementCache->dbIDname, 'action=new' ), T_('Add element'), 3, 4 );
	}
}

// EXPERIMENTAL
// $Results->display();
$Results->display( NULL, $result_fadeout );


/*
 * $Log$
 * Revision 1.6  2006/11/26 01:42:09  fplanque
 * doc
 *
 */
?>