<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */
require_once (dirname(__FILE__).'/_header.php');
require_once (dirname(__FILE__).'/'.$admin_dirout.'/'.$core_subdir.'/_functions_hitlogs.php');						// referer logging
$title = T_('View Stats');

param( 'blog', 'integer', 0, true );
param( 'action', 'string' );
param( 'show', 'string', 'referers' );

require(dirname(__FILE__).'/_menutop.php');
?>
	:
	<?php 
	if( $show == 'summary' ) echo '<strong>[';
	echo '<a href="b2stats.php?show=summary&blog=', $blog, '">', T_('Summary'), '</a>';
	if( $show == 'summary' ) echo ']</strong>'; 
	echo ' | ';
	if( $show == 'referers' ) echo '<strong>[';
	echo '<a href="b2stats.php?show=referers&blog=', $blog, '">', T_('Referers'), '</a>';
	if( $show == 'referers' ) echo ']</strong>'; 
	echo ' | ';
	if( $show == 'refsearches' ) echo '<strong>['; 
	echo '<a href="b2stats.php?show=refsearches&blog=', $blog, '">', T_('Refering Searches'), '</a>';
	if( $show == 'refsearches' ) echo ']</strong>';
	echo ' | ';
	if( $show == 'syndication' ) echo '<strong>[';
	echo '<a href="b2stats.php?show=syndication&blog=', $blog, '">', T_('Syndication'), '</a>';
	if( $show == 'syndication' ) echo ']</strong>';
	echo ' | ';
	if( $show == 'useragents' ) echo '<strong>[';
	echo '<a href="b2stats.php?show=useragents&blog=', $blog, '">', T_('User Agents'), '</a>';
	if( $show == 'useragents' ) echo ']</strong>';
	echo ' | ';
	if( $show == 'other' ) echo '<strong>[';
	echo '<a href="b2stats.php?show=other&blog=', $blog, '">', T_('Direct Accesses'), '</a>';
	if( $show == 'other' ) echo ']</strong>'; 

require(dirname(__FILE__).'/_menutop_end.php');

if ($user_level < 9 && ! $demo_mode) 
{
		die( '<p>'.T_('You have no right to view stats.').'</p>' );
}

switch( $action )
{
	case 'changetype':
		if ($user_level < 9) 
		{
				die( '<p>'.T_('You have no right to change a hit type.').'</p>' );
		}
		// Change the type of a hit:
		param( 'hit_ID', 'integer', true );	// Required!
		param( 'hit_type', 'string', true );	// Required!
		?>
		<div class="panelinfo">
			<p><?php printf( T_('Changing hit #%d type to: %s'), $hit_ID, $hit_type) ?></p>
			<?php 
			hit_change_type( $hit_ID, $hit_type );	
			?>
		</div>
		<?php
		break;

	case 'delete':
		// DELETE A HIT:
		if ($user_level < 9) 
		{
				die( '<p>'.T_('You have no right to delete a hit.').'</p>' );
		}
		param( 'hit_ID', 'integer', true );	// Required!
		?>
		<div class="panelinfo">
			<p><?php printf( T_('Deleting hit #%d...'), $hit_ID )?></p>
			<?php 
			hit_delete( $hit_ID );	
			?>
		</div>
		<?php
		break;

	case 'prune':
		// PRUNE hits for a certain date
		if ($user_level < 9) 
		{
				die( '<p>'.T_('You have no right to delete a hit.').'</p>' );
		}
		param( 'date', 'integer', true );	// Required!
		?>
		<div class="panelinfo">
			<p><?php printf( T_('Pruning hits for %s...'), date( locale_datefmt(), $date) ) ?></p>
			<?php
			hit_prune( $date );
			?>
		</div>
		<?php
		break;
}

function stats_blog_select()
{
	global $blog, $show;
	echo '<p>', T_('Filter'), ': ';
	if( $blog == 0 ) 
	{ // This is the blog being displayed on this page 
		echo '<strong>[<a href="b2stats.php?show=', $show, '&blog=0">', T_('None'), '</a>]</strong>';
	}
	else
	{ // This is another blog
		echo '<a href="b2stats.php?show=', $show, '&blog=0">', T_('None'), '</a>';
	} 
	for( $curr_blog_ID=blog_list_start('stub'); 
				$curr_blog_ID!=false; 
				 $curr_blog_ID=blog_list_next('stub') ) 
		{ 
		if( $curr_blog_ID == $blog ) { // This is the blog being displayed on this page ?>
			| <strong>[<a href="b2stats.php?show=<?php echo $show ?>&blog=<?php echo $curr_blog_ID ?>"><?php blog_list_iteminfo('shortname') ?></a>]</strong>
	<?php } else { // This is another blog ?>
			| <a href="b2stats.php?show=<?php echo $show ?>&blog=<?php echo $curr_blog_ID ?>"><?php blog_list_iteminfo('shortname') ?></a>
	<?php 
		} 
	} 
	echo '</p>';
}

switch( $show )
{
	case 'summary':
?>
<div class="panelblock">

	<?php stats_blog_select(); ?>

	<h3><?php echo T_('Summary') ?>:</h3>

	<?php
		$sql = "SELECT COUNT(*)AS hits, hit_ignore, YEAR(visitTime) AS year, MONTH(visitTime) AS month,  DAYOFMONTH(visitTime) AS day FROM $tablehitlog ";
		if( $blog > 0 )
		{
			$sql .= " WHERE hit_blog_ID = $blog ";
		}
		$sql .= ' GROUP BY YEAR(visitTime), MONTH(visitTime),  DAYOFMONTH(visitTime), hit_ignore ORDER BY YEAR(visitTime), MONTH(visitTime), DAYOFMONTH(visitTime)';
		$querycount++;
		$res_hits = mysql_query( $sql ) or mysql_oops( $sql );
		$hits = array();
		$hits['no'] = 0;
		$hits['invalid'] = 0;
		// $hits['badchar'] = 0;			// Not used any longer
		// $hits['blacklist'] = 0;			// Not used any longer	
		$hits['rss'] = 0;
		$hits['robot'] = 0;
		$hits['search'] = 0;
		$last_date = 0;
		?>
	<table class="thincols">
		<th><?php echo T_('Date') ?></th>
		<th><?php echo T_('Referers') // 'no' ?></th>
		<th><?php echo T_('Refering Searches') ?></th>
		<th><?php echo T_('Indexing Robots') ?></th>
		<th><?php echo T_('Syndication') ?></th>
		<th><?php echo T_('Direct Accesses') ?></th>
		<th><?php echo T_('Total') ?></th>
		<?php
		while($row_stats = mysql_fetch_array($res_hits))
		{
			$this_date = mktime( 0, 0, 0, $row_stats['month'], $row_stats['day'], $row_stats['year'] );
			if( $last_date == 0 ) $last_date = $this_date;	// that'll be the first one
			if( $last_date != $this_date )
			{	// We just hit a new day, let's display the previous one:
				?>
				<tr>
					<td><a href="b2stats.php?action=prune&date=<?php echo $last_date ?>&show=summary&blog=<?php echo $blog ?>" title="<?php echo T_('Prune this date!') ?>"><img src="img/xross.gif" width="13" height="13" class="middle" alt="<?php echo /* TRANS: Abbrev. for Prune (stats) */ T_('Prune') ?>"  title="<?php echo T_('Prune hits for this date!') ?>" /></a> <?php echo date( locale_datefmt(), $last_date ) ?></td>
					<td class="right"><?php echo $hits['no'] ?></td>
					<td class="right"><?php echo $hits['search'] ?></td>
					<td class="right"><?php echo $hits['robot'] ?></td>
					<td class="right"><?php echo $hits['rss'] ?></td>
					<td class="right"><?php echo $hits['invalid'] ?></td>
					<td class="right"><?php echo array_sum($hits) ?></td>
				</tr>
				<?php
					$hits['no'] = 0;
					$hits['invalid'] = 0;
					$hits['rss'] = 0;
					$hits['robot'] = 0;
					$hits['search'] = 0;
					$last_date = $this_date;	// that'll be the next one
			}
			$hits[$row_stats['hit_ignore']] = $row_stats['hits'];
		}

		if( $last_date != 0 )
		{	// We had a day pending:
			?>
			<tr>
				<td><a href="b2stats.php?action=prune&date=<?php echo $this_date ?>&show=summary&blog=<?php echo $blog ?>" title="<?php echo T_('Prune hits for this date!') ?>"><img src="img/xross.gif" width="13" height="13" class="middle" alt="<?php echo /* TRANS: Abbrev. for Prune (stats) */ T_('Prune') ?>"  title="<?php echo T_('Prune hits for this date!') ?>" /></a> <?php echo date( locale_datefmt(), $this_date ) ?></td>
				<td class="right"><?php echo $hits['no'] ?></td>
				<td class="right"><?php echo $hits['search'] ?></td>
				<td class="right"><?php echo $hits['robot'] ?></td>
				<td class="right"><?php echo $hits['rss'] ?></td>
				<td class="right"><?php echo $hits['invalid'] ?></td>
				<td class="right"><?php echo array_sum($hits) ?></td>
			</tr>
		<?php } ?>
		</table>
</div>

<?php
		break;
		
		case 'referers':
?>
<div class="panelblock">

	<?php stats_blog_select(); ?>

	<h3><?php echo T_('Last referers') ?>:</h3>
	<p><?php echo T_('These are hits from external web pages refering to this blog') ?>.</p>
	<?php refererList(40,'global',1,1,'no','',$blog); ?>
	<table class='thin'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
		<tr>
			<td><?php stats_time() ?></td>
			<td>
				<a href="b2stats.php?action=delete&hit_ID=<?php stats_hit_ID() ?>&show=referers&blog=<?php echo $blog ?>" title="<?php echo T_('Delete this hit!') ?>"><img src="img/xross.gif" width="13" height="13" class="middle" alt="<?php echo /* TRANS: Abbrev. for Delete (stats) */ T_('Del') ?>" title="<?php echo T_('Delete this hit!') ?>" /></a>
				[<a href="b2stats.php?action=changetype&hit_type=search&hit_ID=<?php stats_hit_ID() ?>&show=referers&blog=<?php echo $blog ?>" title="<?php echo T_('Log as a search instead') ?>"><?php echo /* TRANS: Abbrev. for "move to searches" (stats) */ T_('-&gt;S') ?></a>]
				<a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a>
			</td>
			<td><a href="b2antispam.php?action=ban&keyword=<?php stats_basedomain() ?>" title="<?php echo T_('Ban this domain!') ?>"><img src="img/noicon.gif" class="middle" alt="<?php echo /* TRANS: Abbrev. */ T_('Ban') ?>" title="<?php echo T_('Ban this domain!') ?>" /></a>
</td>
			<td><?php stats_blog_name() ?></td>
			<td><a href="<?php stats_req_URI() ?>"><?php stats_req_URI() ?></a></td>
		</tr>
		<?php } // End stat loop ?>
	</table>
	
	<h3><?php echo T_('Top referers') ?>:</h3>
	<?php refererList(30,'global',0,0,"'no'",'baseDomain',$blog,true); ?>
	<table class='invisible'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
			<tr>
				<td><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
				<td class="right"><?php stats_hit_count() ?></td>
				<td class="right"><?php stats_hit_percent() ?></td>
			</tr>
		<?php } // End stat loop ?>
	</table>
	<p><?php echo T_('Total referers') ?>: <?php stats_total_hit_count() ?></p>

</div>

<?php
		break;
		
		case 'refsearches':
?>

<div class="panelblock">

	<?php stats_blog_select(); ?>

	<h3><?php echo T_('Last refering searches') ?>:</h3>
	<p><?php echo T_('These are hits from people who came to this blog system through a search engine. (Search engines must be listed in /conf/_stats.php)') ?></p>
	<?php refererList(20,'global',1,1,"'search'",'',$blog); ?>
	<table class='thin'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
		<tr>
			<td><?php stats_time() ?></td>
			<td>
				<a href="b2stats.php?action=delete&hit_ID=<?php stats_hit_ID() ?>&show=refsearches&blog=<?php echo $blog ?>" title="<?php echo T_('Delete this hit!') ?>"><img src="img/xross.gif" width="13" height="13" class="middle" alt="<?php echo /* TRANS: Abbrev. for Delete (stats) */ T_('Del') ?>" /></a>
				<?php stats_basedomain() ?></td>
			<td><a href="<?php stats_referer() ?>"><?php stats_search_keywords() ?></a></td>
			<td><?php stats_blog_name() ?></td>
			<td><a href="<?php stats_req_URI() ?>"><?php stats_req_URI() ?></a></td>
		</tr>
		<?php } // End stat loop ?>
	</table>
	
	<h3><?php echo T_('Top refering search engines') ?>:</h3>
	<?php refererList(20,'global',0,0,"'search'",'baseDomain',$blog,true); ?>
	<table class='invisible'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
			<tr>
				<td><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
				<td class="right"><?php stats_hit_count() ?></td>
				<td class="right"><?php stats_hit_percent() ?></td>
			</tr>
		<?php } // End stat loop ?>
	</table>
	
	<h3><?php echo T_('Top Indexing Robots') ?>:</h3>
	<p><?php echo T_('These are hits from automated robots like search engines\' indexing robots. (Robots must be listed in /conf/_stats.php)') ?></p>
	<?php refererList(20,'global',0,0,"'robot'",'hit_user_agent',$blog,true,true); ?>
	<table class='invisible'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
			<tr>
				<td><?php stats_referer("<a href=\"", "\">") ?><?php stats_user_agent('robots') ?><?php stats_referer("", "</a>", false) ?></td>
				<td class="right"><?php stats_hit_count() ?></td>
				<td class="right"><?php stats_hit_percent() ?></td>
			</tr>
		<?php } // End stat loop ?>
	</table>

</div>

<?php
		break;
		
		case 'syndication':
?>

<div class="panelblock">

	<?php stats_blog_select(); ?>

	<h3><?php echo T_('Top Aggregators') ?>:</h3>
	<p><?php echo T_('These are hits from RSS news aggregators. (Aggregators must be listed in /conf/_stats.php)') ?></p>
	<?php refererList(40,'global',0,0,"'rss'",'hit_user_agent',$blog,true,true); ?>
	<table class='invisible'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
			<tr>
				<td><?php stats_user_agent(true) ?></td>
				<td class="right"><?php stats_hit_count() ?></td>
				<td class="right"><?php stats_hit_percent() ?></td>
			</tr>
		<?php } // End stat loop ?>
	</table>
	<p><?php echo T_('Total RSS hits') ?>: <?php stats_total_hit_count() ?></p>

</div>

<?php
		break;
		
		case 'other':
?>

<div class="panelblock">

	<?php stats_blog_select(); ?>

	<h3><?php echo T_('Last direct accesses') ?>:</h3>
	<p><?php echo T_('These are hits from people who came to this blog system by direct access (either by typing the URL directly, or using a bookmark. Invalid (too short) referers are also listed here.)') ?></p>
	<?php refererList(10,'global',1,1,"'invalid'",'',$blog); ?>
	<table class='thin'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
		<tr>
			<td><?php stats_time() ?></td>
			<td>
				<a href="b2stats.php?action=delete&hit_ID=<?php stats_hit_ID() ?>&show=other&blog=<?php echo $blog ?>" title="<?php echo T_('Delete this hit!') ?>"><img src="img/xross.gif" width="13" height="13" class="middle" alt="<?php echo /* TRANS: Abbrev. for Delete (stats) */ T_('Del') ?>" /></a>
				<a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
			<td><?php stats_blog_name() ?></td>
			<td><a href="<?php stats_req_URI() ?>"><?php stats_req_URI() ?></a></td>
		</tr>
		<?php } // End stat loop ?>
	</table>
	
</div>

<?php
		break;
		
		case 'useragents':
?>

<div class="panelblock">

	<?php stats_blog_select(); ?>

	<h3><?php echo T_('Top User Agents') ?>:</h3>
	<?php refererList(50,'global',0,0,"'no','invalid','badchar','blacklist','search'",'hit_user_agent',$blog,true,true); ?>
	<table class='invisible'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
			<tr>
				<td><?php stats_user_agent( false ) ?></td>
				<td class="right"><?php stats_hit_count() ?></td>
				<td class="right"><?php stats_hit_percent() ?></td>
			</tr>
		<?php } // End stat loop ?>
	</table>

</div>

<?php
		break;
}

require( dirname(__FILE__).'/_footer.php' ); 
?>
