<?php
/**
 * This file implements the UI controller for the antispam management.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
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

global $Settings, $current_User;
global $keyword;

global $row_stats;	// for hit functions

$Form = new Form( NULL, 'antispam_ban', 'post', 'compact' );

$redirect_to = param( 'redirect_to', 'string', NULL );
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
									hit_blog_ID, hit_remote_addr, blog_shortname
					 FROM T_hitlog INNER JOIN T_basedomains ON hit_referer_dom_ID = dom_ID
						 		LEFT JOIN T_blogs ON hit_blog_ID = blog_ID
					WHERE hit_referer LIKE '.$DB->quote('%'.$keyword.'%').'
					ORDER BY dom_name ASC
					LIMIT 500';
	$res_affected_hits = $DB->get_results( $sql, ARRAY_A );
	if( $DB->num_rows == 0 )
	{ // No matching hits.
		printf( '<p>'.T_('No %s match the keyword [%s].').'</p>', '<strong>'.T_('log-hits').'</strong>', htmlspecialchars($keyword) );
	}
	else
	{
	?>
		<p>
			<input type="checkbox" name="delhits" id="delhits_cb" value="1" checked="checked" />
			<label for="delhits_cb">
			<?php printf ( T_('Delete the following %s %s:'), $DB->num_rows == 500 ? '500+' : $DB->num_rows , '<strong>'.T_('referer hits').'</strong>' ) ?>
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
				 OR comment_author_email LIKE '.$DB->quote('%'.$keyword.'%').'
			 	 OR comment_author_url LIKE '.$DB->quote('%'.$keyword.'%').'
    		   	 OR comment_content LIKE '.$DB->quote('%'.$keyword.'%').'
			 ORDER BY comment_date ASC
			 LIMIT 500';
	$res_affected_comments = $DB->get_results( $sql, OBJECT, 'Find matching comments' );
	if( $DB->num_rows == 0 )
	{ // No matching hits.
		printf( '<p>'.T_('No %s match the keyword [%s].').'</p>', '<strong>'.T_('comments').'</strong>', htmlspecialchars($keyword) );
	}
	else
	{ // create comment arrays
		$draft_comments = array();
		$published_comments = array();
		$deprecated_comments = array();
		$draft_noperms_count = 0;
		$publ_noperms_count = 0;
		$depr_noperms_count = 0;
		foreach( $res_affected_comments as $row_stats )
		{ // select comments
			$affected_Comment = new Comment($row_stats);
			$affected_Item = & $affected_Comment->get_Item();
			$comment_blog = $affected_Item->get_blog_ID();
			switch( $affected_Comment->get( 'status' ) )
			{
				case 'draft':
					if( ! $current_User->check_perm( 'blog_draft_comments', 'edit', false, $comment_blog ) )
					{ // no permission to delete
						$draft_noperms_count++;
						continue;
					}
					$draft_comments[] = $affected_Comment;
					break;
				case 'published':
					if( ! $current_User->check_perm( 'blog_published_comments', 'edit', false, $comment_blog ) )
					{ // no permission to delete
						$publ_noperms_count++;
						continue;
					}
					$published_comments[] = $affected_Comment;
					break;
				case 'deprecated':
					if( ! $current_User->check_perm( 'blog_deprecated_comments', 'edit', false, $comment_blog ) )
					{ // no permission to delete
						$depr_noperms_count++;
						continue;
					}
					$deprecated_comments[] = $affected_Comment;
					break;
				default:
					debug_die( 'Invalid comment status' );
			}
		}
		// show comments
		echo_affected_comments( $draft_comments, 'draft', $keyword, $draft_noperms_count );
		echo_affected_comments( $published_comments, 'published', $keyword, $publ_noperms_count );
		echo_affected_comments( $deprecated_comments, 'deprecated', $keyword, $depr_noperms_count );
	}

	// Check for potentially affected comments:
	$quoted_keyword = $DB->quote('%'.$keyword.'%');
	$sql = 'SELECT T_users.* 
				FROM T_users LEFT JOIN T_users__fields ON user_ID = uf_user_ID
			 WHERE user_url LIKE '.$quoted_keyword.'
				 OR user_email LIKE '.$quoted_keyword.'
				 OR user_domain LIKE '.$quoted_keyword.'
				 OR user_nickname LIKE '.$quoted_keyword.'
				 OR user_firstname LIKE '.$quoted_keyword.'
				 OR user_lastname LIKE '.$quoted_keyword.'
				 OR user_login LIKE '.$quoted_keyword.'
				 OR user_aim LIKE '.$quoted_keyword.'
				 OR user_msn LIKE '.$quoted_keyword.'
				 OR user_yim LIKE '.$quoted_keyword.'
				 OR uf_varchar LIKE '.$quoted_keyword.'
			 ORDER BY user_login ASC
			 LIMIT 500';
	$res_affected_users = $DB->get_results( $sql, OBJECT, 'Find matching users' );
	if( $DB->num_rows != 0 )
	{
		if( ! $current_User->check_perm( 'users', 'view', false ) )
		{ // current user has no permission to view users
			printf( '<p>'.T_('There are %d matching %s but you have no permission to see them.').'</p>', $DB->num_rows, '<strong>'.T_('users').'</strong>' );
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
							echo '<a href="?ctrl=user&amp;user_tab=identity&amp;user_ID='
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
		printf( '<p>'.T_('No %s match the keyword [%s]').'</p>', '<strong>'.T_('users').'</strong>', $keyword );
	}

	// Check if the string is already in the blacklist:
	if( antispam_check($keyword) )
	{ // Already there:
		printf( '<p>'.T_('The keyword [%s] is %s by the blacklist.').'</p>', htmlspecialchars($keyword), '<strong>'.T_('already handled').'</strong>' );
	}
	else
	{ // Not in blacklist
		?>
		<p>
		<input type="checkbox" name="blacklist_locally" id="blacklist_locally_cb" value="1" checked="checked" />
		<label for="blacklist_locally_cb">
			<?php printf ( T_('%s the keyword [%s] locally.'), '<strong>'.T_('Blacklist').'</strong>', htmlspecialchars($keyword) ) ?>
		</label>
		</p>

		<?php
		if( $Settings->get('antispam_report_to_central') )
		{
			?>
			<p>
			<input type="checkbox" name="report" id="report_cb" value="1" checked="checked" />
			<label for="report_cb">
				<?php printf ( T_('%s the keyword [%s] as abuse to b2evolution.net.'), '<strong>'.T_('Report').'</strong>', htmlspecialchars($keyword) ) ?>
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

/*
 * $Log$
 * Revision 1.25  2010/07/26 06:52:15  efy-asimo
 * MFB v-4-0
 *
 * Revision 1.24  2010/06/24 08:54:05  efy-asimo
 * PHP 4 compatibility
 *
 * Revision 1.23  2010/06/23 09:30:55  efy-asimo
 * Comments display and Antispam ban form modifications
 *
 * Revision 1.22  2010/06/17 08:54:52  efy-asimo
 * antispam screen, antispam tool dispplay fix
 *
 * Revision 1.21  2010/06/02 07:29:57  efy-asimo
 * Antispam tool - add affected users table
 *
 * Revision 1.20  2010/06/01 11:33:19  efy-asimo
 * Split blog_comments advanced permission (published, deprecated, draft)
 * Use this new permissions (Antispam tool,when edit/delete comments)
 *
 * Revision 1.19  2010/05/14 08:16:04  efy-asimo
 * antispam tool ban form - create seperate table for different comments
 *
 * Revision 1.18  2010/05/10 14:26:17  efy-asimo
 * Paged Comments & filtering & add comments listview
 *
 * Revision 1.17  2010/02/26 08:34:33  efy-asimo
 * dashboard -> ban icon should be javascripted task
 *
 * Revision 1.16  2010/02/08 17:52:06  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.15  2010/01/30 18:55:20  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.14  2010/01/03 17:56:05  fplanque
 * crumbs & stuff
 *
 * Revision 1.13  2010/01/03 13:45:38  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.12  2009/12/06 01:52:55  blueyed
 * Add 'htmlspecialchars' type to format_to_output, same as formvalue, but less irritating. Useful for strmaxlen, which is being used in more places now.
 *
 * Revision 1.11  2009/09/13 21:26:50  blueyed
 * SQL_NO_CACHE for SELECT queries using T_hitlog
 *
 * Revision 1.10  2009/07/08 02:38:55  sam2kb
 * Replaced strlen & substr with their mbstring wrappers evo_strlen & evo_substr when needed
 *
 * Revision 1.9  2009/03/08 23:57:41  fplanque
 * 2009
 *
 * Revision 1.8  2009/03/05 22:39:00  blueyed
 * Add load_funcs, which was required at some point (can't remember, seen during comparison)
 *
 * Revision 1.7  2008/04/15 21:53:30  fplanque
 * minor
 *
 * Revision 1.6  2008/04/04 17:02:21  fplanque
 * cleanup of global settings
 *
 * Revision 1.5  2008/03/17 09:08:28  afwas
 * minor
 *
 * Revision 1.4  2008/01/21 09:35:25  fplanque
 * (c) 2008
 *
 * Revision 1.3  2007/12/29 18:55:32  fplanque
 * better antispam banning screen
 *
 * Revision 1.2  2007/11/22 14:16:43  fplanque
 * antispam / banning cleanup
 *
 * Revision 1.1  2007/09/04 14:56:19  fplanque
 * antispam cleanup
 *
 */
?>
