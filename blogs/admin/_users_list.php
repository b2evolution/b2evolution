<?php
/**
 * This file implements the UI view for the user/group list for user/group editing.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

// get the userlist
$request = "SELECT T_users.*, grp_ID, grp_name
						FROM T_users RIGHT JOIN T_groups ON user_grp_ID = grp_ID
						ORDER BY grp_name, user_login";
$userlist = $DB->get_results( $request );
?>
<div class="panelblock">
<h2><?php echo T_('Groups &amp; Users') ?></h2>
<table class="grouped" cellspacing="0">
	<tr>
		<?php
			echo '<th class="firstcol">'.T_('ID')."</th>\n";
			/* TRANS: table header for user list: */
			echo '<th>'.T_('Login ')."</th>\n";
			echo '<th>'.T_('Nickname')."</th>\n";
			echo '<th>'.T_('Name')."</th>\n";
			echo '<th>'.T_('Email')."</th>\n";
			echo '<th>'.T_('URL')."</th>\n";
			echo '<th';
			if( ! $current_User->check_perm( 'users', 'edit', false ) )
			{ // This will be last col:
				echo ' class="lastcol"';
			}
			if( $current_User->check_perm( 'users', 'edit', false ) )
			{ // extra table cell for +/-
				echo ' colspan="2"';
			}
			echo '>'.T_('Level')."</th>\n";

			if( $current_User->check_perm( 'users', 'edit', false ) )
			{
				echo '<th class="lastcol">'.T_('Edit').'</th>';
			}
		?>
	</tr>
	<?php

	$loop_prev_grp_ID = 0;

	if( count($userlist) )
	{
		// query which groups have users
		$query = 'SELECT grp_ID FROM T_groups, T_users
							WHERE user_grp_ID = grp_ID
							GROUP BY grp_ID';
		$usedgroups = $DB->get_col($query);

		$count = 0;
		foreach( $userlist as $row )
		{ // For each line (can be a user/group or just an empty group)
			$loop_grp_ID = $row->grp_ID;

			if( $loop_prev_grp_ID != $loop_grp_ID )
			{ // ---------- We just entered a new group! ----------
				?>
				<tr class="group">
					<td colspan="7" class="firstcol">
						<strong><a href="b2users.php?group=<?php echo $loop_grp_ID ?>"><img src="img/properties.png" width="18" height="13" class="middle" alt="<?php echo T_('Properties') ?>" /> <?php echo format_to_output( $row->grp_name, 'htmlbody' ); ?></a></strong>
						<?php
							if( $loop_grp_ID == $Settings->get('newusers_grp_ID') )
							{
								echo '<span class="notes">('.T_('default group for new users').')</span>';
							}
						?>
					</td>
					<?php
					if( $current_User->check_perm( 'users', 'edit', false ) )
					{ // copy
						?>
						<td>&nbsp;</td>
						<td class="lastcol">
						<?php
						echo action_icon( T_('Edit this group...'), 'edit', regenerate_url( 'action', 'group='.$loop_grp_ID ) );

						echo action_icon( T_('Duplicate this group...'), 'copy', regenerate_url( 'action', 'action=newgroup&amp;template='.$loop_grp_ID ) );

						if( ($loop_grp_ID != 1) && ($loop_grp_ID != $Settings->get('newusers_grp_ID'))
								&& !in_array( $loop_grp_ID, $usedgroups ) )
						{ // delete
							echo action_icon( T_('Delete this group!'), 'delete', regenerate_url( 'action', 'action=deletegroup&amp;id='.$loop_grp_ID ) );
						}
						echo '</td>';
					}
					?>
				</tr>
				<?php
				$loop_prev_grp_ID = $loop_grp_ID;
			}

			if( !empty( $row->ID ) )
			{ // We have a user here: (i-e group was not empty)
				$loop_User = & new User( $row );
				if( $count%2 == 1 )
					echo "<tr class=\"odd\">\n";
				else
					echo "<tr>\n";

				echo '<td class="firstcol">', $loop_User->get('ID'), "</td>\n";

				echo '<td><a href="b2users.php?user=', $loop_User->get('ID'), '">';
				echo '<img src="img/properties.png" width="18" height="13" class="middle" alt="', T_('Properties'), '" /> ';
				echo $loop_User->get('login'), "</a></td>\n";

				echo '<td>';
				$loop_User->disp('nickname');
				echo '</td>';

				echo '<td>', $loop_User->get('firstname').'&nbsp;'.$loop_User->get('lastname')."</td>\n";
				// Email:
				echo '<td>&nbsp;';
				$email = $loop_User->get('email');
				if( !empty($email) )
				{
					echo '<a href="mailto:'.$email.'" title="e-mail: '.$email.'"><img src="img/email.gif"  alt="e-mail: '.$email.'" class="middle" /></a>&nbsp;';
				}
				echo "</td>\n";

				// URL:
				echo '<td>&nbsp;';
				$url = $loop_User->get('url');
				if (($url != 'http://') and ($url != ''))
				{
					if( !preg_match('#://#', $url) )
					{
						$url = 'http://'.$url;
					}
					echo "<a href=\"$url\" title=\"website: $url\"><img src=\"img/url.gif\" alt=\"website: $url\" class=\"middle\" /></a>&nbsp;";
				}
				echo "</td>\n";

				// User level:
				echo '<td>'.$loop_User->get('level').'</td>';

				if( $current_User->check_perm( 'users', 'edit', false ) )
				{ // We have permission to edit the user:

					// Promotion buttons:
					echo '<td align="right">';
					if( ($loop_User->get('level') > 0) )
					{ // prom=down
						echo action_icon( T_('Decrease user level'), 'arrow_down',
										regenerate_url( 'action', 'action=promote&amp;prom=down&amp;id='.$loop_User->get('ID') ) );
					}
					if( ($loop_User->get('level') < 10 ) )
					{ // prom=up
						echo action_icon( T_('Increase user level'), 'arrow_up',
										regenerate_url( 'action', 'action=promote&amp;prom=up&amp;id='.$loop_User->get('ID') ) );
					}
					echo '</td>';

					// Edit actions:
					echo '<td class="lastcol">';
					// edit user:
					echo action_icon( T_('Edit this user...'), 'edit', regenerate_url( 'action', 'user='.$loop_User->get('ID') ) );
					// copy user:
					echo action_icon( T_('Duplicate this user...'), 'copy', regenerate_url( 'action', 'action=newuser&amp;template='.$loop_User->get('ID') ) );
					if( ($loop_User->ID != 1) && ($loop_User->ID != $current_User->ID) )
					{ // delete user:
						echo action_icon( T_('Delete this user!'), 'delete', regenerate_url( 'action', 'action=deleteuser&amp;id='.$loop_User->get('ID') ) );
					}
					echo '</td>';
				}
				echo "\n</tr>\n";
				$count++;
			}
		}

		echo "\n</table>";
	}

if( $current_User->check_perm( 'users', 'edit', false ) )
{ // create new user link
	?>
	<p class="center">
		<a href="?action=newuser"><img src="img/new.gif" width="13" height="13" class="middle" alt="" /> <?php echo T_('New user...') ?></a>
		&middot;
		<a href="?action=newgroup"><img src="img/new.gif" width="13" height="13" class="middle" alt="" /> <?php echo T_('New group...') ?></a>
	</p>
	<?php
}
?>
</div>
