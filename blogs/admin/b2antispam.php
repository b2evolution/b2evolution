<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 */
require_once (dirname(__FILE__).'/_header.php');
require_once (dirname(__FILE__).'/'.$admin_dirout.'/'.$core_subdir.'/_functions_hitlogs.php');	// referer logging
$title = T_('Anti-Spam');

param( 'action', 'string' );
param( 'confirm', 'string' );

require(dirname(__FILE__).'/_menutop.php');
require(dirname(__FILE__).'/_menutop_end.php');

if ($user_level < 9 && ! $demo_mode) 
{
		die( '<p>'.T_('You have no right to view the blacklist.').'</p>' );
}

switch( $action )
{
	// 		param( 'hit_ID', 'integer', true );	// Required!
	case 'bankeyword':
		if ($user_level < 9 && ! $demo_mode)
		{
			die( '<p>'.T_('You have no right to ban domains.').'</p>' );
		}

		param( 'keyword', 'string', true );	// Required!

		if ( $deluxe_ban && ! $confirm )
		{
			// Show confirmation page:
			?>
			<div class="panelblock">
				<h3><?php echo T_('Confirm ban &amp; delete') ?></h3>
				<?php ban_affected_comments($keyword) ?>
				<p><?php printf ( T_('Banning the keyword %s from the statistics and comments would lead to the deletion of the following %d comments:'), $keyword, mysql_affected_rows($res_stats) ) ?></p>
				<table class="thin">
					<?php while($row_stats = mysql_fetch_array($res_stats)){ ?>
					<tr>
						<td><?php echo $row_stats['comment_date'] ?></td>
						<td><?php echo $row_stats['comment_author'] ?></a></td>
						<td><?php echo $row_stats['comment_author_url'] ?></td>
						<td><?php
						$comment_content = preg_replace("/<br \/>/", '', $row_stats['comment_content']);
						if ( strlen($comment_content) > 60 )
						{
							// Trail off (truncate and add '...') after 60 chars
							echo substr($comment_content, 0, 60) . "...";
						}
						else
						{
							echo $comment_content;
						}
						?></td>
					</tr>
					<?php } // End stat loop ?>
				</table>
				
				<?php ban_affected_hits($keyword) ?>
				<p><?php printf ( T_('...and the following %d referer hits:'), mysql_affected_rows($res_stats) ) ?></p>
				<table class="thin">
					<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
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
					<p>
					<input type="hidden" name="confirm" value="confirm" />
					<input type="hidden" name="keyword" value="<?php echo $keyword ?>" />
					<input type="hidden" name="action" value="ban" />
					<input type="submit" value="<?php echo T_(' BAN ') ?>" class="search" />
					</p>
				</form>
			</div>
			<?php
		}
		elseif ( $deluxe_ban && $confirm )
		{
			// BAN a keyword, DELETE stats entries and comments:
			?>
			<div class="panelinfo">
				<p><?php printf( T_('Banning the keyword %s, and removing all related comments and hits...'), $keyword) ?></p>
				<?php 
				keyword_ban( $keyword );
				?>
			</div>
			<?php
		}
		elseif ( ! $deluxe_ban )
		{
			// BAN a keyword, leaving stats entries and comments in place:
			?>
			<div class="panelinfo">
				<p><?php printf( T_('Banning the keyword %s...'), $keyword) ?></p>
				<?php 
				keyword_ban( $keyword );
				?>
			</div>
			<?php
		}
		// Should there be an "else" clause here?
		break;

	case 'banhit':
		if ($user_level < 9 && ! $demo_mode)
		{
			die( '<p>'.T_('You have no right to ban domains.').'</p>' );
		}

		param( 'hit_ID', 'integer', true );	// Required!

		if ( $deluxe_ban && ! $confirm )
		{
			// Show confirmation page:
			?>
			<div class="panelblock">
				<h3><?php echo T_('Confirm ban &amp; delete') ?></h3>
				<?php ban_affected_comments($keyword) ?>
				<p><?php printf ( T_('Banning the domain %s from the statistics and comments would lead to the deletion of the following %d comments:'), $keyword, mysql_affected_rows($res_stats) ) ?></p>
				<table class="thin">
					<?php while($row_stats = mysql_fetch_array($res_stats)){ ?>
					<tr>
						<td><?php echo $row_stats['comment_date'] ?></td>
						<td><?php echo $row_stats['comment_author'] ?></a></td>
						<td><?php echo $row_stats['comment_author_url'] ?></td>
						<td><?php
						$comment_content = preg_replace("/<br \/>/", '', $row_stats['comment_content']);
						if ( strlen($comment_content) > 60 )
						{
							// Trail off (truncate and add '...') after 60 chars
							echo substr($comment_content, 0, 60) . "...";
						}
						else
						{
							echo $comment_content;
						}
						?></td>
					</tr>
					<?php } // End stat loop ?>
				</table>
				
				<?php ban_affected_hits($keyword) ?>
				<p><?php printf ( T_('...and the following %d referer hits:'), mysql_affected_rows($res_stats) ) ?></p>
				<table class="thin">
					<?php while($row_stats = mysql_fetch_array($res_stats)){  ?>
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
					<p>
					<input type="hidden" name="confirm" value="confirm" />
					<input type="hidden" name="keyword" value="<?php echo $keyword ?>" />
					<input type="hidden" name="action" value="ban" />
					<input type="submit" value="<?php echo T_(' BAN ') ?>" class="search" />
					</p>
				</form>
			</div>
			<?php
		}
		elseif ( $deluxe_ban && $confirm )
		{
			// BAN a domain, DELETE stats entries and comments:
			?>
			<div class="panelinfo">
				<p><?php printf( T_('Banning the referer of hit #%d, and removing all their comments and hits...'), $hit_ID) ?></p>
				<?php 
				domain_ban( $hit_ID );
				?>
			</div>
			<?php
		}
		elseif ( ! $deluxe_ban )
		{
			// BAN a domain, leaving stats entries and comments in place:
			
			?>
			<div class="panelinfo">
				<p><?php printf( T_('Banning the referer of hit #%d...'), $hit_ID) ?></p>
				<?php 
				domain_ban( $hit_ID );
				?>
			</div>
		<?php
		}
		// Should there be an "else" clause here?
		break;
		
	case 'remove':
		// Remove a domain from ban list:
		if ( $user_level < 9 && ! $demo_mode )
		{
				die( '<p>'.T_('You have no right to remove domains from the ban list.').'</p>' );
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
}
?>

<div class="panelblock">

	<h3><?php echo T_('Ban list') ?>:</h3>
	<p><?php echo T_('Domains containing the listed keywords are banned from logging hits and commenting. To allow comments and log hits on your blogs from a banned domain, click the cross to remove it from the list.') ?></p>
	<?php list_blackList() ?>
	<table class='thin'>
		<?php while( $row_stats = mysql_fetch_array($res_stats) ) {  ?>
		<tr>
			<td>
				<a href="b2antispam.php?action=remove&hit_ID=<?php antiSpam_ID() ?>" title="<?php echo T_('Remove from blacklist') ?>"><img src="img/xross.gif" width="13" height="13" class="middle" alt="<?php echo /* TRANS: Abbrev. for Delete (stats) */ T_('Del') ?>" /></a>
				<?php antiSpam_domain() ?>
			</td>
		</tr>
		<?php } // End stat loop ?>
	</table>

</div>

<div class="panelblock">
	<h3><?php echo T_('Ban by keyword') ?>:</h3>
	<form action="b2antispam.php" method="GET">
		<p>
		<?php echo T_('Keyword') ?>: <input type="text" size="30" name="keyword" /> &nbsp; 
		<input type="hidden" name="action" value="ban" />
		<input type="submit" value="<?php echo T_(' BAN ') ?>" class="search" style="font-weight:bold;" />
		</p>
	</form>
</div>
<?php

require( dirname(__FILE__).'/_footer.php' ); 
?>
