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
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
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

global $agnt_browser, $agnt_robot, $agnt_rss, $agnt_unknown;

// For the user agents list:
param( 'agnt_browser', 'integer', 0, true );
param( 'agnt_robot', 'integer', 0, true );
param( 'agnt_rss', 'integer', 0, true );
param( 'agnt_unknown', 'integer', 0, true );

if( !$agnt_browser && !$agnt_robot && !$agnt_rss && !$agnt_unknown )
{	// Set default status filters:
	$agnt_browser = 1;
	$agnt_robot = 1;
	$agnt_rss = 1;
	$agnt_unknown = 1;
}


echo '<h2>'.T_('User agents').'</h2>';

$selected_agnt_types = array();
if( $agnt_browser ) $selected_agnt_types[] = "'browser'";
if( $agnt_robot ) $selected_agnt_types[] = "'robot'";
if( $agnt_rss ) $selected_agnt_types[] = "'rss'";
if( $agnt_unknown ) $selected_agnt_types[] = "'unknown'";
$from = 'T_useragents LEFT JOIN T_hitlog ON agnt_ID = hit_agnt_ID';
$where_clause =  ' WHERE agnt_type IN ('.implode(',',$selected_agnt_types).')';

if( !empty($blog) )
{
	$from .= ' INNER JOIN T_blogs ON hit_blog_ID = blog_ID';
	$where_clause .= ' AND hit_blog_ID = '.$blog;
}

$total_hit_count = $DB->get_var( "
		SELECT COUNT(*) AS hit_count
			FROM $from "
			.$where_clause, 0, 0, 'Get total hit count - hits with an UA' );


// Create result set:
$sql = "SELECT agnt_signature, agnt_type, COUNT( * ) AS hit_count
					FROM $from"
			.$where_clause.'
				 GROUP BY agnt_ID ';

$count_sql = "SELECT COUNT( agnt_ID )
					FROM $from"
			.$where_clause;

$Results = & new Results( $sql, 'uagnt_', '--D', 20, $count_sql );


/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_useragents( & $Form )
{
	global $blog, $agnt_browser, $agnt_robot, $agnt_rss, $agnt_unknown;

	$Form->checkbox( 'agnt_browser', $agnt_browser, T_('Browsers') );
	$Form->checkbox( 'agnt_robot', $agnt_robot, T_('Robots') );
	$Form->checkbox( 'agnt_rss', $agnt_rss, T_('XML readers') );
	$Form->checkbox( 'agnt_unknown', $agnt_unknown, T_('Unknown') );
}
$Results->filter_area = array(
	'callback' => 'filter_useragents',
	'url_ignore' => 'results_uagnt_page,agnt_browser,agnt_robot,agnt_rss,agnt_unknown',	// ignore page param and checkboxes
	'presets' => array(
			'browser' => array( T_('Browser'), '?ctrl=stats&amp;tab=useragents&amp;agnt_browser=1&amp;blog='.$blog ),
			'robot' => array( T_('Robots'), '?ctrl=stats&amp;tab=useragents&amp;agnt_robot=1&amp;blog='.$blog ),
			'rss' => array( T_('XML'), '?ctrl=stats&amp;tab=useragents&amp;agnt_rss=1&amp;blog='.$blog ),
			'unknown' => array( T_('Unknown'), '?ctrl=stats&amp;tab=useragents&amp;agnt_unknown=1&amp;blog='.$blog ),
			'all' => array( T_('All'), '?ctrl=stats&amp;tab=useragents&amp;agnt_browser=1&amp;agnt_robot=1&amp;agnt_rss=1&amp;agnt_unknown=1&amp;blog='.$blog ),
		)
	);


	$Results->title = T_('User agents');

$Results->cols[] = array(
						'th' => T_('Agent signature'),
						'order' => 'agnt_signature',
						'td' => '²agnt_signature²',
						'total' => '<strong>'.T_('Global total').'</strong>',
					);

$Results->cols[] = array(
						'th' => T_('Agent type'),
						'order' => 'agnt_type',
						'td' => '²agnt_type²',
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
 * $Log$
 * Revision 1.4  2006/11/24 18:27:26  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.3  2006/09/02 00:33:11  blueyed
 * Merged from branch
 *
 * Revision 1.2  2006/08/24 21:41:14  fplanque
 * enhanced stats
 *
 * Revision 1.1  2006/07/12 18:07:06  fplanque
 * splitted stats into different views
 *
 */
?>