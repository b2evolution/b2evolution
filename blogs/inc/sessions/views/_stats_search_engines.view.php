<?php
/**
 * This file implements the UI view for the referering searches stats.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';


global $blog, $admin_url, $rsc_url;


// TOP REFERING SEARCH ENGINES
?>

<h3><?php echo T_('Top refering search engines') ?></h3>

<?php
global $res_stats, $row_stats;
refererList(20,'global',0,0,"'search'",'dom_name',$blog,true);
if( count( $res_stats ) )
{
	?>
	<table class="grouped" cellspacing="0">
		<tr>
			<th class="firstcol"><?php echo T_('Search engine') ?></th>
			<th><?php echo T_('Hits') ?></th>
			<th class="lastcol"><?php echo T_('% of total') ?></th>
		</tr>
		<?php
		$count = 0;
		foreach( $res_stats as $row_stats )
		{
			?>
			<tr class="<?php echo( $count%2 ? 'odd' : 'even') ?>">
				<td class="firstcol"><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
				<td class="right"><?php stats_hit_count() ?></td>
				<td class="right"><?php stats_hit_percent() ?></td>
			</tr>
		<?php
		$count++;
		}
		?>
	</table>
<?php
}


/*
 * $Log$
 * Revision 1.1  2008/05/26 19:30:38  fplanque
 * enhanced analytics
 *
 */
?>