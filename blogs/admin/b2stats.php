<?php
$title = "View Stats";
$standalone=1;
require_once (dirname(__FILE__)."/b2header.php");
require_once (dirname(__FILE__).'/'.$b2inc.'/_functions_hitlogs.php');						// referer logging

set_param( 'action', 'string' );
set_param( 'show', 'string', 'referers' );
set_param( 'blog', 'string', '' );

include($b2inc."/_menutop.php");
?>
<span class="menutopbloglist">
	:
	<?php if( $show == 'referers' ) echo '<strong>['; ?>
	<a href="b2stats.php?show=referers&blog=<?php echo $blog ?>">Referers</a>
	<?php if( $show == 'referers' ) echo ']</strong>'; ?>
	|
	<?php if( $show == 'refsearches' ) echo '<strong>['; ?>
	<a href="b2stats.php?show=refsearches&blog=<?php echo $blog ?>">Refering Searches</a>
	<?php if( $show == 'refsearches' ) echo ']</strong>'; ?>
	|
	<?php if( $show == 'syndication' ) echo '<strong>['; ?>
	<a href="b2stats.php?show=syndication&blog=<?php echo $blog ?>">Syndication</a>
	<?php if( $show == 'syndication' ) echo ']</strong>'; ?>
	|
	<?php if( $show == 'useragents' ) echo '<strong>['; ?>
	<a href="b2stats.php?show=useragents&blog=<?php echo $blog ?>">User Agents</a>
	<?php if( $show == 'useragents' ) echo ']</strong>'; ?>
	|
	<?php if( $show == 'other' ) echo '<strong>['; ?>
	<a href="b2stats.php?show=other&blog=<?php echo $blog ?>">Other</a>
	<?php if( $show == 'other' ) echo ']</strong>'; ?>
</span>
<?php
include($b2inc."/_menutop_end.php");

if ($user_level < 9) 
{
	die("You have no right to view stats.<br>Ask for a promotion to your <a href=\"mailto:$admin_email\">blog admin</a> :)");
}

switch( $action )
{
	case 'changetype':
		// Change the type of a hit:
		set_param( 'hit_ID', 'integer', true );	// Required!
		set_param( 'hit_type', 'string', true );	// Required!
		?>
		<div class="panelinfo">
		<p>Changing hit #<?php echo $hit_ID ?> type to: <?php echo $hit_type ?></p>
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
		<p>Deleting hit #<?php echo $hit_ID ?>...</p>
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
	echo '<p>Filter: ';
	if( empty($blog) ) { // This is the blog being displayed on this page ?>
	<strong>[<a href="b2stats.php?show=<?php echo $show ?>">None</a>]</strong>
	<?php } else { // This is another blog ?>
	<a href="b2stats.php?show=<?php echo $show ?>">None</a>
	<?php 
	} 
	for( $curr_blog_ID=blog_list_start('stub'); 
				$curr_blog_ID!=false; 
				 $curr_blog_ID=blog_list_next('stub') ) 
		{ 
		echo $sep;
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

	<h3>Last referers:</h3>
	<p>These are hits from external web pages refering to this blog.</p>
	<?php refererList(40,'global',1,1,'no','',$blog); ?>
	<table class='thin'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
		<tr>
			<td>
				[<a href="b2stats.php?action=delete&hit_ID=<?php stats_hit_ID() ?>&show=referers" title="Delete this hit!">Del</a>]
				[<a href="b2stats.php?action=changetype&hit_type=search&hit_ID=<?php stats_hit_ID() ?>&show=referers" title="Log as a search instead">-&gt;S</a>]
				<a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a>
			</td>
			<td><?php stats_blog_name() ?></td>
			<td><a href="<?php stats_req_URI() ?>"><?php stats_req_URI() ?></a></td>
		</tr>
		<?php } // End stat loop ?>
	</table>
	
	<h3>Top referers:</h3>
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
	<p>Total referers: <?php stats_total_hit_count() ?></p>

</div>

<?php
		break;
		
		case 'refsearches':
?>

<div class="panelblock">

	<?php stats_blog_select(); ?>

	<h3>Last refering searches:</h3>
	<p>These are hits from people who came to this blog system through a search engine. (Search engines must be listed in /conf/b2evo_advanced.php)</p>
	<?php refererList(20,'global',1,1,"'search'",'',$blog); ?>
	<table class='thin'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
		<tr>
			<td>
				[<a href="b2stats.php?action=delete&hit_ID=<?php stats_hit_ID() ?>&show=refsearches" title="Delete this hit!">Del</a>]
				<?php stats_basedomain() ?></td>
			<td><a href="<?php stats_referer() ?>"><?php stats_search_keywords() ?></a></td>
			<td><?php stats_blog_name() ?></td>
			<td><a href="<?php stats_req_URI() ?>"><?php stats_req_URI() ?></a></td>
		</tr>
		<?php } // End stat loop ?>
	</table>
	
	<h3>Top refering search engines:</h3>
	<?php refererList(20,'global',0,0,"'search'",'baseDomain',$blogtrue); ?>
	<table class='invisible'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
			<tr>
				<td><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
				<td class="right"><?php stats_hit_count() ?></td>
				<td class="right"><?php stats_hit_percent() ?></td>
			</tr>
		<?php } // End stat loop ?>
	</table>
	
	<h3>Top Indexing Robots:</h3>
	<p>These are hits from automated robots like search engines' indexing robots. (Robots must be listed in /conf/b2evo_advanced.php)</p>
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

	<h3>Top Aggregators:</h3>
	<p>These are hits from RSS news aggregators. (Aggregators must be listed in /conf/b2evo_advanced.php)</p>
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
	<p>Total RSS hits: <?php stats_total_hit_count() ?></p>

</div>

<?php
		break;
		
		case 'other':
?>

<div class="panelblock">

	<?php stats_blog_select(); ?>

	<h3>Last direct accesses:</h3>
	<p>These are hits from people who came to this blog system by direct access (either by typing the URL directly, or using a bookmark. Invalid (too short) referers are also listed here.)</p>
	<?php refererList(10,'global',1,1,"'invalid'",'',$blog); ?>
	<table class='thin'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
		<tr>
			<td>
				[<a href="b2stats.php?action=delete&hit_ID=<?php stats_hit_ID() ?>&show=other" title="Delete this hit!">Del</a>]
				<a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
			<td><?php stats_blog_name() ?></td>
			<td><a href="<?php stats_req_URI() ?>"><?php stats_req_URI() ?></a></td>
		</tr>
		<?php } // End stat loop ?>
	</table>
	
	<h3>Last blacklisted referers:</h3>
	<p>These are hits from people who came to this blog system through a blacklisted page. (Blacklist must be defined in /conf/b2evo_advanced.php. By default the blacklist includes all internal references.)</p>
	<?php refererList(10,'global',1,1,"'blacklist'",'',$blog); ?>
	<table class='thin'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
		<tr>
			<td>
				[<a href="b2stats.php?action=delete&hit_ID=<?php stats_hit_ID() ?>&show=other" title="Delete this hit!">Del</a>]
				<a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
			<td><?php stats_blog_name() ?></td>
			<td><a href="<?php stats_req_URI() ?>"><?php stats_req_URI() ?></a></td>
		</tr>
		<?php } // End stat loop ?>
	</table>
	
	<h3>Last bad chars:</h3>
	<p>These are hits with bad chars in the referer.</p>
	<?php refererList(10,'global',1,1,"'badchar'",'',$blog); ?>
	<table class='thin'>
		<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
		<tr>
			<td>
				[<a href="b2stats.php?action=delete&hit_ID=<?php stats_hit_ID() ?>&show=other" title="Delete this hit!">Del</a>]
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

	include($b2inc."/_footer.php"); 
?>