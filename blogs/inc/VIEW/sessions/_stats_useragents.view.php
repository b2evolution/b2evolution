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

echo '<h2>'.T_('Top User Agents').'</h2>';

$total_hit_count = $DB->get_var( "
	SELECT COUNT(*) AS hit_count
		FROM T_useragents INNER JOIN T_hitlog ON agnt_ID = hit_agnt_ID
	 WHERE agnt_type <> 'rss' "
		.( empty($blog) ? '' : "AND hit_blog_ID = $blog " ), 0, 0, 'Get total hit count' );


// Create result set:
$sql = "SELECT agnt_signature, COUNT(*) AS hit_count
					FROM T_useragents INNER JOIN T_hitlog ON agnt_ID = hit_agnt_ID
				 WHERE agnt_type <> 'rss' "
								.( empty($blog) ? '' : "AND hit_blog_ID = $blog " ).'
				 GROUP BY agnt_ID ';

$count_sql = "SELECT COUNT( DISTINCT agnt_signature )
					FROM T_useragents INNER JOIN T_hitlog ON agnt_ID = hit_agnt_ID
				 WHERE agnt_type <> 'rss' "
								.( empty($blog) ? '' : "AND hit_blog_ID = $blog " );

$Results = & new Results( $sql, 'topua_', '--D', 20, $count_sql );

$Results->title = T_('Top User Agents');

$Results->cols[] = array(
						'th' => T_('Agent signature'),
						'order' => 'agnt_signature',
						'td' => '²agnt_signature²',
						'total' => '<strong>'.T_('Global total').'</strong>',
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
 * Revision 1.1  2006/07/12 18:07:06  fplanque
 * splitted stats into different views
 *
 */
?>