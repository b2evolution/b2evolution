<?php
/**
 * Displays user properties form
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
	<div style="float:right"><a title="<?php echo T_('Close user profile'); ?>" href="b2users.php">[ X ]</a></div>
	<h2><?php echo T_('Profile for:'), ' ', $edited_User->disp('nickname') ?></h2>

	<form class="fform" method="post" action="b2users.php">
		<input type="hidden" name="action" value="userupdate" />
		<input type="hidden" name="edited_user_ID" value="<?php $edited_User->disp('ID','formvalue') ?>" />
		<table>
		<tr>
		<td>
			<table cellpadding="5" cellspacing="0">
			<tr>
			<td align="right"><strong><?php echo T_('Login:') ?></strong></td>
			<td><?php $edited_User->disp('login'); ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('First name') ?>:</strong></td>
			<td><?php $edited_User->disp('firstname') ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('Last name') ?>:</strong></td>
			<td><?php $edited_User->disp('lastname') ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('Nickname') ?>:</strong></td>
			<td><?php $edited_User->disp('nickname') ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('Email') ?>:</strong></td>
			<td><?php echo make_clickable($edited_User->disp('email')) ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('URL') ?>:</strong></td>
			<td><?php $edited_User->disp('url') ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('ICQ') ?>:</strong></td>
			<td><?php if ($edited_User->disp('icq') > 0) { echo make_clickable('icq:'. $edited_User->disp('icq')); } ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('AIM') ?>:</strong></td>
			<td><?php echo make_clickable('aim:'. $edited_User->disp('aim')) ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('MSN IM') ?>:</strong></td>
			<td><?php $edited_User->disp('msn') ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('YahooIM') ?>:</strong></td>
			<td><?php $edited_User->disp('yim') ?></td>
			</tr>
			</table>

		</td>
		<td valign="top">
			<table cellpadding="5" cellspacing="0">
			<tr>
			<td align="right"><strong><?php echo T_('ID') ?>:</strong></td>
			<td><?php $edited_User->disp('ID')  ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('Posts') ?>:</strong></td>
			<td><?php $posts=get_usernumposts($user); ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('Identity') ?>:</strong></td>
			<td><?php $edited_User->disp('preferedname'); ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('Created on:') ?></strong></td>
			<td><?php $edited_User->disp('datecreated'); ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('From IP:') ?></strong></td>
			<td><?php $edited_User->disp('ip'); ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('From Domain:') ?></strong></td>
			<td><?php $edited_User->disp('domain'); ?></td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('With Browser:') ?></strong></td>
			<td><?php $edited_User->disp('browser'); ?></td>
			</tr>
			</table>
		</td>
		</table>

		<fieldset>
			<legend><?php echo T_('User rights') ?></legend>
			<?php
			
				form_info(  T_('Level'), $edited_User->get('level') );
				
				if(  $edited_User->get('ID') != 1 )
				{
					form_select_object( 'edited_user_grp_ID', $edited_User->Group->get('ID'), $GroupCache, T_('User group') );
				}
				else
				{
					form_info(  T_('User group'), $edited_User->Group->get('name') );
				}
			?>

		</fieldset>

		<?php
		if( $current_User->check_perm( 'users', 'edit' ) )
		{ ?>
		<fieldset>
			<fieldset>
				<div class="input">
					<input type="submit" name="submit" value="<?php echo T_('Update') ?>" class="search">
					<input type="reset" value="<?php echo T_('Reset') ?>" class="search">
				</div>
			</fieldset>
		</fieldset>
		<?php } ?>

		<div class="clear"></div>
	</form>

</div>
