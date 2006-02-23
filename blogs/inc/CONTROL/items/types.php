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

$AdminUI->set_path( 'options', 'types' );
$edited_table = 'T_itemtypes';
$edited_table_IDcol = 'ptyp_ID';
$edited_table_namecol = 'ptyp_name';
$default_col_order = '-A';
$edited_name_maxlen = 40;

require $control_path.'_misc/inc/_listeditor.php';
?>