<?php
/**
 * This file implements the controller for item types management.
 *
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

$AdminUI->set_path( 'options', 'types' );

$edited_table = 'T_itemtypes';
$edited_table_IDcol = 'ptyp_ID';
$edited_table_namecol = 'ptyp_name';
$default_col_order = '-A';
$edited_name_maxlen = 40;
$perm_name = 'options';
$perm_level = 'edit';

require $control_path.'_misc/inc/_listeditor.php';
?>