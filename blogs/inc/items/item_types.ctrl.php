<?php
/**
 * This file implements the controller for item types management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

$AdminUI->set_path( 'options', 'types' );

$list_title = T_('Item/Post/Page types');
$default_col_order = '-A';
$edited_name_maxlen = 40;
$locked_IDs = array( 1000, 2000, 3000, 4000, 5000 ); // Prevent editing of Page type
$perm_name = 'options';
$perm_level = 'edit';
$form_below_list = true;

/**
 * Delete restrictions
 */
$delete_restrictions = array(
		array( 'table'=>'T_items__item', 'fk'=>'post_ptyp_ID', 'msg'=>T_('%d related items') ),
	);

$restrict_title = T_('Cannot delete item type');	 //&laquo;%s&raquo;

// Used to know if the element can be deleted, so to display or not display confirm delete dialog (true:display, false:not display)
// It must be initialized to false before checking the delete restrictions
$checked_delete = false;

$GenericElementCache = & new GenericCache( 'GenericElement', false, 'T_items__type', 'ptyp_', 'ptyp_ID' );

require $inc_path.'generic/inc/_generic_listeditor.php';

/*
 * $Log$
 * Revision 1.2  2008/01/21 09:35:31  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:00:23  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.11  2007/05/14 02:43:04  fplanque
 * Started renaming tables. There probably won't be a better time than 2.0.
 *
 * Revision 1.10  2007/05/13 20:44:52  fplanque
 * more pages support
 *
 * Revision 1.9  2007/04/26 00:11:12  fplanque
 * (c) 2007
 *
 * Revision 1.8  2007/03/26 12:59:18  fplanque
 * basic pages support
 *
 * Revision 1.7  2007/03/26 09:34:16  fplanque
 * removed deprecated list editor
 *
 * Revision 1.6  2006/11/26 01:42:09  fplanque
 * doc
 */
?>