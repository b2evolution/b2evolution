<?php
/**
 * This file implements the UI controller for the antispam management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 * @todo Allow applying / re-checking of the known data, not just after an update!
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $keyword, $report_abuse;

global $row_stats;	// for hit functions

$Form = & new Form( NULL, 'antispam_ban', 'post', 'compact' );

$Form->global_icon( T_('Cancel!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform',  T_('Confirm ban & delete') );

	$Form->hidden_ctrl();
	$Form->hiddens_by_key( get_memorized() );
	$Form->hidden( 'confirm', 'confirm' );

	// Check for junk:

	// Check for potentially affected logged hits:
	$sql = 'SELECT hit_ID, UNIX_TIMESTAMP(hit_datetime) as hit_datetime, hit_uri, hit_referer, dom_name,
									hit_blog_ID, hit_remote_addr, blog_shortname
					 FROM T_hitlog INNER JOIN T_basedomains ON hit_referer_dom_ID = dom_ID
						 		LEFT JOIN T_blogs ON hit_blog_ID = blog_ID
					WHERE hit_referer LIKE '.$DB->quote('%'.$keyword.'%').'
					ORDER BY dom_name ASC
					LIMIT 500';
	$res_affected_hits = $DB->get_results( $sql, ARRAY_A );
	if( $DB->num_rows == 0 )
	{ // No matching hits.
		printf( '<p><strong>'.T_('No log-hits match the keyword [%s].').'</strong></p>', htmlspecialchars($keyword) );
	}
	else
	{
	?>
		<p>
			<input type="checkbox" name="delhits" id="delhits_cb" value="1" checked="checked" />
			<label for="delhits_cb">
			<strong><?php printf ( T_('Delete the following %s referer hits:'), $DB->num_rows == 500 ? '500+' : $DB->num_rows ) ?></strong>
			</label>
		</p>
		<table class="grouped" cellspacing="0">
			<thead>
			<tr>
				<th class="firstcol"><?php echo T_('Date') ?></th>
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
				<tr class="<?php echo ($count%2 == 1) ? 'odd' : 'even' ?>">
					<td class="firstcol"><?php stats_time() ?></td>
					<td><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
					<td><?php stats_hit_remote_addr() ?></td>
					<td><?php echo format_to_output( $row_stats['blog_shortname'], 'htmlbody' ); ?></td>
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
								 OR comment_author_email LIKE '.$DB->quote('%'.$keyword.'%').'
							 	 OR comment_author_url LIKE '.$DB->quote('%'.$keyword.'%').'
    				   	 OR comment_content LIKE '.$DB->quote('%'.$keyword.'%').'
					 ORDER BY comment_date ASC
					 LIMIT 500';
	$res_affected_comments = $DB->get_results( $sql, ARRAY_A, 'Find matching comments' );
	if( $DB->num_rows == 0 )
	{ // No matching hits.
		printf( '<p><strong>'.T_('No comments match the keyword [%s].').'</strong></p>', htmlspecialchars($keyword) );
	}
	else
	{
	?>
		<p>
			<input type="checkbox" name="delcomments" id="delcomments_cb" value="1" checked="checked" />
			<label for="delcomments_cb">
			<strong><?php printf ( T_('Delete the following %s comments:'), $DB->num_rows == 500 ? '500+' : $DB->num_rows ) ?></strong>
			</label>
		</p>
		<table class="grouped" cellspacing="0">
			<thead>
			<tr>
				<th class="firstcol"><?php echo T_('Date') ?></th>
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
				<tr class="<?php echo ($count%2 == 1) ? 'odd' : 'even' ?>">
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
		<p>
		<input type="checkbox" name="blacklist_locally" id="blacklist_locally_cb" value="1" checked="checked" />
		<label for="blacklist_locally_cb">
			<strong><?php printf ( T_('Blacklist the keyword [%s] locally.'), htmlspecialchars($keyword) ) ?></strong>
		</label>
		</p>

		<?php
		if( $report_abuse )
		{
			?>
			<p>
			<input type="checkbox" name="report" id="report_cb" value="1" checked="checked" />
			<label for="report_cb">
				<strong><?php printf ( T_('Report the keyword [%s] as abuse to b2evolution.net.'), htmlspecialchars($keyword) ) ?></strong>
			</label>
			[<a href="http://b2evolution.net/about/terms.html"><?php echo T_('Terms of service') ?></a>]
			</p>
			<?php
		}
	}

	$Form->buttons( array(
		array( '', 'actionArray[ban]', T_('Perform selected operations'), 'DeleteButton' ),
	) );

$Form->end_form();


$Form = & new Form( NULL, 'antispam_add', 'post', 'compact' );
$Form->begin_form( 'fform', T_('Add a banned keyword') );
	$Form->hidden_ctrl();
	$Form->hidden( 'action', 'ban' );
	$Form->text( 'keyword', $keyword, 50, T_('Keyword/phrase to ban'), '', 80 ); // TODO: add note
	/*
	 * TODO: explicitly add a domain?
	 * $add_Form->text( 'domain', $domain, 30, T_('Add a banned domain'), 'note..', 80 ); // TODO: add note
	 */
$Form->end_form( array( array( 'submit', 'submit', T_('Check & ban...'), 'SaveButton' ) ) );

/*
 * $Log$
 * Revision 1.2  2007/11/22 14:16:43  fplanque
 * antispam / banning cleanup
 *
 * Revision 1.1  2007/09/04 14:56:19  fplanque
 * antispam cleanup
 *
 */
?>