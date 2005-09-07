<?php
/**
 * This file implements the UI controller for the antispam management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Vegar BERG GULDAL - {@link http://funky-m.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * Vegar BERG GULDAL grants François PLANQUE the right to license
 * Vegar BERG GULDAL's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: François PLANQUE.
 * @author vegarg: Vegar BERG GULDAL.
 *
 * @todo Allow applying / re-checking of the known data, not just after an update!
 *
 * @version $Id$
 */

/**
 * Includes:
 */
require_once (dirname(__FILE__).'/_header.php');
require_once (dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_antispam.funcs.php');

$AdminUI->setPath( 'antispam' );

param( 'action', 'string' );
param( 'confirm', 'string' );
param( 'keyword', 'string' );
param( 'domain', 'string' );

// Check permission:
$current_User->check_perm( 'spamblacklist', 'view', true );

switch( $action )
{
	case 'ban': // only an action if further "actions" given
		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true ); // TODO: This should become different for 'edit'/'add' perm level - check for 'add' here.

		$keyword = substr( $keyword, 0, 80 );
		param( 'delhits', 'integer', 0 );
		param( 'delcomments', 'integer', 0 );
		param( 'blacklist_locally', 'integer', 0 );
		param( 'report', 'integer', 0 );

		// Check if the string is too short,
		// it has to be a minimum of 5 characters to avoid being too generic
		if( strlen($keyword) < 5 )
		{
			$Messages->add( sprintf( T_('The keyword &laquo;%s&raquo; is too short, it has to be a minimum of 5 characters!'), htmlspecialchars($keyword) ), 'error' );
			break;
		}

		if( $delhits )
		{ // Delete all banned hit-log entries
			$r = $DB->query('DELETE FROM T_hitlog
												WHERE hit_referer LIKE '.$DB->quote('%'.$keyword.'%') );

			$Messages->add( sprintf( T_('Deleted %d log hits matching &laquo;%s&raquo;.'), $r, htmlspecialchars($keyword) ), 'note' );
		}

		if( $delcomments )
		{ // Then all banned comments
			$r = $DB->query('DELETE FROM T_comments
												WHERE comment_author LIKE '.$DB->quote('%'.$keyword.'%').'
													 OR comment_author_url LIKE '.$DB->quote('%'.$keyword.'%').'
					      				   OR comment_content LIKE '.$DB->quote('%'.$keyword.'%') );
			$Messages->add( sprintf( T_('Deleted %d comments matching &laquo;%s&laquo;.'), $r, htmlspecialchars($keyword) ), 'note' );
		}

		if( $blacklist_locally )
		{ // Local blacklist:
			if( antispam_create( $keyword ) )
			{
				$Messages->add( sprintf( T_('The keyword &laquo;%s&raquo; has been blacklisted locally.'), htmlspecialchars($keyword) ), 'note' );
			}
			else
			{ // TODO: message?
			}
		}

		if( $report && $report_abuse )
		{ // Report this keyword as abuse:
			antispam_report_abuse( $keyword );
		}

		// We'll ask the user later what to do, if no "sub-action" given.
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
		antispam_report_abuse( $keyword );
		break;


	case 'poll':
		// request abuse list from central blacklist:

		// Check permission:
		$current_User->check_perm( 'spamblacklist', 'edit', true );

		ob_start();
		antispam_poll_abuse();
		$Debuglog->add( ob_get_contents(), 'antispam_poll' );
		ob_end_clean();
		break;
}


/**
 * Top menu
 */
require(dirname(__FILE__).'/_menutop.php');


$AdminUI->dispPayloadBegin();


if( !$Messages->count('error') && $action == 'ban' && !( $delhits || $delcomments || $blacklist_locally || $report ) )
{{{ // Nothing to do, ask user:
	?>

	<div class="panelblock">
		<form action="antispam.php" method="post">
		<input type="hidden" name="confirm" value="confirm" />
		<input type="hidden" name="keyword" value="<?php echo format_to_output( $keyword, 'formvalue' ) ?>" />
		<input type="hidden" name="action" value="ban" />
		<h2><?php echo T_('Confirm ban &amp; delete') ?></h2>

		<?php
		// Check for junk:
		// Check for potentially affected log hits:
		$sql = 'SELECT hit_ID, UNIX_TIMESTAMP(hit_datetime), hit_uri, hit_referer, dom_name
										hit_blog_ID, hit_remote_addr
						 FROM T_hitlog, T_basedomains
						WHERE hit_referer_dom_ID = dom_ID
							AND hit_referer LIKE '.$DB->quote('%'.$keyword.'%').'
						ORDER BY dom_name ASC';
		$res_affected_hits = $DB->get_results( $sql, ARRAY_A );
		if( $DB->num_rows == 0 )
		{ // No matching hits.
			printf( '<p><strong>'.T_('No log-hits match the keyword [%s].').'</strong></p>', htmlspecialchars($keyword) );
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
				{
					?>
					<tr <?php if($count%2 == 1) echo 'class="odd"' ?>>
						<td class="firstcol"><?php stats_time() ?></td>
						<td><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
						<td><?php stats_hit_remote_addr() ?></td>
						<td><?php stats_blog_name() ?></td>
						<td><a href="<?php stats_req_URI() ?>"><?php stats_req_URI() ?></a></td>
					</tr>
					<?php
					$count++;
				} ?>
				</tbody>
			</table>
		<?php
		}

		// Check for potentially affected comments:
		$sql = 'SELECT comment_ID, comment_date, comment_author, comment_author_url,
										comment_author_IP, comment_content
						FROM T_comments
						WHERE comment_author LIKE '.$DB->quote('%'.$keyword.'%').'
							 OR comment_author_url LIKE '.$DB->quote('%'.$keyword.'%').'
    				   OR comment_content LIKE '.$DB->quote('%'.$keyword.'%').'
						ORDER BY comment_date ASC';
		$res_affected_comments = $DB->get_results( $sql, ARRAY_A );
		if( $DB->num_rows == 0 )
		{ // No matching hits.
			printf( '<p><strong>'.T_('No comments match the keyword [%s].').'</strong></p>', htmlspecialchars($keyword) );
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
				{ // TODO: new Comment( $row_stats )
					?>
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
				} ?>
				</tbody>
			</table>
		<?php
		}

		// Check if the string is already in the blacklist:
		if( antispam_check($keyword) )
		{ // Already there:
			printf( '<p><strong>'.T_('The keyword [%s] is already handled by the blacklist.').'</strong></p>', htmlspecialchars($keyword) );
		}
		else
		{ // Not in blacklist
			?>
			<p><strong><input type="checkbox" name="blacklist_locally" value="1" checked="checked" />
			<?php printf ( T_('Blacklist the keyword [%s] locally.'), htmlspecialchars($keyword) ) ?>
			</strong></p>

			<?php
			if( $report_abuse )
			{
				?>
				<p>
				<strong><input type="checkbox" name="report" value="1" checked="checked" />
				<?php printf ( T_('Report the keyword [%s] as abuse to b2evolution.net.'), htmlspecialchars($keyword) ) ?>
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
}}}


// ADD KEYWORD FORM:
if( $current_User->check_perm( 'spamblacklist', 'edit' ) ) // TODO: check for 'add' here once it's mature.
{ // add keyword or domain
	echo '<div class="panelblock">';
	$Form = & new Form( 'antispam.php', 'antispam_add', 'get', '' );
	$Form->begin_form('fform');
	$Form->hidden( 'action', 'ban' );
	$Form->text( 'keyword', $keyword, 30, T_('Add a banned keyword'), '', 80 ); // TODO: add note
	/*
	 * TODO: explicitly add a domain?
	 * $add_Form->text( 'domain', $domain, 30, T_('Add a banned domain'), 'note..', 80 ); // TODO: add note
	 */
	$Form->end_form( array( array( 'submit', 'submit', T_('Check & ban...'), 'SaveButton' ) ) );
	echo '</div>';
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
	$Results = & new Results( 'SELECT aspm_ID, aspm_string, aspm_source
														FROM T_antispam
														ORDER BY aspm_string ASC' );

	$Results->cols[] = array(
							'th' => T_('Keyword'),
							'order' => 'aspm_string',
							'td' => '%htmlspecialchars(#aspm_string#)%',
						);

	// Set columns:
	function antispam_source2( & $row )
	{
		static $aspm_sources = NULL;

		if( $aspm_sources === NULL )
		{
			/**
			 * @var the antispam sources
			 * @static
			 */
			$aspm_sources = array (
				'local' => T_('Local'),
				'reported' => T_('Reported'),
				'central' => T_('Central'),
			);
		}

		return $aspm_sources[$row->aspm_source];
	}
	$Results->cols[] = array(
							'th' => T_('Source'),
							'order' => 'aspm_source',
							'td' => '%antispam_source2({row})%',
						);

	// Check if we need to display more:
	if( $current_User->check_perm( 'spamblacklist', 'edit' ) )
	{ // User can edit, spamlist: add controls to output columns:

		// Add this before results:
		?>

		<p class="center">
			[<a href="antispam.php?action=poll"><?php echo T_('Request abuse update from centralized blacklist!') ?></a>]
			[<a href="http://b2evolution.net/about/terms.html"><?php echo T_('Terms of service') ?></a>]
		</p>

		<?php

		// Add CHECK to 1st column:
		$Results->cols[0]['td'] = '<a href="antispam.php?action=remove&amp;hit_ID=$aspm_ID$" title="'.
												TS_('Allow keyword back (Remove it from the blacklist)').
												'"><img src="img/tick.gif" width="13" height="13" class="middle" alt="'.
												TS_('Allow Back').
												'" /></a> '.
												$Results->cols[0]['td'];

		// Add a column for actions:
		function antispam_actions( & $row )
		{
			$output = '';

			if( $row->aspm_source == 'local' )
			{
				$output .= '[<a href="antispam.php?action=report&amp;keyword='.
										urlencode( $row->aspm_string ).'" title="'.
										T_('Report abuse to centralized ban blacklist!').'">'.
										T_('Report').'</a>]';
			}

			return $output.'[<a href="antispam.php?action=ban&amp;keyword='.
										urlencode( $row->aspm_string ).'" title="'.
										T_('Check hit-logs and comments for this keyword!').'">'.
										T_('Re-check').'</a>]';
		}
		$Results->cols[] = array(
								'th' => T_('Actions'),
								'td' => '%antispam_actions({row})%',
							);
	}

	// Display results:
	$Results->display();
	?>
</div>

<?php

$AdminUI->dispPayloadEnd();

require( dirname(__FILE__).'/_footer.php' );
?>