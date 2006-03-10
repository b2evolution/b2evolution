<?php
/**
 * This file implements the controller for item statuses management.
 *
 * @package admin
 *
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

$AdminUI->set_path( 'options', 'statuses' );

$edited_table = 'T_itemstatuses';
$edited_table_IDcol = 'pst_ID';
$edited_table_namecol = 'pst_name';
$default_col_order = 'A';
$edited_name_maxlen = 40;
$perm_name = 'options';
$perm_level = 'edit';

/**
 * Delete restrictions
 */
$delete_restrictions = array(
		array( 'table'=>'T_items', 'fk'=>'itm_itst_ID', 'msg'=>T_('%d related items') ), // "Lignes de items"
	);

$restrict_title = T_('Cannot delete item status');	 //&laquo;%s&raquo;

// Used to know if the element can be deleted, so to display or not display confirm delete dialog (true:display, false:not display)
// It must be initialized to false before checking the delete restrictions
$checked_delete = false;

require $control_path.'_misc/inc/_listeditor.php';
?>