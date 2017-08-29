<?php
/**
 * This file implements the UI view for the User Agents stats.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';


global $blog, $admin_url, $rsc_url, $current_User, $UserSettings, $tab3;

global $dname, $dtyp_normal, $dtyp_searcheng, $dtyp_aggregator, $dtyp_email, $dtyp_unknown;

// For the referring domains list:
param( 'dname', 'string', '', true );
param( 'dtyp_normal', 'integer', 0, true );
param( 'dtyp_searcheng', 'integer', 0, true );
param( 'dtyp_aggregator', 'integer', 0, true );
param( 'dtyp_email', 'integer', 0, true );
param( 'dtyp_unknown', 'integer', 0, true );

if( !$dtyp_normal && !$dtyp_searcheng && !$dtyp_aggregator && !$dtyp_email && !$dtyp_unknown )
{	// Set default status filters:
	$dtyp_normal = 1;
	$dtyp_searcheng = 1;
	$dtyp_aggregator = 1;
	$dtyp_email = 1;
	$dtyp_unknown = 1;
}


if( empty( $blog ) )
{ // Page title when we show domains for all blogs
	$page_title = T_('All referring domains');
}
else
{ // Page title for selected blog domains
	global $Collection, $Blog;
	$page_title = sprintf( T_('Referring domains for collection %s'), $Blog->get( 'shortname' ) );
}

echo '<h2 class="page-title">'.$page_title.'</h2>';

$SQL = new SQL();
$list_is_filtered = false;

$selected_agnt_types = array();
if( $dtyp_normal ) $selected_agnt_types[] = "'normal'";
if( $dtyp_searcheng ) $selected_agnt_types[] = "'searcheng'";
if( $dtyp_aggregator ) $selected_agnt_types[] = "'aggregator'";
if( $dtyp_email ) $selected_agnt_types[] = "'email'";
if( $dtyp_unknown ) $selected_agnt_types[] = "'unknown'";
$SQL->WHERE( 'dom_type IN ( '.implode( ', ', $selected_agnt_types ).' )' );
if( count( $selected_agnt_types ) != 5 ) $list_is_filtered = true;

if( ! empty( $dname ) )
{
	$SQL->WHERE( 'dom_name LIKE '.$DB->quote( '%'.$dname.'%' ) );
	$list_is_filtered = true;
}

// Exclude hits of type "self" and "admin":
// TODO: fp>implement filter checkboxes, not a hardwired filter
//$where_clause .= ' AND hit_referer_type NOT IN ( "self", "admin" )';

if( ! empty( $blog ) )
{
	$SQL->WHERE_and( 'hit_coll_ID = '.$blog.' OR hit_coll_ID IS NULL' );
}

$SQL->FROM( 'T_basedomains LEFT OUTER JOIN T_hitlog ON dom_ID = hit_referer_dom_ID' );

if( $tab3 == 'top' )
{ // Calculate the counts only for "top" tab
	$SQL->SELECT( 'SQL_NO_CACHE COUNT( hit_ID ) AS hit_count' );
	$total_hit_count = $DB->get_var( $SQL->get(), 0, 0, 'Get total hit count - referred hits only' );

	$sql_select = ', COUNT( hit_ID ) AS hit_count';
}
else
{ // No calc the counts
	$sql_select = '';
}

// Create result set:
$SQL->SELECT( 'SQL_NO_CACHE dom_ID, dom_name, dom_comment, dom_status, dom_type'.$sql_select );
$SQL->GROUP_BY( 'dom_ID' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'SQL_NO_CACHE COUNT( DISTINCT dom_ID )' );
$count_SQL->FROM( $SQL->get_from( '' ) );
$count_SQL->WHERE( $SQL->get_where( '' ) );

$Results = new Results( $SQL->get(), 'refdom_', '---D', $UserSettings->get( 'results_per_page' ), $count_SQL->get() );

if( $list_is_filtered )
{ // List is filtered, offer option to reset filters:
	$Results->global_icon( T_('Reset all filters!'), 'reset_filters', $admin_url.'?ctrl=stats&amp;tab=domains&amp;tab3='.$tab3.( empty( $blog ) ? '' : '&amp;blog='.$blog ), T_('Reset filters'), 3, 3, array( 'class' => 'action_icon btn-warning' ) );
}

if( $current_User->check_perm( 'stats', 'edit' ) )
{ // Current user has a permission to create new domain
	global $tab_from;
	$tab_from_param = empty( $tab_from ) ? '' : '&amp;tab_from='.$tab_from;
	$Results->global_icon( T_('Add domain'), 'new', $admin_url.'?ctrl=stats&amp;tab=domains&amp;tab3='.$tab3.'&amp;action=domain_new'.$tab_from_param.( empty( $blog ) ? '' : '&amp;blog='.$blog ), T_('Add domain').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_basedomains( & $Form )
{
	global $blog, $dtyp_normal, $dtyp_searcheng, $dtyp_aggregator, $dtyp_email, $dtyp_unknown;

	$Form->text_input( 'dname', get_param( 'dname' ), 20, T_('Domain name'), '', array( 'maxlength' => 250 ) );
	$Form->checkbox( 'dtyp_normal', $dtyp_normal, T_('Regular sites') );
	$Form->checkbox( 'dtyp_searcheng', $dtyp_searcheng, T_('Search engines') );
	$Form->checkbox( 'dtyp_aggregator', $dtyp_aggregator, T_('Feed aggregators') );
	$Form->checkbox( 'dtyp_email', $dtyp_email, T_('Email domains') );
	$Form->checkbox( 'dtyp_unknown', $dtyp_unknown, T_('Unknown') );
}

if( get_param( 'ctrl' ) == 'antispam' )
{ // Set url when we view this page from antispam controller
	$current_url = $admin_url.'?ctrl=antispam&amp;tab3=domains';
}
else
{ // Default url for stats controller
	$current_url = $admin_url.'?ctrl=stats&amp;tab=domains&amp;tab3='.$tab3.'&amp;blog='.$blog;
}

$Results->filter_area = array(
	'callback' => 'filter_basedomains',
	'url_ignore' => 'results_refdom_page,dtyp_normal,dtyp_searcheng,dtyp_aggregator,dtyp_unknown',	// ignore page param and checkboxes
	'presets' => array(
			'browser' => array( T_('Regular'), $current_url.'&amp;dtyp_normal=1' ),
			'robot'   => array( T_('Search engines'), $current_url.'&amp;dtyp_searcheng=1' ),
			'rss'     => array( T_('Aggregators'), $current_url.'&amp;dtyp_aggregator=1' ),
			'email'   => array( T_('Email'), $current_url.'&amp;dtyp_email=1' ),
			'unknown' => array( T_('Unknown'), $current_url.'&amp;dtyp_unknown=1' ),
			'all'     => array( T_('All'), $current_url ),
		)
	);


$Results->title = $page_title.get_manual_link('referring-domains-tab');

$Results->cols[] = array(
						'th' => T_('Domain name'),
						'order' => 'dom_name',
						'td' => '$dom_name$',
						'total' => '<strong>'.T_('Global total').'</strong>',
					);

$Results->cols[] = array(
		'th' => T_('Comment'),
		'td' => '$dom_comment$'
	);

$Results->cols[] = array(
		'th' => T_('Type'),
		'order' => 'dom_type',
		'td_class' => 'jeditable_cell dom_type_edit',
		'td' => /* Check permission: */$current_User->check_perm( 'stats', 'edit' ) ?
			/* Current user can edit Domains */'<a href="#" rel="$dom_type$">%stats_dom_type_title( #dom_type# )%</a>' :
			/* No edit */'%stats_dom_type_title( #dom_type# )%',
		'total' => '',
	);

$Results->cols[] = array(
		'th' => T_('Status'),
		'order' => 'dom_status',
		'td_class' => 'jeditable_cell dom_status_edit',
		'td' => /* Check permission: */$current_User->check_perm( 'stats', 'edit' ) ?
			/* Current user can edit Domains */'<a href="#" rel="$dom_status$">%stats_dom_status_title( #dom_status# )%</a>' :
			/* No edit */'%stats_dom_status_title( #dom_status# )%',
		'total' => '',
		'extra' => array( 'style' => 'background-color: %stats_dom_status_color( "#dom_status#" )%;', 'format_to_output' => false )
	);

if( $tab3 == 'top' )
{ // Display the hit counts
	$Results->cols[] = array(
						'th' => T_('Hit count'),
						'order' => 'hit_count',
						'td_class' => 'right',
						'total_class' => 'right',
						'td' => '$hit_count$',
						'total' => $total_hit_count,
					);

	$Results->cols[] = array(
						'th' => T_('Hit %'),
						'order' => 'hit_count',
						'td_class' => 'right',
						'total_class' => 'right',
						'td' => '%percentage( #hit_count#, '.$total_hit_count.' )%',
						'total' => '%percentage( 100, 100 )%',
					);
}

if( $current_User->check_perm( 'stats', 'edit' ) )
{
	$Results->cols[] = array(
			'th' => T_('Actions'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'td' => '%dom_row_actions( #dom_ID# )%'
		);
}

function dom_row_actions( $dom_ID )
{
	global $admin_url, $tab3;

	$r = '';
	$r .= action_icon( T_('Edit this domain'), 'edit', $admin_url.'?ctrl=stats&amp;tab=domains&amp;action=domain_edit&amp;dom_ID='.$dom_ID.'&amp;tab3='.$tab3 );
	$r .= action_icon( T_('Delete this domain'), 'delete', $admin_url.'?ctrl=stats&amp;tab=domains&amp;action=domain_delete&amp;dom_ID='.$dom_ID.'&amp;tab3='.$tab3.'&amp;'.url_crumb( 'domain' ) );

	return $r;
}

// Display results:
$Results->display();

if( $current_User->check_perm( 'stats', 'edit' ) )
{ // Check permission to edit Domains:
	// Print JS to edit a domain type
	echo_editable_column_js( array(
		'column_selector' => '.dom_type_edit',
		'ajax_url'        => get_htsrv_url().'async.php?action=dom_type_edit&'.url_crumb( 'domtype' ),
		'options'         => stats_dom_type_titles( true ),
		'new_field_name'  => 'new_dom_type',
		'ID_value'        => 'jQuery( ":first", jQuery( this ).parent() ).text()',
		'ID_name'         => 'dom_name' ) );

	// Print JS to edit a domain status
	echo_editable_column_js( array(
		'column_selector' => '.dom_status_edit',
		'ajax_url'        => get_htsrv_url().'async.php?action=dom_status_edit&'.url_crumb( 'domstatus' ),
		'options'         => stats_dom_status_titles( true ),
		'new_field_name'  => 'new_dom_status',
		'ID_value'        => 'jQuery( ":first", jQuery( this ).parent() ).text()',
		'ID_name'         => 'dom_name',
		'colored_cells'   => true ) );
}

?>