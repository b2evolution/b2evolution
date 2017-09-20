<?php
/**
 * This file implements the UI view for the referering searches stats.
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


global $blog, $DB;

$max_top_engines = 20;

// Initialize SQL to get 20 top referring search engines:
$SQL = new SQL( 'Get top referring search engines' );
$SQL->SELECT( 'COUNT( hit_ID ) AS count_hits, hit_referer, dom_name' );
$SQL->FROM( 'T_hitlog' );
$SQL->FROM_add( 'LEFT JOIN T_basedomains ON dom_ID = hit_referer_dom_ID' );
$SQL->WHERE( 'hit_referer_type = "search"' );
$SQL->WHERE_and( 'hit_agent_type = "browser"' );
if( ! empty( $blog ) )
{	// Filter by current collection:
	$SQL->WHERE_and( 'hit_coll_ID = '.$DB->quote( $blog ) );
}
$SQL->GROUP_BY( 'dom_name' );

// Get total hits of the 20 top referring search engines:
$total_SQL = new SQL( 'Get total hits of the top referring search engines' );
$total_SQL->SELECT( 'SUM( h.count_hits )' );
$total_SQL->FROM( '( '.$SQL->get().' LIMIT '.$max_top_engines.' ) AS h' );
$total_hits = $DB->get_var( $total_SQL->get(), 0, NULL, $total_SQL->title );

$Results = new Results( $SQL->get(), 'topeng_', '-D', 0, $max_top_engines );

$Results->title = T_('Top referring search engines').get_manual_link( 'top-referring-search-engines' );

function stats_td_search_engine_domain( $hit_referer )
{
	return htmlentities( trim( $hit_referer ) );
}
$Results->cols[] = array(
		'th' => T_('Search engine'),
		'td' => '<a href="%stats_td_search_engine_domain( #hit_referer# )%">%htmlentities( #dom_name# )%</a>',
	);

$Results->cols[] = array(
		'th'       => T_('Hits'),
		'td'       => '$count_hits$',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right'
	);

function stats_td_search_engine_percent( $total_hits, $count_hits )
{
	$percent = $count_hits / $total_hits * 100;
	return number_format( $percent, 1, '.', '' ).'&nbsp;%';
}
$Results->cols[] = array(
		'th'       => T_('% of total'),
		'td'       => '%stats_td_search_engine_percent( '.$total_hits.', #count_hits# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'right'
	);

// Display results:
$Results->display();
?>