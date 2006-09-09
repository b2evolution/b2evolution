<?php
/**
 * This file implements the UI view for the User Agents stats.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
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
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $rsc_url;

global $dtyp_normal, $dtyp_searcheng, $dtyp_aggregator, $dtyp_unknown;

// For the referring domains list:
param( 'dtyp_normal', 'integer', 0, true );
param( 'dtyp_searcheng', 'integer', 0, true );
param( 'dtyp_aggregator', 'integer', 0, true );
param( 'dtyp_unknown', 'integer', 0, true );

if( !$dtyp_normal && !$dtyp_searcheng && !$dtyp_aggregator && !$dtyp_unknown )
{	// Set default status filters:
	$dtyp_normal = 1;
	$dtyp_searcheng = 1;
	$dtyp_aggregator = 1;
	$dtyp_unknown = 1;
}


echo '<h2>'.T_('Referring domains').'</h2>';

$selected_agnt_types = array();
if( $dtyp_normal ) $selected_agnt_types[] = "'normal'";
if( $dtyp_searcheng ) $selected_agnt_types[] = "'searcheng'";
if( $dtyp_aggregator ) $selected_agnt_types[] = "'aggregator'";
if( $dtyp_unknown ) $selected_agnt_types[] = "'unknown'";
$where_clause =  ' WHERE dom_type IN ('.implode(',',$selected_agnt_types).')';

// Exclude hits of type "self" and "admin":
$where_clause .= ' AND hit_referer_type NOT IN ( "self", "admin" )';

if( !empty($blog) )
{
	$where_clause .= ' AND hit_blog_ID = '.$blog;
}

$total_hit_count = $DB->get_var( "
		SELECT COUNT(*) AS hit_count
			FROM T_basedomains INNER JOIN T_hitlog ON dom_ID = hit_referer_dom_ID "
			.$where_clause, 0, 0, 'Get total hit count - referred hits only' );


// Create result set:
$results_sql = "
		SELECT dom_name, dom_status, dom_type, COUNT( * ) AS hit_count
		  FROM T_basedomains LEFT JOIN T_hitlog ON dom_ID = hit_referer_dom_ID "
		.$where_clause.'
		 GROUP BY dom_ID ';

$results_count_sql = "
		SELECT COUNT( DISTINCT dom_ID )
		  FROM T_basedomains INNER JOIN T_hitlog ON dom_ID = hit_referer_dom_ID "
			.$where_clause;

$Results = & new Results( $results_sql, 'refdom_', '---D', 20, $results_count_sql );


/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_basedomains( & $Form )
{
	global $blog, $dtyp_normal, $dtyp_searcheng, $dtyp_aggregator, $dtyp_unknown;

	$Form->checkbox( 'dtyp_normal', $dtyp_normal, T_('Regular sites') );
	$Form->checkbox( 'dtyp_searcheng', $dtyp_searcheng, T_('Search engines') );
	$Form->checkbox( 'dtyp_aggregator', $dtyp_aggregator, T_('Feed aggregators') );
	$Form->checkbox( 'dtyp_unknown', $dtyp_unknown, T_('Unknown') );
}
$Results->filter_area = array(
	'callback' => 'filter_basedomains',
	'url_ignore' => 'results_refdom_page,dtyp_normal,dtyp_searcheng,dtyp_aggregator,dtyp_unknown',	// ignore page param and checkboxes
	'presets' => array(
			'browser' => array( T_('Regular'), '?ctrl=stats&amp;tab=domains&amp;dtyp_normal=1&amp;blog='.$blog ),
			'robot' => array( T_('Search engines'), '?ctrl=stats&amp;tab=domains&amp;dtyp_searcheng=1&amp;blog='.$blog ),
			'rss' => array( T_('Aggregators'), '?ctrl=stats&amp;tab=domains&amp;dtyp_aggregator=1&amp;blog='.$blog ),
			'unknown' => array( T_('Unknown'), '?ctrl=stats&amp;tab=domains&amp;dtyp_unknown=1&amp;blog='.$blog ),
			'all' => array( T_('All'), '?ctrl=stats&amp;tab=domains&amp;dtyp_normal=1&amp;dtyp_searcheng=1&amp;dtyp_aggregator=1&amp;dtyp_unknown=1&amp;blog='.$blog ),
		)
	);


$Results->title = T_('Referring domains');

$Results->cols[] = array(
						'th' => T_('Domain name'),
						'order' => 'dom_name',
						'td' => '²dom_name²',
						'total' => '<strong>'.T_('Global total').'</strong>',
					);

$Results->cols[] = array(
						'th' => T_('Type'),
						'order' => 'dom_type',
						'td' => '$dom_type$',
						'total' => '',
					);

$Results->cols[] = array(
						'th' => T_('Status'),
						'order' => 'dom_status',
						'td' => '$dom_status$',
						'total' => '',
					);

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

// Display results:
$Results->display();

/*
 nolog */
?>