<?php
/**
 * Antispam blacklist handling
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
require_once (dirname(__FILE__).'/_header.php');
require_once (dirname(__FILE__).'/'.$admin_dirout.'/'.$core_subdir.'/_functions_antispam.php');
$admin_tab = 'antispam';
$admin_pagetitle = T_('Antispam');

param( 'action', 'string' );
param( 'confirm', 'string' );
param( 'keyword', 'string' );

require(dirname(__FILE__).'/_menutop.php');
require(dirname(__FILE__).'/_menutop_end.php');

// Check permission:
$current_User->check_perm( 'spamblacklist', 'view', true );

switch( $action )
{
	case 'ban':
		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		$keyword = substr( $keyword, 0, 80 );
		$dbkeyword = $DB->escape( $keyword );
		param( 'delhits', 'integer', 0 );
		param( 'delcomments', 'integer', 0 );
		param( 'blacklist', 'integer', 0 );
		param( 'report', 'integer', 0 );

		// Check if the string is too short, 
		// it has to be a minimum of 5 characters to avoid being too generic
		if( strlen($keyword) < 5 )
		{
			echo '<div class="panelinfo">';
			printf( '<p>'.T_('The keyword [%s] is too short, it has to be a minimum of 5 characters!').'</p>', $keyword);
			echo '</div>';
			break;
		}

		if( $delhits && $deluxe_ban )
		{	// Delete all banned hit-log entries
			echo '<div class="panelinfo">';
			printf( '<h3>'.T_('Deleting log-hits matching [%s]...').'</h3>', $keyword );
			// Stats entries first
			$sql = "DELETE FROM $tablehitlog 
							WHERE referingURL LIKE '%$dbkeyword%'";
			$DB->query($sql);
			echo '</div>';
		}
					
		if( $delcomments && $deluxe_ban )
		{ // Then all banned comments
			echo '<div class="panelinfo">';
			printf( '<h3>'.T_('Deleting comments matching [%s]...').'</h3>', $keyword );
			$sql = "DELETE FROM $tablecomments 
							WHERE comment_author_url LIKE '%$dbkeyword%'";	
			$DB->query($sql);
			echo '</div>';
		}
		
		if( $blacklist )
		{	// Local blacklist:
			echo '<div class="panelinfo">';
			printf( '<h3>'.T_('Blacklisting the keyword [%s]...').'</h3>', $keyword );
			// Insert into DB:
			antispam_create( $keyword );
			echo '</div>';
		}
			
		if( $report && $report_abuse )
		{ // Report this keyword as abuse:
			b2evonet_report_abuse( $keyword );
		}
		
		if( !( $delhits || $delcomments || $blacklist || $report ) )
		{	// Nothing to do, ask user:
			?>
			<div class="panelblock">
				<form action="b2antispam.php" method="post">
				<input type="hidden" name="confirm" value="confirm" />
				<input type="hidden" name="keyword" value="<?php echo $keyword ?>" />
				<input type="hidden" name="action" value="ban" />
				<h2><?php echo T_('Confirm ban &amp; delete') ?></h2>

				<?php
				if( $deluxe_ban )
				{	// We can we autodelete junk, check for junk:
					// Check for potentially affected log hits:
					$sql = "SELECT visitID, UNIX_TIMESTAMP(visitTime) AS visitTime, referingURL,
												 baseDomain, hit_blog_ID, visitURL 
												 FROM $tablehitlog 
												 WHERE referingURL LIKE '%$dbkeyword%' 
												 ORDER BY baseDomain ASC";
					$res_affected_hits = $DB->get_results( $sql, ARRAY_A );
					if( $DB->num_rows == 0 )
					{	// No matching hits.
						printf( '<p><strong>'.T_('No log-hits match the keyword [%s].').'</strong></p>', format_to_output( $keyword, 'htmlbody' ) );
					}
					else
					{
					?>
						<p><strong><input type="checkbox" name="delhits" value="1" checked="checked" />
						<?php printf ( T_('Delete the following %d referer hits:'), $DB->num_rows ) ?>
						</strong></p>
						<table class="thin">
							<?php foreach( $res_affected_hits as $row_stats ) 
							{  ?>
							<tr>
								<td><?php stats_time() ?></td>
								<td><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
								<td><?php stats_blog_name() ?></td>
								<td><a href="<?php stats_req_URI() ?>"><?php stats_req_URI() ?></a></td>
							</tr>
							<?php } // End stat loop ?>
						</table>
					<?php
					}
	
					// Check for potentially affected comments:
					$sql = "SELECT * 
									FROM $tablecomments 
									WHERE comment_author_url LIKE '%$dbkeyword%' 
									ORDER BY comment_date ASC";
					$res_affected_comments = $DB->get_results( $sql, ARRAY_A );
					if( $DB->num_rows == 0 )
					{	// No matching hits.
						printf( '<p><strong>'.T_('No comments match the keyword [%s].').'</strong></p>', format_to_output( $keyword, 'htmlbody' ) );
					}
					else
					{
					?>
						<p><strong><input type="checkbox" name="delcomments" value="1" checked="checked" />
						<?php printf ( T_('Delete the following %d comments:'), mysql_affected_rows() ) ?>
						</strong></p>
						<table class="thin">
							<?php foreach( $res_affected_comments as $row_stats )
							{ // TODO: new Comment( $row_stats ) ?>
							<tr>
								<td><?php echo mysql2date(locale_datefmt().' '.locale_timefmt(), $row_stats['comment_date'] ); ?></td>
								<td><?php echo $row_stats['comment_author'] ?></a></td>
								<td><?php echo $row_stats['comment_author_url'] ?></td>
								<td><?php
								$comment_content = strip_tags( $row_stats['comment_content'] );
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
					<?php 
					} 
				}
					
				// Check if the string is already in the blacklist:
				if( antispam_url($keyword) )
				{ // Already there:
					printf( '<p><strong>'.T_('The keyword [%s] is already handled by the blacklist.').'</strong></p>', $keyword );
				}
				else
				{ // Not in blacklist
				  ?>
					<p><strong><input type="checkbox" name="blacklist" value="1" checked="checked" />
					<?php printf ( T_('Blacklist the keyword [%s] locally.'), format_to_output( $keyword, 'htmlbody' ) ) ?>
					</strong></p>

					<?php if( $report_abuse ) 
					{ ?>
						<p>
						<strong><input type="checkbox" name="report" value="1" checked="checked" />
						<?php printf ( T_('Report the keyword [%s] as abuse to b2evolution.net.'), format_to_output( $keyword, 'htmlbody' ) ) ?>
						</strong>
						[<a href="http://b2evolution.net/about/terms.html"><?php echo T_('Terms of service') ?></a>]
						</p>					
					<?php
					}
				}
				?>				
				
				<input type="submit" value="<?php echo T_('Perform selected operations') ?>" class="search" />
				</form>
			</div>
			<?php
		}
		break;

		
	case 'remove':
		// Remove a domain from ban list:

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		param( 'hit_ID', 'integer', true );	// Required!
		?>
		<div class="panelinfo">
			<p><?php printf( T_('Removing entry #%d from the ban list...'), $hit_ID) ?></p>
			<?php 
			antispam_delete( $hit_ID );
			?>
		</div>
		<?php
		break;


	case 'report':
		// Report an entry as abuse to centralized blacklist:

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		// Report this keyword as abuse:
		b2evonet_report_abuse( $keyword );
		break;


	case 'poll':
		// request abuse list from central blacklist:

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		b2evonet_poll_abuse( );
		break;
}


if( $current_User->check_perm( 'spamblacklist', 'edit' ) ) 
{ ?>
	<div class="panelblock">
		<form action="b2antispam.php" method="GET" class="fform">
			<input type="hidden" name="action" value="ban" />
			<input type="hidden" name="type" value="keyword" />
			<label for="keyword"><strong><?php echo T_('Add a banned keyword') ?>:</strong></label>
			<input type="text" name="keyword" id="keyword" size="30" maxlength="80" value="<?php echo format_to_output( $keyword, 'formvalue')?>" />
			<input type="submit" value="<?php echo T_('Check &amp; ban...') ?>" class="search" />
		</form>
	</div>
<?php
}
?>

<div class="panelblock">
	<h2><?php echo T_('Banned domains blacklist') ?></h2>
	<p class="center"><?php echo T_('Any URL containing one of the following keywords will be banned from posts, comments and logs.');
	if( $current_User->check_perm( 'spamblacklist', 'edit' ) ) 
	{
		echo '<br />'.T_( 'If a keyword restricts legitimate domains, click on the green tick to stop banning with this keyword.');
	}
	?></p>
	<?php list_antiSpam() ?>
	<?php if( $current_User->check_perm( 'spamblacklist', 'edit' ) ) 
	{ ?>
		<p class="center">
			[<a href="b2antispam.php?action=poll"><?php echo T_('Request abuse update from centralized blacklist!') ?></a>]
			[<a href="http://b2evolution.net/about/terms.html"><?php echo T_('Terms of service') ?></a>]
		</p>
	<?php } ?>
	<table class='thin'>
		<?php if( count($res_stats) ) foreach( $res_stats as $row_stats )
		{  ?>
		<tr>
			<td>
				<?php if( $current_User->check_perm( 'spamblacklist', 'edit' ) ) 
				{ ?>
				<a href="b2antispam.php?action=remove&hit_ID=<?php antiSpam_ID() ?>" title="<?php echo T_('Allow keyword back (Remove it from the blacklist)') ?>"><img src="img/tick.gif" width="13" height="13" class="middle" alt="<?php echo T_('Allow Back') ?>" /></a>
				<?php }
				antiSpam_domain( 40 );
				?>
			</td>
			<td><?php antispam_source(); ?></td>
			<td><?php
					if( (antispam_source(false,true) == 'local') 
						&& $current_User->check_perm( 'spamblacklist', 'edit' ) ) 
					{
					?>
					[<a href="b2antispam.php?action=report&keyword=<?php echo urlencode( antiSpam_domain(false) ) ?>" title="<?php echo T_('Report abuse to centralized ban blacklist!') ?>"><?php echo T_('Report') ?></a>]
				<?php } ?>
				[<a href="b2antispam.php?action=ban&keyword=<?php echo urlencode( antiSpam_domain(false) ) ?>" title="<?php echo T_('Check hit-logs and comments for this keyword!') ?>"><?php echo T_('Re-check') ?></a>]
			</td>
		</tr>
		<?php } // End stat loop ?>
	</table>
</div>
<?php
require( dirname(__FILE__).'/_footer.php' ); 
?>
