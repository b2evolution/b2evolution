<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */
require_once (dirname(__FILE__).'/_header.php');
require_once (dirname(__FILE__).'/'.$admin_dirout.'/'.$core_subdir.'/_functions_antispam.php');
$title = T_('Antispam');

param( 'action', 'string' );
param( 'confirm', 'string' );

require(dirname(__FILE__).'/_menutop.php');
require(dirname(__FILE__).'/_menutop_end.php');

if ($user_level < 9 && ! $demo_mode) 
{
		die( '<p>'.T_('You have no right to edit the blacklist.').'</p>' );
}

switch( $action )
{
	case 'ban':
		if ($user_level < 9)
		{
			die( '<p>'.T_('You have no right to edit the blacklist.').'</p>' );
		}

		param( 'keyword', 'string', true );	// Required!

		if ( $deluxe_ban && ! $confirm )
		{
			// Show confirmation page:
			?>
			<div class="panelblock">
				<h2><?php echo T_('Confirm ban &amp; delete') ?></h2>
				<?php ban_affected_comments($keyword, 'keyword') ?>
				<p><?php printf ( T_('Banning the keyword %s from the statistics and comments would lead to the deletion of the following %d comments:'), $keyword, mysql_affected_rows() ); ?></p>
				<table class="thin">
					<?php while($row_stats = mysql_fetch_array($res_affected_comments)){ ?>
					<tr>
						<td><?php
						preg_match("/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/", $row_stats['comment_date'], $matches);
						$date = mktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]);
						echo date(locale_datefmt()." ".locale_timefmt(), $date);
						?></td>
						<td><?php echo $row_stats['comment_author'] ?></a></td>
						<td><?php echo $row_stats['comment_author_url'] ?></td>
						<td><?php
						$comment_content = preg_replace("/<br \/>/", '', $row_stats['comment_content']);
						if ( strlen($comment_content) > 70 )
						{
							// Trail off (truncate and add '...') after 70 chars
							echo substr($comment_content, 0, 70) . "...";
						}
						else
						{
							echo $comment_content;
						}
						?></td>
					</tr>
					<?php } // End stat loop ?>
				</table>
				
				<?php ban_affected_hits($keyword, 'keyword') ?>
				<p><?php printf ( T_('...and the following %d referer hits:'), mysql_affected_rows() ) ?></p>
				<table class="thin">
					<?php while($row_stats = mysql_fetch_array($res_affected_hits)){  ?>
					<tr>
						<td><?php stats_time() ?></td>
						<td><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
						<td><?php stats_blog_name() ?></td>
						<td><a href="<?php stats_req_URI() ?>"><?php stats_req_URI() ?></a></td>
					</tr>
					<?php } // End stat loop ?>
				</table>
				
				<p><?php echo T_('Are you sure you want to continue?') ?></p>
				<form action="b2antispam.php" method="get">
					<input type="hidden" name="confirm" value="confirm" />
					<input type="hidden" name="keyword" value="$keyword" />
					<input type="hidden" name="action" value="ban" />
					<input type="submit" value="<?php echo T_('Ban the keyword + delete matching hits and comments') ?>" class="search" />
				</form>
			</div>
			<?php
		}
		else
		{	// BAN a keyword + (if requested) DELETE stats entries and comments:
			keyword_ban( $keyword );
		}
		break;

		
	case 'remove':
		// Remove a domain from ban list:
		if ($user_level < 9)
		{
			die( '<p>'.T_('You have no right to edit the blacklist.').'</p>' );
		}
		param( 'hit_ID', 'integer', true );	// Required!
		?>
		<div class="panelinfo">
			<p><?php printf( T_('Removing entry #%d from the ban list...'), $hit_ID) ?></p>
			<?php 
			remove_ban( $hit_ID );
			?>
		</div>
		<?php
		break;


	case 'report':
		// Report an entry as abuse to centralized blacklist:
		param( 'keyword', 'string', true );	// Required!
		// Report this keyword as abuse:
		b2evonet_report_abuse( $keyword );
		break;


	case 'poll':
		// request abuse list from central blacklist:
		b2evonet_poll_abuse( );
		break;
}
?>

<div class="panelblock">
	<h2><?php echo T_('Banned domains blacklist') ?></h2>
	<p><?php echo T_('Any URL containing one of the following keywords will be banned from posts, comments and logs. If a keyword restricts legitimate domains, click on the green tick to stop banning with this keyword.') ?></p>
	<?php list_antiSpam() ?>
	<table class='thin'>
		<?php while( $row_stats = mysql_fetch_array($res_stats) ) {  ?>
		<tr>
			<td>
				<a href="b2antispam.php?action=remove&hit_ID=<?php antiSpam_ID() ?>" title="<?php echo T_('Allow keyword back (Remove it from the blacklist)') ?>"><img src="img/tick.gif" width="13" height="13" class="middle" alt="<?php echo T_('Allow Back') ?>" /></a>
				<?php antiSpam_domain() ?>
			</td>
			<td>[<a href="b2antispam.php?action=report&keyword=<?php antiSpam_domain() ?>" title="<?php echo T_('Report abuse to centralized ban blacklist!') ?>">Report</a>]</td>
		</tr>
		<?php } // End stat loop ?>
	</table>
	<p>[<a href="b2antispam.php?action=poll"><?php echo T_('Request abuse update from centralized blacklist.') ?></a>]</p>
</div>

<div class="panelblock">
	<h2><?php echo T_('Add a banned keyword') ?></h2>
	<form action="b2antispam.php" method="GET">
		<p>
		<?php echo T_('Keyword') ?>: <input type="text" size="30" name="keyword" />
		<input type="hidden" name="action" value="ban" />
		<input type="hidden" name="type" value="keyword" />
		<input type="submit" value="<?php echo T_('Ban this keyword!') ?>" class="search" />
		</p>
	</form>
</div>
<?php

require( dirname(__FILE__).'/_footer.php' ); 
?>
