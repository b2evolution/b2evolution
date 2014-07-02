<?php
/**
 * This file implements the UI view for the Goal categories list.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * @version $Id: _stats_goalcats.view.php 6135 2014-03-08 07:54:05Z manuel $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $rsc_url;
global $Session;

$perm_options_edit = $current_User->check_perm( 'options', 'edit', false );

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';

// Create query:
$SQL = new SQL();
$SQL->SELECT( 'gcat_ID, gcat_name, gcat_color' );
$SQL->FROM( 'T_track__goalcat' );

// Create result set:
$Results = new Results( $SQL->get(), 'gcats_', '-A' );

$Results->Cache = & get_GoalCategoryCache();

$Results->title = T_('Goal categories').get_manual_link( 'goal-category-settings' );

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'gcat_ID',
		'td_class' => 'shrinkwrap',
		'td' => '$gcat_ID$',
	);

$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'gcat_name',
		'td' => $perm_options_edit ?
			'<a href="'.$admin_url.'?ctrl=goals&amp;tab3=cats&amp;action=cat_edit&amp;gcat_ID=$gcat_ID$" style="color:$gcat_color$;font-weight:bold">$gcat_name$</a>' :
			'<b style="color:$gcat_color$">$gcat_name$</b>',
	);

$Results->cols[] = array(
		'th' => T_('Color'),
		'order' => 'gcat_color',
		'td_class' => 'shrinkwrap',
		'td' => '$gcat_color$',
		'extra' => array( 'style' => 'color:#gcat_color#' )
	);

if( $perm_options_edit )
{ // We have permission to modify:
	$Results->cols[] = array(
			'th' => T_('Actions'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'td' => '%action_icon( "'.T_('Edit this goal category...').'", "edit", "'.$admin_url.'?ctrl=goals&amp;tab3=cats&amp;action=cat_edit&amp;gcat_ID=#gcat_ID#" )%'
				.'%action_icon( "'.T_('Copy this goal category...').'", "copy", "'.$admin_url.'?ctrl=goals&amp;tab3=cats&amp;action=cat_copy&amp;gcat_ID=#gcat_ID#" )%'
				.'~conditional( #gcat_ID# > 1, \'%action_icon( "'.T_('Delete this goal category...').'", "delete", "'.$admin_url.'?ctrl=goals&amp;tab3=cats&amp;action=cat_delete&amp;gcat_ID=#gcat_ID#&amp;'.url_crumb( 'goalcat' ).'" )%\', "" )~',
		);

	$Results->global_icon( T_('Create a new goal category...'), 'new', regenerate_url( 'action', 'action=cat_new' ), T_('New goal category').' &raquo;', 3, 4 );
}

// Display results:
$Results->display();

?>