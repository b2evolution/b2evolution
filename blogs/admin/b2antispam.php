<?php
/**
 * This file implements the UI controller for the antispam blacklist management.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */

/**
 * Includes:
 */
require_once (dirname(__FILE__).'/_header.php');
require_once (dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_functions_antispam.php');

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
			$sql = "DELETE FROM T_hitlog
							WHERE referingURL LIKE '%$dbkeyword%'";
			$DB->query($sql);
			echo '</div>';
		}

		if( $delcomments && $deluxe_ban )
		{ // Then all banned comments
			echo '<div class="panelinfo">';
			printf( '<h3>'.T_('Deleting comments matching [%s]...').'</h3>', $keyword );
			$sql = "DELETE FROM T_comments
							WHERE comment_author_url LIKE '%$dbkeyword%'
      				   OR comment_content LIKE '%$dbkeyword%'";
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
												 baseDomain, hit_blog_ID, visitURL, hit_remote_addr
												 FROM T_hitlog
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
						<table class="grouped" cellspacing="0">
							<thead>
							<tr>
								<th><?php echo T_('Date') ?></th>
								<th><?php echo T_('Referer') ?></th>
								<th><?php echo T_('Ref. IP') ?></th>
								<th><?php echo T_('Target Blog') ?></th>
								<th><?php echo T_('Target URL') ?></th>
							</tr>
							</thead>
							<tbody>
							<?php
							$count = 0;
							foreach( $res_affected_hits as $row_stats )
							{  ?>
           		<tr <?php if($count%2 == 1) echo 'class="odd"' ?>>
								<td class="firstcol"><?php stats_time() ?></td>
								<td><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
								<td><?php stats_hit_remote_addr() ?></td>
								<td><?php stats_blog_name() ?></td>
								<td><a href="<?php stats_req_URI() ?>"><?php stats_req_URI() ?></a></td>
							</tr>
							<?php
              $count++;
              } // End stat loop ?>
              </tbody>
						</table>
					<?php
					}

					// Check for potentially affected comments:
					$sql = "SELECT comment_ID, comment_date, comment_author, comment_author_url,
													comment_author_IP, comment_content
									FROM T_comments
									WHERE comment_author_url LIKE '%$dbkeyword%'
									   OR comment_content LIKE '%$dbkeyword%'
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
						<?php printf ( T_('Delete the following %d comments:'), count($res_affected_comments) ) ?>
						</strong></p>
						<table class="grouped" cellspacing="0">
							<thead>
							<tr>
								<th><?php echo T_('Date') ?></th>
								<th><?php echo T_('Author') ?></th>
								<th><?php echo T_('Auth. URL') ?></th>
								<th><?php echo T_('Auth. IP') ?></th>
								<th><?php echo T_('Content starts with...') ?></th>
							</tr>
							</thead>
							<tbody>
							<?php
							$count = 0;
              foreach( $res_affected_comments as $row_stats )
							{ // TODO: new Comment( $row_stats ) ?>
           		<tr <?php if($count%2 == 1) echo 'class="odd"' ?>>
								<td class="firstcol"><?php echo mysql2date(locale_datefmt().' '.locale_timefmt(), $row_stats['comment_date'] ); ?></td>
								<td><?php echo $row_stats['comment_author'] ?></a></td>
								<td><?php echo $row_stats['comment_author_url'] ?></td>
								<td><?php echo $row_stats['comment_author_IP'] ?></td>
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
							<?php
              $count++;
              } // End stat loop ?>
							</tbody>
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
		<form action="b2antispam.php" method="get" class="fform">
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

	<?php
	// Create result set:
	$Results = new Results(	'SELECT aspm_ID, aspm_string, aspm_source
														 FROM T_antispam
														ORDER BY aspm_string ASC' );

	// Set headers:
	$Results->col_headers = array(
															T_('Keyword'),
															T_('Source')
														);

	// Set columns:
  function antispam_source2( & $row )
	{
		global $aspm_sources;
		$asp_source = $row['aspm_source'];
		return T_($aspm_sources[$asp_source] );
	}
	$Results->cols = array(
													'$aspm_string$',
													'%antispam_source2($row)%'
												);

	// Check if we need to display nore:
	if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
	{	// User can edit, spamlist: add controls to output columns:

		// Add this before results:
	  ?>
			<p class="center">
				[<a href="b2antispam.php?action=poll"><?php echo T_('Request abuse update from centralized blacklist!') ?></a>]
				[<a href="http://b2evolution.net/about/terms.html"><?php echo T_('Terms of service') ?></a>]
			</p>
		<?php

		// Add CHECK to 1st column:
		$Results->cols[0] = '<a href="b2antispam.php?action=remove&amp;hit_ID=$aspm_ID$" title="'.
												T_('Allow keyword back (Remove it from the blacklist)').
												'"><img src="img/tick.gif" width="13" height="13" class="middle" alt="'.
												T_('Allow Back').'" /></a> '.
												$Results->cols[0];

		// Add a column for actions:
		$Results->col_headers[2] = T_('Actions');
	  function antispam_actions( & $row )
		{
			$output = '';

		 	if( $row['aspm_source'] == 'local' )
			{
				$output .= '[<a href="b2antispam.php?action=report&amp;keyword='.
										urlencode( $row['aspm_string'] ).'" title="'.
										T_('Report abuse to centralized ban blacklist!').'">'.
										T_('Report').'</a>]';
			}

			return $output.'[<a href="b2antispam.php?action=ban&amp;keyword='.
										urlencode( $row['aspm_string'] ).'" title="'.
										T_('Check hit-logs and comments for this keyword!').'">'.
										T_('Re-check').'</a>]';
		}
		$Results->cols[2] = '%antispam_actions($row)%';
	}

	// Display results:
	$Results->display();
	?>
</div>
<?php
require( dirname(__FILE__).'/_footer.php' );
?>