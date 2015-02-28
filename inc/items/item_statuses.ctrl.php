<?php
/**
 * This file implements the controller for item statuses management.
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

$AdminUI->set_page_manual_link( 'managing-item-statuses' );

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
