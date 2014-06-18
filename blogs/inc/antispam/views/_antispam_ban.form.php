<?php
/**
 * This file implements the UI controller for the antispam management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
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
 * @version $Id: _antispam_ban.form.php 6225 2014-03-16 10:01:05Z attila $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $Settings, $current_User;
global $keyword;

global $row_stats;	// for hit functions

$Form = new Form( NULL, 'antispam_ban', 'post', 'compact' );

$redirect_to = param( 'redirect_to', 'url', NULL );
if( $redirect_to == NULL )
{
	$redirect_to = regenerate_url( 'action' );
}

$Form->global_icon( T_('Cancel!'), 'close', $redirect_to, '', 3, 2, array( 'class'=>'action_icon', 'id'=>'close_button' ) );

$Form->begin_form( 'fform',  T_('Confirm ban & delete') );

	$Form->add_crumb( 'antispam' );
	$Form->hidden_ctrl();
	$Form->hiddens_by_key( get_memorized() );
	$Form->hidden( 'confirm', 'confirm' );

	// Check for junk:

	// Check for potentially affected logged hits:
	$sql = 'SELECT SQL_NO_CACHE hit_ID, UNIX_TIMESTAMP(hit_datetime) as hit_datetime, hit_uri, hit_referer, dom_name,
									hit_coll_ID, hit_remote_addr, blog_shortname
					 FROM T_hitlog INNER JOIN T_basedomains ON hit_referer_dom_ID = dom_ID
					 LEFT JOIN T_blogs ON hit_coll_ID = blog_ID
					WHERE hit_referer LIKE '.$DB->quote('%'.$keyword.'%').'
					ORDER BY dom_name ASC
					LIMIT 500';
	$res_affected_hits = $DB->get_results( $sql, ARRAY_A );
	if( $DB->num_rows == 0 )
	{ // No matching hits.
		printf( '<p>'.T_('No <strong>log-hits</strong> match the keyword [%s].').'</p>', evo_htmlspecialchars($keyword) );
	}
	else
	{
	?>
		<p>
			<input type="checkbox" name="delhits" id="delhits_cb" value="1" checked="checked" />
			<label for="delhits_cb">
			<?php printf ( T_('Delete the following %s <strong>referer hits</strong>:'), $DB->num_rows == 500 ? '500+' : $DB->num_rows ) ?>
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
			load_funcs('sessions/model/_hitlog.funcs.php');
			$count = 0;
			foreach( $res_affected_hits as $row_stats )
			{
				?>
				<tr class="<?php echo ($count%2 == 1) ? 'odd' : 'even' ?>">
					<td class="firstcol"><?php stats_time() ?></td>
					<td><a href="<?php stats_referer() ?>"><?php stats_basedomain() ?></a></td>
					<td class="center"><?php stats_hit_remote_addr() ?></td>
					<td><?php echo format_to_output( $row_stats['blog_shortname'], 'htmlbody' ); ?></td>
					<td><?php disp_url( $row_stats['hit_uri'], 50 ); ?></td>
				</tr>
				<?php
				$count++;
			} ?>
			</tbody>
		</table>
	<?php
	}

	// Check for potentially affected comments:
	$sql = 'SELECT *
			  FROM T_comments
			 WHERE comment_author LIKE '.$DB->quote('%'.$keyword.'%').'
			    OR comment_author_email LIKE '.$DB->quote('%'.evo_strtolower( $keyword ).'%').'
			    OR comment_author_url LIKE '.$DB->quote('%'.$keyword.'%').'
			    OR comment_content LIKE '.$DB->quote('%'.$keyword.'%').'
			 ORDER BY comment_date ASC
			 LIMIT 500';
	$res_affected_comments = $DB->get_results( $sql, OBJECT, 'Find matching comments' );
	if( $DB->num_rows == 0 )
	{ // No matching hits.
		printf( '<p>'.T_('No <strong>comments</strong> match the keyword [%s].').'</p>', evo_htmlspecialchars($keyword) );
	}
	else
	{ // create comment arrays
		$comments_by_status = array( 'published' => array(), 'community' => array(), 'protected' => array(), 'private' => array(), 'draft' => array(), 'review' => array(), 'deprecated' => array() );
		$no_perms_count = array( 'published' => 0, 'community' => 0, 'protected' => 0, 'private' => 0, 'draft' => 0, 'review' => 0, 'deprecated' => 0 );
		foreach( $res_affected_comments as $row_stats )
		{ // select comments
			$affected_Comment = new Comment($row_stats);
			$comment_status = $affected_Comment->get( 'status' );
			if( $comment_status == 'trash' )
			{ // This comment was already deleted
				continue;
			}
			if( !$current_User->check_perm( 'comment!CURSTATUS', 'edit', false, $affected_Comment ) )
			{ // no permission to delete
				$no_perms_count[$comment_status] = $no_perms_count[$comment_status] + 1;
				continue;
			}
			// Add comment to the corresponding list
			$comments_by_status[$comment_status][] = $affected_Comment;
		}

		// show comments
		foreach( $comments_by_status as $status => $comments )
		{
			echo_affected_comments( $comments, $status, $keyword, $no_perms_count[$status] );
		}
	}

	// Check for potentially affected comments:
	$quoted_keyword = $DB->quote('%'.$keyword.'%');
	$sql = 'SELECT DISTINCT T_users.*
				FROM T_users 
					LEFT JOIN T_users__fields ON user_ID = uf_user_ID
					LEFT JOIN T_users__usersettings user_domain_setting ON user_ID = user_domain_setting.uset_user_ID AND user_domain_setting.uset_name = "user_domain"
			 WHERE user_url LIKE '.$quoted_keyword.'
				 OR user_email LIKE '.$quoted_keyword.'
				 OR user_domain_setting.uset_value LIKE '.$quoted_keyword.'
				 OR user_nickname LIKE '.$quoted_keyword.'
				 OR user_firstname LIKE '.$quoted_keyword.'
				 OR user_lastname LIKE '.$quoted_keyword.'
				 OR user_login LIKE '.$quoted_keyword.'
				 OR uf_varchar LIKE '.$quoted_keyword.'
			 ORDER BY user_login ASC
			 LIMIT 500';
	$res_affected_users = $DB->get_results( $sql, OBJECT, 'Find matching users' );
	if( $DB->num_rows != 0 )
	{
		if( ! $current_User->check_perm( 'users', 'view', false ) )
		{ // current user has no permission to view users
			printf( '<p>'.T_('There are %d matching <strong>users</strong> but you have no permission to see them.').'</p>', $DB->num_rows );
		}
		else
		{ // matching found, and current user has permission to view -> display users table
			?>
			<p><label><strong><?php echo( T_('Affected users').':' )?></strong></label></p>
			<table class="grouped" cellspacing="0">
				<thead><tr>
				<th class="firstcol"><?php printf( T_('Login') )?></th>
				<th><?php echo( T_('First name') )?></th>
				<th><?php echo( T_('Last name') )?></th>
				<th><?php echo( T_('Nickname') )?></th>
				<th><?php echo( T_('URL') )?></th>
				</tr></thead>
			 	<?php
			 	$count = 0;
				$current_user_edit_perm = $current_User->check_perm( 'users', 'edit', false );
				foreach( $res_affected_users as $row_stats )
				{ // Display affected users
					$affected_User = new User($row_stats);
					?>
					<tr class="<?php echo ($count%2 == 1) ? 'odd' : 'even' ?>">
					<td class="firstcol">
						<?php
						if( $current_user_edit_perm )
						{
							echo '<a href="?ctrl=user&amp;user_tab=profile&amp;user_ID='
								.$affected_User->ID.'"><strong>'.$affected_User->login.'</strong></a>';
						}
						else
						{
							echo '<strong>'.$affected_User->login.'</strong>';
						}
						?>
					</td>
					<td><?php echo $affected_User->first_name() ?></td>
					<td><?php echo $affected_User->last_name() ?></td>
					<td><?php echo $affected_User->nick_name() ?></td>
					<td><?php echo '<strong>'.$affected_User->get('url').'</strong>' ?></td>
					</tr>
					<?php
					$count++;
				}
			 	?>
			</table>
			<?php
		}
	}
	else
	{ // There is no affected users
		printf( '<p>'.T_('No <strong>users</strong> match the keyword [%s]').'</p>', $keyword );
	}

	// Check if the string is already in the blacklist:
	if( antispam_check($keyword) )
	{ // Already there:
		printf( '<p>'.T_('The keyword [%s] is <strong>already handled</strong> by the blacklist.').'</p>', evo_htmlspecialchars($keyword) );
	}
	else
	{ // Not in blacklist
		?>
		<p>
		<input type="checkbox" name="blacklist_locally" id="blacklist_locally_cb" value="1" checked="checked" />
		<label for="blacklist_locally_cb">
			<?php printf ( T_('<strong>Blacklist</strong> the keyword [%s] locally.'), evo_htmlspecialchars($keyword) ) ?>
		</label>
		</p>

		<?php
		if( $Settings->get('antispam_report_to_central') )
		{
			?>
			<p>
			<input type="checkbox" name="report" id="report_cb" value="1" checked="checked" />
			<label for="report_cb">
				<?php printf ( T_('<strong>Report</strong> the keyword [%s] as abuse to b2evolution.net.'), evo_htmlspecialchars($keyword) ) ?>
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


$Form = new Form( NULL, 'antispam_add', 'post', 'compact' );
$Form->begin_form( 'fform', T_('Add a banned keyword') );
	$Form->add_crumb('antispam');
	$Form->hidden_ctrl();
	$Form->hidden( 'action', 'ban' );
	$Form->text( 'keyword', $keyword, 50, T_('Keyword/phrase to ban'), '', 80 ); // TODO: add note
	/*
	 * TODO: explicitly add a domain?
	 * $add_Form->text( 'domain', $domain, 30, T_('Add a banned domain'), 'note..', 80 ); // TODO: add note
	 */
$Form->end_form( array( array( 'submit', 'submit', T_('Check & ban...'), 'SaveButton' ) ) );

?>
