<?php
/**
 * This file implements the ordered list editor list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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


/*
 * $Log$
 * Revision 1.7  2010/01/30 18:55:28  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.6  2010/01/03 13:10:57  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.5  2009/09/25 13:09:36  efy-vyacheslav
 * Using the SQL class to prepare queries
 *
 * Revision 1.4  2009/03/08 23:57:43  fplanque
 * 2009
 *
 * Revision 1.3  2008/01/21 09:35:31  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/09/08 20:23:03  fplanque
 * action icons / wording
 *
 * Revision 1.1  2007/06/25 11:00:20  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.7  2007/04/26 00:11:11  fplanque
 * (c) 2007
 *
 * Revision 1.6  2006/11/26 01:42:09  fplanque
 * doc
 */
?>