<?php
require_once (dirname(__FILE__).'/_header.php');
require_once (dirname(__FILE__).'/'.$b2inc.'/_functions_hitlogs.php');						// referer logging
$title = _('View Stats');

set_param( 'action', 'string' );
set_param( 'show', 'string', 'referers' );
set_param( 'blog', 'string', '' );

require(dirname(__FILE__).'/_menutop.php');
?>
<span class="menutopbloglist">
	:
	<?php 
	if( $show == 'referers' ) echo '<strong>[';
	echo '<a href="b2stats.php?show=referers&blog=', $blog, '">', _('Referers'), '</a>';
	if( $show == 'referers' ) echo ']</strong>'; 
	echo ' | ';
	if( $show == 'refsearches' ) echo '<strong>['; 
	echo '<a href="b2stats.php?show=refsearches&blog=', $blog, '">', _('Refering Searches'), '</a>';
	if( $show == 'refsearches' ) echo ']</strong>';
	echo ' | ';
	if( $show == 'syndication' ) echo '<strong>[';
	echo '<a href="b2stats.php?show=syndication&blog=', $blog, '">', _('Syndication'), '</a>';
	if( $show == 'syndication' ) echo ']</strong>';
	echo ' | ';
	if( $show == 'useragents' ) echo '<strong>[';
	echo '<a href="b2stats.php?show=useragents&blog=', $blog, '">', _('User Agents'), '</a>';
	if( $show == 'useragents' ) echo ']</strong>';
	echo ' | ';
	if( $show == 'other' ) echo '<strong>[';
	echo '<a href="b2stats.php?show=other&blog=', $blog, '">', _('Other'), '</a>';
	if( $show == 'other' ) echo ']</strong>'; 
	?>
</span>
<?php
require(dirname(__FILE__).'/_menutop_end.php');

if ($user_level < 9) 
{
		die( _('You have no right to view stats.') );
}

switch( $action )
{
	case 'changetype':
		// Change the type of a hit:
		set_param( 'hit_ID', 'integer', true );	// Required!
		set_param( 'hit_type', 'string', true );	// Required!
		?>
		<div class="panelinfo">
		<p><?php printf( _('Changing hit #%d type to: %s'), $hit_ID, $hit_type) ?></p>
		<?php 
		hit_change_type( $hit_ID, $hit_type );	
		?>
		</div>
		<?php
		break;

	case 'delete':
		// DELETE A HIT:
		set_param( 'hit_ID', 'integer', true );	// Required!
		?>
		<div class="panelinfo">
		<p><?php printf( _('Deleting hit #%d...'), $hit_ID )?></p>
		<?php 
		hit_delete( $hit_ID );	
		?>
		</div>
		<?php
		break;
}

function stats_blog_select()
{
	global $blog, $show;
	echo '<p>', _('Filter'), ': ';
	if( empty($blog) ) 
	{ // This is the blog being displayed on this page 
		echo '<strong>[<a href="b2stats.php?show=', $show, '">', _('None'), '</a>]</strong>';
	}
	else
	{ // This is another blog
		echo '<a href="b2stats.php?show=', $show, '">', _('None'), '</a>';
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
	case 'referers':
?>

<div class="panelblock">

	<?php stats_blog_select(); ?>

	<h3><?php echo _('Last referers') ?>:</h3>
	<p><?php echo _('These are hits from external web pages refering to this blog') ?>.</p>
	<?php refererList(40,'global',1,1,'no','',$blog); ?>
	<table class='thin'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
		<tr>
			<td>
				[<a href="b2stats.php?action=delete&hit_ID=<?php stats_hit_ID() ?>&show=referers" title="<?php echo _('Delete this hit!') ?>"><?php echo /* TRANS: Abbrev. for Delete (stats) */ _('Del') ?></a>]
				[<a href="b2stats.php?action=changetype&hit_type=search&hit_ID=<?php stats_hit_ID() ?>&show=referers" title="<?php echo _('Log as a search instead') ?>"><?php echo /* TRANS: Abbrev. for "move to searches" (stats) */ _('-&gt;S') ?></a>]
				<a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a>
			</td>
			<td><?php stats_blog_name() ?></td>
			<td><a href="<?php stats_req_URI() ?>"><?php stats_req_URI() ?></a></td>
		</tr>
		<?php } // End stat loop ?>
	</table>
	
	<h3><?php echo _('Top referers') ?>:</h3>
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
	<p><?php echo _('Total referers') ?>: <?php stats_total_hit_count() ?></p>

</div>

<?php
		break;
		
		case 'refsearches':
?>

<div class="panelblock">

	<?php stats_blog_select(); ?>

	<h3><?php echo _('Last refering searches') ?>:</h3>
	<p><?php echo _('These are hits from people who came to this blog system through a search engine. (Search engines must be listed in /conf/b2evo_advanced.php)') ?></p>
	<?php refererList(20,'global',1,1,"'search'",'',$blog); ?>
	<table class='thin'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
		<tr>
			<td>
				[<a href="b2stats.php?action=delete&hit_ID=<?php stats_hit_ID() ?>&show=refsearches" title="<?php echo _('Delete this hit!') ?>"><?php echo _('Del') ?></a>]
				<?php stats_basedomain() ?></td>
			<td><a href="<?php stats_referer() ?>"><?php stats_search_keywords() ?></a></td>
			<td><?php stats_blog_name() ?></td>
			<td><a href="<?php stats_req_URI() ?>"><?php stats_req_URI() ?></a></td>
		</tr>
		<?php } // End stat loop ?>
	</table>
	
	<h3><?php echo _('Top refering search engines') ?>:</h3>
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
	
	<h3><?php echo _('Top Indexing Robots') ?>:</h3>
	<p><?php echo _('These are hits from automated robots like search engines\' indexing robots. (Robots must be listed in /conf/b2evo_advanced.php)') ?></p>
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

	<h3><?php echo _('Top Aggregators') ?>:</h3>
	<p><?php echo _('These are hits from RSS news aggregators. (Aggregators must be listed in /conf/b2evo_advanced.php)') ?></p>
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
	<p><?php echo _('Total RSS hits') ?>: <?php stats_total_hit_count() ?></p>

</div>

<?php
		break;
		
		case 'other':
?>

<div class="panelblock">

	<?php stats_blog_select(); ?>

	<h3><?php echo _('Last direct accesses') ?>:</h3>
	<p><?php echo _('These are hits from people who came to this blog system by direct access (either by typing the URL directly, or using a bookmark. Invalid (too short) referers are also listed here.)') ?></p>
	<?php refererList(10,'global',1,1,"'invalid'",'',$blog); ?>
	<table class='thin'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
		<tr>
			<td>
				[<a href="b2stats.php?action=delete&hit_ID=<?php stats_hit_ID() ?>&show=other" title="<?php echo _('Delete this hit!') ?>"><?php echo _('Del') ?></a>]
				<a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
			<td><?php stats_blog_name() ?></td>
			<td><a href="<?php stats_req_URI() ?>"><?php stats_req_URI() ?></a></td>
		</tr>
		<?php } // End stat loop ?>
	</table>
	
	<h3><?php echo _('Last blacklisted referers') ?>:</h3>
	<p><?php echo _('These are hits from people who came to this blog system through a blacklisted page. (Blacklist must be defined in /conf/b2evo_advanced.php. By default the blacklist includes all internal references.)') ?></p>
	<?php refererList(10,'global',1,1,"'blacklist'",'',$blog); ?>
	<table class='thin'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
		<tr>
			<td>
				[<a href="b2stats.php?action=delete&hit_ID=<?php stats_hit_ID() ?>&show=other" title="<?php echo _('Delete this hit!') ?>"><?php echo _('Del') ?></a>]
				<a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
			<td><?php stats_blog_name() ?></td>
			<td><a href="<?php stats_req_URI() ?>"><?php stats_req_URI() ?></a></td>
		</tr>
		<?php } // End stat loop ?>
	</table>
	
	<h3><?php echo _('Last bad chars') ?>:</h3>
	<p><?php echo _('These are hits with bad chars in the referer.') ?></p>
	<?php refererList(10,'global',1,1,"'badchar'",'',$blog); ?>
	<table class='thin'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
		<tr>
			<td>
				[<a href="b2stats.php?action=delete&hit_ID=<?php stats_hit_ID() ?>&show=other" title="<?php echo _('Delete this hit!') ?>"><?php echo _('Del') ?></a>]
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

	<h3>Top User Agents:</h3>
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