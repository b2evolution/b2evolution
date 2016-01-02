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

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';


global $blog, $admin_url, $rsc_url;


// TOP REFERRING SEARCH ENGINES
?>

<h3><?php echo T_('Top referring search engines').get_manual_link( 'top-referring-search-engines' ) ?></h3>

<?php
global $res_stats, $row_stats;
refererList(20,'global',0,0,"'search'",'dom_name',$blog,true);
if( count( $res_stats ) )
{
	?>
	<table class="grouped table table-striped table-bordered table-hover table-condensed" cellspacing="0">
		<tr>
			<th class="firstcol"><?php echo T_('Search engine') ?></th>
			<th><?php echo T_('Hits') ?></th>
			<th class="lastcol"><?php echo /* xgettext:no-php-format */ T_('% of total') ?></th>
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


?>