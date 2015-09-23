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

// We should activate toolbar menu items for this controller
$activate_collection_toolbar = true;

$tab = param( 'tab', 'string', 'settings', true );

$AdminUI->set_path( 'collections', 'settings', 'statuses' );

// Generate available blogs list:
$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array( 'ctrl' => 'itemstatuses', 'tab' => $tab, 'tab3' => 'statuses' ) );

$AdminUI->breadcrumbpath_init( true, array( 'text' => T_('Collections'), 'url' => $admin_url.'?ctrl=dashboard&amp;blog=$blog$' ) );
$AdminUI->breadcrumbpath_add( T_('Settings'), $admin_url.'?ctrl=coll_settings&amp;blog=$blog$&amp;tab=general' );
$AdminUI->breadcrumbpath_add( T_('Post Statuses'), $admin_url.'?ctrl=itemtypes&amp;blog=$blog$&amp;tab=settings&amp;tab3=statuses' );

$AdminUI->set_page_manual_link( 'managing-item-statuses' );

$list_title = T_('Post Statuses').get_manual_link( 'managing-item-statuses' );
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

$restrict_title = T_('Cannot delete Post Status');	 //&laquo;%s&raquo;

// Used to know if the element can be deleted, so to display or not display confirm delete dialog (true:display, false:not display)
// It must be initialized to false before checking the delete restrictions
$checked_delete = false;

$GenericElementCache = new GenericCache( 'GenericElement', false, 'T_items__status', 'pst_', 'pst_ID' );

require $inc_path.'generic/inc/_generic_listeditor.php';

?>
