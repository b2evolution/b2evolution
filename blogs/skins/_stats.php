<?php
	/**
	 * This is the template that displays stats for a blog
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 * To display the stats, you should call a stub AND pass the right parameters
	 * For example: /blogs/index.php?disp=stats
	 *
	 * b2evolution - {@link http://b2evolution.net/}
	 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
	 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
	 *
	 * @package evoskins
	 */
	if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

	if( $disp == 'stats' )
	{ ?>

	<div class="statbloc"><h3><?php echo T_('Last referers') ?>:</h3>
	<?php refererList(10, 'global', 1, 1, 'no', '', ($blog > 1) ? $blog : '');
	if( count( $res_stats ) ) { ?>
	<ul>
		<?php foreach( $res_stats as $row_stats ) { ?>
			<li><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></li>
		<?php } // End stat loop ?>
	</ul>
	<?php } ?>
	</div>

	<div class="statbloc">
	<h3><?php echo T_('Top referers') ?>:</h3>
	<?php refererList(10, 'global', 0, 0, 'no', 'dom_name', ($blog > 1) ? $blog : '', false);
	if( count( $res_stats ) ) { ?>
	<ol>
		<?php foreach( $res_stats as $row_stats ) { ?>
			<li><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></li>
		<?php } // End stat loop ?>
	</ol>
	<?php } ?>
	</div>

	<div class="statbloc" style="clear: left;">
	<h3><?php echo T_('Last refering searches') ?>:</h3>
	<?php refererList(20, 'global', 1, 1, 'search', '', ($blog > 1) ? $blog : '');
	if( count( $res_stats ) ) { ?>
	<ul>
		<?php foreach( $res_stats as $row_stats ) { ?>
			<li><a href="<?php stats_referer() ?>"><?php stats_search_keywords() ?></a></li>
		<?php } // End stat loop ?>
	</ul>
	<?php } ?>
	</div>

	<div class="statbloc">
	<h3><?php echo T_('Top refering engines') ?>:</h3>
	<?php refererList(10, 'global', 0, 0, 'search', 'dom_name', ($blog > 1) ? $blog : '', true);
	if( count( $res_stats ) ) { ?>
	<table class="invisible">
		<?php foreach( $res_stats as $row_stats ) { ?>
			<tr>
				<td class="right">&#8226;</td>
				<td><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
				<td class="right"><?php stats_hit_percent() ?></td>
			</tr>
		<?php } // End stat loop ?>
	</table>
	<?php } ?>
	</div>

	<div class="statbloc">
	<h3><?php echo T_('Top Indexing Robots') ?>:</h3>
	<?php refererList(10, 'global', 0, 0, 'robot', 'agnt_signature', ($blog > 1) ? $blog : '', true, true);
	if( count( $res_stats ) ) { ?>
	<table class="invisible">
		<?php foreach( $res_stats as $row_stats ) { ?>
			<tr>
				<td class="right">&#8226;</td>
				<td><?php stats_referer('<a href="', '">') ?><?php stats_user_agent( true ) ?><?php stats_referer('', '</a>', false) ?></td>
				<td class="right"><?php stats_hit_percent() ?></td>
			</tr>
		<?php } // End stat loop ?>
	</table>
	<?php } ?>
	</div>


	<div class="statbloc">
	<h3><?php echo T_('Top Aggregators') ?>:</h3>
	<?php refererList(10, 'global', 0, 0, 'rss', 'agnt_signature', ($blog > 1) ? $blog : '', true, true);
	if( count( $res_stats ) ) { ?>
	<table class="invisible">
		<?php if( count( $res_stats ) ) foreach( $res_stats as $row_stats ) { ?>
			<tr>
				<td class="right">&#8226;</td>
				<td><?php stats_user_agent('robots,aggregators') ?> </td>
				<td class="right"><?php stats_hit_percent() ?></td>
			</tr>
		<?php } // End stat loop ?>
	</table>
	<?php } ?>
	</div>

	<div class="clear"></div>

	<?php
	}
?>