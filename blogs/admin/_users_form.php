<?php
/**
 * Displays user properties form
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */

$allowed_to_edit = ($current_User->check_perm( 'users', 'edit' )
										|| ($user_profile_only && $edited_User->ID == $current_User->ID))
?>
<div class="panelblock">
	<?php if( $current_User->check_perm( 'users', 'view' ) )
	{?>
	<div style="float:right">
		<?php
		if( $user > 0 )
		{	// Links to next/previous user
			
			$prevuserid = 0;
			$nextuserid = 0;
			
			$query = "SELECT MAX(ID), MIN(ID) FROM $tableusers";
			$uminmax = $DB->get_row( $query, ARRAY_A );
			
			foreach( $userlist as $fuser )
			{ // find prev/next id
				if( $fuser['ID'] < $user )
				{
					if( $fuser['ID'] > $prevuserid )
					{
						$prevuserid = $fuser['ID'];
						$prevuserlogin = $fuser['user_login'];
					}
				}
				elseif( $fuser['ID'] > $user )
				{
					if( $fuser['ID'] < $nextuserid || $nextuserid == 0 )
					{
						$nextuserid = $fuser['ID'];
						$nextuserlogin = $fuser['user_login'];
					}
				}
			}
			
			echo ( $user != $uminmax['MIN(ID)'] ) ? '<a title="'.T_('first user').'" href="?user='.$uminmax['MIN(ID)'].'">[&lt;&lt;]</a>' : '[&lt;&lt;]';
			echo ( $prevuserid ) ? '<a title="'.T_('previous user').' ('.$prevuserlogin.')" href="?user='.$prevuserid.'">[&lt;]</a>' : '[&lt;]';
			echo ( $nextuserid ) ? '<a title="'.T_('next user').' ('.$nextuserlogin.')" href="?user='.$nextuserid.'">[&gt;]</a>' : '[&gt;]';
			echo ( $user != $uminmax['MAX(ID)'] ) ? '<a title="'.T_('last user').'" href="?user='.$uminmax['MAX(ID)'].'">[&gt;&gt;]</a>' : '[&gt;&gt;]';
		}
		?>
		<a title="<?php echo T_('Close user profile'); ?>" href="b2users.php">[ X ]</a>
	</div>
	<?php } ?>
		
	<h2><?php
	if( $edited_User->get('ID') == 0 )
	{
		echo T_('Create new user profile');
	}
	else
	{
		echo T_('Profile for:').' '.$edited_User->dget('firstname').' '.$edited_User->dget('lastname')
					.' ['.( isset($edited_user_oldlogin)? $edited_user_oldlogin : $edited_User->dget('login') ).']';
	}	
	?></h2>
	
	<table align="center">
	<tr><td>
	<fieldset>

	<form class="fform" method="post" action="b2users.php<?php if( $user != 0 ) echo '?user='.$user?>">
		<input type="hidden" name="action" value="userupdate" />
		<input type="hidden" name="edited_user_ID" value="<?php $edited_User->disp('ID','formvalue') ?>" />
		<input type="hidden" name="edited_user_oldlogin" value="<?php
		echo ( isset($edited_user_oldlogin) ? format_to_output($edited_user_oldlogin, 'formvalue')
																				: $edited_User->dget('login', 'formvalue') )
		?>" />
		
		<table cellpadding="5" cellspacing="0">
			<?php
			if( $allowed_to_edit )
			{
				form_text_tr( 'edited_user_login', $edited_User->dget('login', 'formvalue'), 50, T_('Login'), '', 20 );
				form_text_tr( 'edited_user_firstname', $edited_User->dget('firstname', 'formvalue'), 50, T_('First name'), '', 50 );
				form_text_tr( 'edited_user_lastname', $edited_User->dget('lastname', 'formvalue'), 50, T_('Last name'), '', 50 );
				form_text_tr( 'edited_user_nickname', $edited_User->dget('nickname', 'formvalue'), 50, T_('Nickname'), '', 50 );
			?>
			<tr>
			<td align="right"><strong><?php echo T_('Identity shown') ?>:</strong></td>
			<td>
				<?php $idmode = $edited_User->get( 'idmode' ); ?>
				<select name="edited_user_idmode">
					<option value="nickname"<?php if ( $idmode == 'nickname' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('nickname') != '' ) $edited_User->disp('nickname', 'htmlhead' ); else echo '['.T_('Nickname').']'; ?></option>
					<option value="login"<?php if ( $idmode == 'login' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('login') != '' ) $edited_User->disp('login', 'htmlhead' ); else echo '['.T_('Login').']'; ?></option>
					<option value="firstname"<?php if ( $idmode == 'firstname' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('firstname') != '' ) $edited_User->disp('firstname', 'htmlhead' ); else echo '['.T_('First name').']'; ?></option>
					<option value="lastname"<?php if ( $idmode == 'lastname' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('lastname') != '' ) $edited_User->disp('lastname', 'htmlhead' ); else echo '['.T_('Last name').']'; ?></option>
					<option value="namefl"<?php if ( $idmode == 'namefl' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('firstname') != '' ) $edited_User->disp('firstname', 'htmlhead' ); else echo '['.T_('First name').']'; echo ' '; if( $edited_User->get('lastname') != '' ) $edited_User->disp('lastname', 'htmlhead' ); else echo '['.T_('Last name').']'; ?></option>
					<option value="namelf"<?php if ( $idmode == 'namelf' ) echo ' selected="selected"'; ?>><?php if( $edited_User->get('lastname') != '' ) $edited_User->disp('lastname', 'htmlhead' ); else echo '['.T_('Last name').']'; echo ' '; if( $edited_User->get('firstname') != '' ) $edited_User->disp('firstname', 'htmlhead' ); else echo '['.T_('First name').']'; ?></option>
				</select>
			</td>
			</tr>
			<tr>
			<td align="right"><strong><?php echo T_('Locale') ?>:</strong></td>
			<td>
				<select name="edited_user_locale"><?php
					locale_options( $edited_User->get('locale') );?>
				</select>
			</td>
			</tr>
			<?php
			}
			else
			{ // display only
				form_info_tr( T_('Login'), $edited_User->dget('login') );
				form_info_tr( T_('First name'), $edited_User->dget('firstname') );
				form_info_tr( T_('Last name'), $edited_User->dget('lastname') );
				form_info_tr( T_('Nickname'), $edited_User->dget('nickname') );
				form_info_tr( T_('Identity shown'), $edited_User->dget('preferedname') );
				form_info_tr( T_('Locale'), $edited_User->dget('locale') );
			}
			$fieldnote = '<a href="mailto:'.$edited_User->get('email').'"><img src="img/play.png" border="0" height="14" width="14" alt="&gt;" title="'.T_('Write Email').'" /></a>';
			if( $allowed_to_edit )
				form_text_tr( 'edited_user_email', $edited_User->dget('email', 'formvalue'), 50, T_('Email'), $fieldnote, 100 );
			else
				form_info_tr( T_('Email'), $edited_User->dget('email'), $fieldnote );
			
			if( ($url = $edited_User->get('url')) != '' )
			{
				if( !preg_match('#://#', $url) )
				{
					$url = 'http://'.$url;
				}
				$fieldnote = '<a href="'.$url.'" target="_blank"><img src="img/play.png" border="0" height="14" width="14" alt="&gt;" title="'.T_('Visit homepage').'" /></a>';
			}
			else $fieldnote = '';
			if( $allowed_to_edit )
				form_text_tr( 'edited_user_url', $edited_User->dget('url', 'formvalue'), 50, T_('URL'), $fieldnote, 100 );
			else
				form_info_tr( T_('URL'), $edited_User->dget('url'), $fieldnote );
			
			if( $edited_User->get('icq') != 0 )
			{
				$fieldnote = '<a href="http://wwp.icq.com/scripts/search.dll?to='.$edited_User->get('icq').'" target="_blank"><img src="img/play.png" border="0" height="14" width="14" alt="&gt;" title="'.T_('Search on ICQ.com').'" /></a>';
			}
			else $fieldnote = '';
			if( $allowed_to_edit )
				form_text_tr( 'edited_user_icq', $edited_User->dget('icq', 'formvalue'), 50, T_('ICQ'), $fieldnote, 10 );
			else
				form_info_tr( T_('ICQ'), $edited_User->dget('icq', 'formvalue'), $fieldnote );
			
			if( $edited_User->get('aim') != '' )
			{
				$fieldnote = '<a href="aim:goim?screenname='.$edited_User->get('aim').'&amp;message=Hello"><img src="img/play.png" border="0" height="14" width="14" alt="&gt;" title="'.T_('Instant Message to user').'" /></a>';
			}
			else $fieldnote = '';
			if( $allowed_to_edit )
			{
				form_text_tr( 'edited_user_aim', $edited_User->dget('aim', 'formvalue'), 50, T_('AIM'), $fieldnote, 50 );
				form_text_tr( 'edited_user_msn', $edited_User->dget('msn', 'formvalue'), 50, T_('MSN IM'), '', 100 );
				form_text_tr( 'edited_user_yim', $edited_User->dget('yim', 'formvalue'), 50, T_('YahooIM'), '', 50 );
			?>
			<tr>
				<td align="right"><label for="edited_user_notify"><strong><?php echo T_('Notifications') ?>:</strong></td>
				<td><input type="checkbox" name="edited_user_notify" id="edited_user_notify" value="1"<?php if($edited_User->get('notify')) echo ' checked="checked"'?> />
				<!--<span class="notes"><?php echo T_('Check this to receive notification whenever one of your posts receives comments, trackbacks, etc.')?></span>-->
				</td>
			</tr>
			<tr>
				<td align="right"><label for="edited_user_pass1"><strong><?php echo T_('New password') ?>:</strong></td>
				<td><input type="password" name="edited_user_pass1" id="edited_user_pass1" value="" size="50" /></td>
			</tr>
			<tr>
				<td align="right"><label for="edited_user_pass2"><strong><?php echo T_('Confirm new password') ?>:</strong></td>
				<td><input type="password" name="edited_user_pass2" id="edited_user_pass2" value="" size="50" /></td>
			</tr>
			<?php
			}
			else
			{
				form_info_tr( T_('AIM'), $edited_User->dget('aim'), $fieldnote );
				form_info_tr( T_('MSN IM'), $edited_User->dget('msn') );
				form_info_tr( T_('YahooIM'), $edited_User->dget('yim') );
				form_info_tr( T_('Notifications'), ($edited_User->dget('notify')) ? T_('yes') : T_('no') );
			}?>
		</table>
	</fieldset>
	</td>
	<td style="vertical-align:top">
	<fieldset>
		<legend><?php echo T_('User information') ?></legend>
		<table cellpadding="5" cellspacing="0">
			<?php
			form_info_tr( T_('ID'), $edited_User->dget('ID') );
			form_info_tr( T_('Posts'), ( $action != 'newtemplate' ) ? get_usernumposts($edited_User->get('ID')) : '-' );
			form_info_tr( T_('Created on'), $edited_User->dget('datecreated') );
			form_info_tr( T_('From IP'), $edited_User->dget('ip') );
			form_info_tr( T_('From Domain'), $edited_User->dget('domain') );
			form_info_tr( T_('With Browser'), $edited_User->dget('browser') );?>
		</table>
	</fieldset>

	<fieldset>
		<legend><?php echo T_('User rights') ?></legend>
		<?php
		if( $user_profile_only )
		{
			form_info( T_('Level'), $edited_User->dget('level') );
		}
		else
		{
			form_text( 'edited_user_level', $edited_User->dget('level', 'formvalue'), 2, T_('Level'), '[0 - 10]', 2 );
		}
		if( $edited_User->get('ID') != 1 && !$user_profile_only )
		{
			$chosengroup = ( $edited_User->Group === NULL ) ? $Settings->get('newusers_grp_ID') : $edited_User->Group->get('ID');
			form_select_object( 'edited_user_grp_ID', $chosengroup, $GroupCache, T_('User group') );
		}
		else
		{
			form_info( T_('User group'), $edited_User->Group->dget('name') );
		}
		?>
	</fieldset>
	</td>
	</tr>
	<?php
	if( $current_User->check_perm( 'users', 'edit' )
			|| ($user_profile_only && $edited_User->ID == $current_User->ID) )
	{ ?>
		<tr><td colspan="2" class="center">
		<input type="submit" name="submit" value="<?php if( $edited_User->ID == 0 ) echo T_('Create'); else echo T_('Update') ?>" class="search" />
		<input type="reset" value="<?php echo T_('Reset') ?>" class="search" />
		</td></tr>
	<?php } ?>
	

	</table>
	
	<div class="clear"></div>

</form>

</div>
