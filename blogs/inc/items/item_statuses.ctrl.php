<?php
/**
 * This file implements the controller for item statuses management.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id: item_statuses.ctrl.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI
 */
global $AdminUI;

/**
 * @var User
 */
global $current_User;

global $dispatcher;

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

$tab = param( 'tab', 'string', 'settings', true );

/**
 * We need make this call to build menu for all modules
 */
$AdminUI->set_path( 'items' );

/*
 * Add sub menu entries:
 * We do this here instead of _header because we need to include all filter params into regenerate_url()
 */
attach_browse_tabs();

$AdminUI->set_path( 'items', 'settings', 'statuses' );

$AdminUI->breadcrumbpath_init( true, array( 'text' => T_('Contents'), 'url' => '?ctrl=items&amp;blog=$blog$&amp;tab=full&amp;filter=restore' ) );
$AdminUI->breadcrumbpath_add( T_('Content settings'), '?ctrl=itemtypes&amp;blog=$blog$&amp;tab=settings&amp;tab3=statuses' );
$AdminUI->breadcrumbpath_add( T_('Post statuses'), '?ctrl=itemtypes&amp;blog=$blog$&amp;tab=settings&amp;tab3=statuses' );



$list_title = T_('Post statuses');
$default_col_order = 'A';
$edited_name_maxlen = 30;
$perm_name = 'options';
$perm_level = 'edit';
$form_below_list = true;

/**
 * Delete restrictions
 */
$delete_restrictions = array(
		array( 'table'=>'T_items__item', 'fk'=>'post_pst_ID', 'msg'=>T_('%d related items') ),
	);

$restrict_title = T_('Cannot delete item status');	 //&laquo;%s&raquo;

// Used to know if the element can be deleted, so to display or not display confirm delete dialog (true:display, false:not display)
// It must be initialized to false before checking the delete restrictions
$checked_delete = false;

$GenericElementCache = new GenericCache( 'GenericElement', false, 'T_items__status', 'pst_', 'pst_ID' );

require $inc_path.'generic/inc/_generic_listeditor.php';

?>
