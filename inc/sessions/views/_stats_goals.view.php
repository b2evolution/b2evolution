<?php
/**
 * This file implements the UI view for the Goal Hit list.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $rsc_url;
global $Session;

$perm_options_edit = $current_User->check_perm( 'options', 'edit', false );

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';

global $final, $s, $cat;
$final = param( 'final', 'integer', NULL, true );
$s = param( 's', 'string', NULL, true );
$cat = param( 'cat', 'integer', NULL, true );

$goal_filters = $Session->get( 'goal_filters' );
if( empty( $goal_filters ) )
{ // No saved filter yet
	$goal_filters = array();
}
if( $final === NULL && $s === NULL && $cat === NULL )
{ // Get a param values from Session
	$final = empty( $goal_filters['final'] ) ? 0 : $goal_filters['final'];
	$s = empty( $goal_filters['s'] ) ? '' : $goal_filters['s'];
	$cat = empty( $goal_filters['cat'] ) ? 0 : $goal_filters['cat'];
}
else
{ // Save new values to Session
	$goal_filters['final'] = $final;
	$goal_filters['s'] = $s;
	$goal_filters['cat'] = $cat;
}
// Save new filters
$Session->set( 'goal_filters', $goal_filters );
$Session->dbsave();

// Create query:
$SQL = new SQL();
$SQL->SELECT( 'g.*, gcat_name, gcat_color' );
$SQL->FROM( 'T_track__goal AS g' );
$SQL->FROM_add( 'LEFT JOIN T_track__goalcat ON gcat_ID = goal_gcat_ID' );

if( ! empty( $final ) )
{ // We want to filter on final goals only:
	$SQL->WHERE_and( 'goal_redir_url IS NULL' );
}

if( ! empty( $s ) )
{ // We want to filter on search keyword:
	// Note: we use CONCAT_WS (Concat With Separator) because CONCAT returns NULL if any arg is NULL
	$SQL->WHERE_and( 'CONCAT_WS( " ", goal_name, goal_key, goal_redir_url ) LIKE "%'.$DB->escape($s).'%"' );
}
if( ! empty( $cat ) )
{ // We want to filter on category:
	$SQL->WHERE_and( 'goal_gcat_ID = '.$DB->quote( $cat ) );
}

// Create result set:
$Results = new Results( $SQL->get(), 'goals_', '-A' );

$Results->Cache = & get_GoalCache();

$Results->title = T_('Goals').get_manual_link( 'goal-settings' );

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_goals( & $Form )
{
	$Form->checkbox_basic_input( 'final', get_param('final'), /* TODO: please add context for translators.. */ T_('Final only').' &bull;' );
	$Form->text( 's', get_param('s'), 30, T_('Search'), '', 255 );

	$GoalCategoryCache = & get_GoalCategoryCache( NT_('All') );
	$GoalCategoryCache->load_all();
	$Form->select_input_object( 'cat', get_param('cat'), $GoalCategoryCache, T_('Category'), array( 'allow_none' => true ) );
}
$Results->filter_area = array(
	'callback' => 'filter_goals',
	'url_ignore' => 'results_goals_page,final',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=goals&amp;blog='.$blog.'&amp;final=0&amp;s=&amp;cat=0' ),
		'final' => array( T_('Final'), '?ctrl=goals&amp;blog='.$blog.'&amp;final=1' ),
		)
	);

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'goal_ID',
		'td_class' => 'center',
		'td' => '$goal_ID$',
	);

$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'goal_name',
		'td' => $perm_options_edit ?
			'<a href="'.$admin_url.'?ctrl=goals&amp;action=edit&amp;blog='.$blog.'&amp;goal_ID=$goal_ID$" style="color:$gcat_color$~conditional( #gcat_color# == "", "", ";font-weight:bold" )~">$goal_name$</a>' :
			'<span style="color:$gcat_color$~conditional( #gcat_color# == "", "", ";font-weight:bold" )~">$goal_name$</span>',
	);

$Results->cols[] = array(
		'th' => T_('Category'),
		'order' => 'gcat_name',
		'td' => $perm_options_edit ?
			'<a href="'.$admin_url.'?ctrl=goals&amp;tab3=cats&amp;action=cat_edit&amp;blog='.$blog.'&amp;gcat_ID=$goal_gcat_ID$" style="color:$gcat_color$">$gcat_name$</a>' :
			'<span style="color:$gcat_color$">$gcat_name$</span>',
		'extra' => array( 'style' => 'color:#gcat_color#' )
	);

$Results->cols[] = array(
		'th' => T_('Key'),
		'order' => 'goal_key',
		'td' => '@action_link( "edit", #goal_key# )@',
 	);


$Results->cols[] = array(
		'th' => T_('Redirect to'),
		'order' => 'goal_redir_url',
		'td_class' => 'small',
		'td' => '<a href="%{Obj}->get_active_url()%">%{Obj}->get_active_url( array( "before_temp" => "<b>", "after_temp" => "</b>" ) )%</a>',
 	);

$Results->cols[] = array(
		'th' => T_('Def. val.'),
		'order' => 'goal_default_value',
		'td_class' => 'right',
		'td' => '$goal_default_value$',
	);

if( $perm_options_edit )
{ // We have permission to modify:

	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '@action_icon("edit")@@action_icon("copy")@@action_icon("delete")@',
						);

  $Results->global_icon( T_('Create a new goal...'), 'new', regenerate_url( 'action', 'action=new' ), T_('New goal').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}


// Display results:
$Results->display();

?>