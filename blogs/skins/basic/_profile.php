<?php
	/**
	 * This is the template that displays the user profile form
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 * To display a feedback, you should call a stub AND pass the right parameters
	 * For example: /blogs/index.php?disp=profile
	 * Note: don't code this URL by hand, use the template functions to generate it!
	 *
	 * b2evolution - {@link http://b2evolution.net/}
	 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
	 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
	 *
	 * @package evoskins
	 * @subpackage basic
	 */
	if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

	if( !is_logged_in() )
	{ // must be logged in!
		echo '<p>', T_( 'You are not logged in.' ), '</p>';
		return;
	}
	// --- //
?>

	<!-- form to add a comment -->
	<form action="<?php echo $htsrv_url ?>profile_update.php" method="post">

	<input type="hidden" name="checkuser_id" value="<?php echo $current_User->ID ?>" />

	<table align="center">

		<tr>
			<td align="right"><strong><?php echo T_('Login:') ?></strong></td>
			<td><?php user_info( 'login', 'htmlhead' ) ?>
				-
				<strong><?php echo T_('ID') ?>:</strong>
				<?php user_info( 'ID', 'raw' ) ?>
			</td>
		</tr>

		<tr>
			<td align="right"><strong><?php echo T_('Level') ?>:</strong></td>
			<td><?php echo user_info( 'level', 'raw' ) ?></td>
		</tr>

		<tr>
			<td align="right"><strong><?php echo T_('Posts') ?>:</strong></td>
			<td><?php echo user_info( 'num_posts', 'raw' ) ?></td>
		</tr>

		<?php
			form_text_tr( 'newuser_firstname', get_user_info( 'firstname' ), 40, T_('First name'), '', 50, 'bComment' );
			form_text_tr( 'newuser_lastname', get_user_info( 'lastname' ), 40, T_('Last name'), '', 50, 'bComment' );
			form_text_tr( 'newuser_nickname', get_user_info( 'nickname' ), 40, T_('Nickname'), '', 50, 'bComment' );
		?>

		<tr>
			<td align="right"><strong><label for="newuser_idmode"><?php echo T_('Identity shown') ?>:</label></strong></td>
			<td>
				<?php $idmode = get_user_info( 'idmode' ); ?>
				<select name="newuser_idmode">
					<option value="nickname"<?php if ( $idmode == 'nickname' ) echo ' selected="selected"'; ?>><?php user_info( 'nickname', 'htmlhead' ) ?></option>
					<option value="login"<?php if ( $idmode == 'login' ) echo ' selected="selected"'; ?>><?php user_info( 'login', 'htmlhead' ) ?></option>
					<option value="firstname"<?php if ( $idmode == 'firstname' ) echo ' selected="selected"'; ?>><?php user_info( 'firstname', 'htmlhead' ) ?></option>
					<option value="lastname"<?php if ( $idmode == 'lastname' ) echo ' selected="selected"'; ?>><?php user_info( 'lastname', 'htmlhead' ) ?></option>
					<option value="namefl"<?php if ( $idmode == 'namefl' ) echo ' selected="selected"'; ?>><?php user_info( 'firstname', 'htmlhead' ); echo ' '; user_info( 'lastname', 'htmlhead' ); ?></option>
					<option value="namelf"<?php if ( $idmode == 'namelf' ) echo ' selected="selected"'; ?>><?php user_info( 'lastname', 'htmlhead' ); echo ' '; user_info( 'firstname', 'htmlhead' ); ?></option>
				</select>
		</td>
		</tr>


		<?php
			form_text_tr( 'newuser_email', get_user_info( 'email' ), 40, T_('Email'), '', 100, 'bComment' );
			form_text_tr( 'newuser_url', get_user_info( 'url' ), 40, T_('URL'), '', 100, 'bComment' );
			form_text_tr( 'newuser_icq', get_user_info( 'icq' ), 40, T_('ICQ'), '', 40, 'bComment' );
			form_text_tr( 'newuser_aim', get_user_info( 'aim' ), 40, T_('AOL I.M.'), '', 50, 'bComment' );
			form_text_tr( 'newuser_msn', get_user_info( 'msn' ), 40, T_('MSN I.M.'), '', 100, 'bComment' );
			form_text_tr( 'newuser_yim', get_user_info( 'yim' ), 40, T_('Yahoo I.M.'), '', 50, 'bComment' );

			form_checkbox_tr( 'newuser_notify', get_user_info( 'notify' ), T_('Notifications'), T_('Check this to receive notification whenever one of your posts receives comments, trackbacks, etc.') );
		?>

		<tr>
			<td align="right"><strong><label for="pass1"><?php echo T_('New password') ?>:</label></strong></td>
			<td><input type="password" name="pass1" id="pass1" value="" size="16" /></td>
		</tr>

		<tr>
			<td align="right"><strong><label for="pass2"><?php echo T_('Confirm new password') ?>:</label></strong></td>
			<td><input type="password" name="pass2" id="pass2" value="" size="16" /></td>
		</tr>

		<tr>
			<td align="center" colspan="2">
				<input type="submit" name="submit" value="<?php echo T_('Update') ?>" />
			</td>
		</tr>
	</table>

	</form>
