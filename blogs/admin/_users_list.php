<?php 
/**
 * Displays groups/users list for editing
 *
 * b2evolution - {@link http://b2evolution.net/}
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
?>
<div class="panelblock">
	<h2><?php echo T_('Groups &amp; Users') ?></h2>
	<?php
	$request = "SELECT $tableusers.*, grp_ID, grp_name FROM $tableusers RIGHT JOIN $tablegroups ON user_grp_ID = grp_ID ORDER BY grp_name, user_login";
	$querycount++; 
	$result = mysql_query($request);
	?>
<table class="thin">
	<tr>
		<th><?php echo T_('ID') ?></th>
		<th><?php /* TRANS: table header for user list */ echo T_('Login ') ?></th>
		<th><?php echo T_('Nickname') ?></th>
		<th><?php echo T_('Name') ?></th>
		<th><?php echo T_('Email') ?></th>
		<th><?php echo T_('URL') ?></th>
		<th><?php echo T_('Level') ?></th>
	</tr>
	<?php 
	$loop_prev_grp_ID = 0;
	while($row = mysql_fetch_array($result) )
	{	// For each line (can be a user/group or just an empty group)
		$loop_grp_ID = $row['grp_ID'];
		if( $loop_prev_grp_ID != $loop_grp_ID )
		{	// We just entered a new group!
			?>
			<tr class="group">
				<td colspan="7">
					<strong><a href="b2users.php?action=groupedit&amp;grp_ID=<?php echo $loop_grp_ID ?>"><img src="img/properties.png" width="18" height="13" class="middle" alt="<?php echo T_('Properties') ?>" /> <?php echo format_to_output( $row['grp_name'], 'htmlbody' ); ?></a></strong>
				</td>
			</tr>
			<?php
			$loop_prev_grp_ID = $loop_grp_ID;
		}

		if( !empty( $row['ID'] ) )
		{	// We have a user here: (i-e group was not empty)
			$loop_User = & new User( $row );
			echo "<tr>\n";
			$email = $loop_User->get('email');
			$url = $loop_User->get('url');
			echo "<td>", $loop_User->get('ID'), "</td>\n";
			echo '<td><a href="b2users.php?action=useredit&amp;user=', $loop_User->get('ID'), '">';
			echo '<img src="img/properties.png" width="18" height="13" class="middle" alt="', T_('Properties'), '" /> ';
			echo $loop_User->get('login'), "</a></td>\n";
			?>
			<td><?php $loop_User->disp('nickname') ?></td>
			<?php
			echo "<td>", $loop_User->get('firstname')."&nbsp;".$loop_User->get('lastname')."</td>\n";
			echo "<td>&nbsp;<a href=\"mailto:$email\" title=\"e-mail: $email\"><img src=\"img/email.gif\" border=\"0\" alt=\"e-mail: $email\" /></a>&nbsp;</td>";
			echo "<td>&nbsp;";
			if (($url != "http://") and ($url != ""))
				echo "<a href=\"$url\" title=\"website: $url\"><img src=\"img/url.gif\" border=\"0\" alt=\"website: $url\" /></a>&nbsp;";
			echo "</td>\n";
			echo "<td>".$loop_User->get('level');
			if( ($loop_User->get('level') < 10 ) &&$current_User->check_perm( 'users', 'edit' ) )
			{
				echo " <a href=\"b2users.php?action=promote&id=".$loop_User->get('ID')."&prom=up\">+</a> ";
			}
			if( ($loop_User->get('level') > 0) && $current_User->check_perm( 'users', 'edit' ))
			{
				echo " <a href=\"b2users.php?action=promote&id=".$loop_User->get('ID')."&prom=down\">-</a> ";
			}
			if( ($loop_User->get('level') == 0) && $current_User->check_perm( 'users', 'edit' ) )
			{
				?>
				<a href="b2users.php?action=delete&id=<?php echo $loop_User->get('ID') ?>" style="color:red;font-weight:bold;" onClick="return confirm('<?php echo /* TRANS: Warning this is a javascript string */ T_('Are you sure you want to delete this user?\\nWarning: all his posts will be deleted too!') ?>')"><img src="img/xross.gif" width="13" height="13" class="middle" alt="<?php echo /* TRANS: Abbrev. for Delete */ T_('Del') ?>" /></a>
				<?php
			}
			echo "</td>\n";
			echo "</tr>\n";
		}
	}
	?>
</table>
</div>

<?php 
	if( $current_User->check_perm( 'users', 'edit' ) )
	{ ?>
		<div class="panelblock">
			<?php	
			echo '<p><a href="', $htsrv_url, '/register.php?redirect_to=', $admin_url, '/b2users.php"><img src="img/new.png" width="13" height="12" class="middle" alt="" /> ', T_('Register a new user...'), '</a></p>'; ?>
	
			<p><?php echo T_('To delete an user, bring his/her level to zero, then click on the red cross.') ?><br />
			<strong><?php echo T_('Warning') ?>:</strong> <?php echo T_('deleting an user also deletes all posts made by this user.') ?></p>
		</div>
	<?php
	}
?>