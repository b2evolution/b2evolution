<?php 
	/*
	 * This is the template that displays the user profile form
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 * To display a feedback, you should call a stub AND pass the right parameters
	 * For example: /blogs/index.php?disp=profile
	 * Note: don't code this URL by hand, use the template functions to generate it!
	 */
	if(substr(basename($_SERVER['SCRIPT_FILENAME']),0,1)=='_')
		die("Please, do not access this page directly.");

	// --- //
?>

	<!-- form to add a comment -->
	<form action="<?php echo $baseurl, '/', $pathhtsrv ?>/profile_update.php" method="post" class="bComment">

		<input type="hidden" name="checkuser_id" value="<?php echo $user_ID ?>" />
	
		<fieldset>
			<div class="label"><?php echo T_('Login:') ?></div>
			<div class="input"><?php user_info( 'login', 'htmlhead' ) ?>
				-
				<strong><?php echo T_('ID') ?>:</strong> 
				<?php user_info( 'ID', 'raw' ) ?>
			</div>
		</fieldset>

		<fieldset>
			<div class="label"><?php echo T_('Level') ?>:</div>
			<div class="input"><?php echo user_info( 'level', 'raw' ) ?></div>
		</fieldset>

		<fieldset>
			<div class="label"><?php echo T_('Posts') ?>:</div>
			<div class="input"><?php echo user_info( 'num_posts', 'raw' ) ?></div>
		</fieldset>

		<fieldset>
			<div class="label"><label for="newuser_firstname"><?php echo T_('First name') ?>:</label></div>
			<div class="input"><input type="text" name="newuser_firstname" id="newuser_firstname" value="<?php user_info( 'firstname', 'formvalue' ) ?>" size="40" tabindex="1" class="bComment" /></div>
		</fieldset>

		<fieldset>
			<div class="label"><label for="newuser_lastname"><?php echo T_('Last name') ?>:</label></div>
			<div class="input"><input type="text" name="newuser_lastname" id="newuser_lastname" value="<?php user_info( 'lastname', 'formvalue' ) ?>" size="40" tabindex="1" class="bComment" /></div>
		</fieldset>

		<fieldset>
			<div class="label"><label for="newuser_nickname"><?php echo T_('Nickname') ?>:</label></div>
			<div class="input"><input type="text" name="newuser_nickname" id="newuser_nickname" value="<?php user_info( 'nickname', 'formvalue' ) ?>" size="40" tabindex="1" class="bComment" /></div>
		</fieldset>

		<fieldset>
			<div class="label"><label for="newuser_idmode"><?php echo T_('Identity shown') ?>:</label></div>
			<div class="input">
				<?php $idmode = get_user_info( 'idmode' ); ?>
				<select name="newuser_idmode" class="bComment">
					<option value="nickname"<?php if ( $idmode == 'nickname' ) echo ' selected="selected"'; ?>><?php user_info( 'nickname', 'htmlhead' ) ?></option>
					<option value="login"<?php if ( $idmode == 'login' ) echo ' selected="selected"'; ?>><?php user_info( 'login', 'htmlhead' ) ?></option>
					<option value="firstname"<?php if ( $idmode == 'firstname' ) echo ' selected="selected"'; ?>><?php user_info( 'firstname', 'htmlhead' ) ?></option>
					<option value="lastname"<?php if ( $idmode == 'lastname' ) echo ' selected="selected"'; ?>><?php user_info( 'lastname', 'htmlhead' ) ?></option>
					<option value="namefl"<?php if ( $idmode == 'namefl' ) echo ' selected="selected"'; ?>><?php user_info( 'firstname', 'htmlhead' ); echo ' '; user_info( 'lastname', 'htmlhead' ); ?></option>
					<option value="namelf"<?php if ( $idmode == 'namelf' ) echo ' selected="selected"'; ?>><?php user_info( 'lastname', 'htmlhead' ); echo ' '; user_info( 'firstname', 'htmlhead' ); ?></option>
				</select>
		</div>
		</fieldset>


		<fieldset>
			<div class="label"><label for="user_email"><?php echo T_('Email') ?>:</label></div>
			<div class="input"><input type="text" name="user_email" id="user_email" value="<?php user_info( 'email', 'formvalue' ) ?>" size="40" tabindex="1" class="bComment" /></div>
		</fieldset>

		<fieldset>
			<div class="label"><label for="newuser_url"><?php echo T_('URL') ?>:</label></div>
			<div class="input"><input type="text" name="newuser_url" id="newuser_url" value="<?php user_info( 'url', 'formvalue' ) ?>" size="40" tabindex="1" class="bComment" /></div>
		</fieldset>

		<fieldset>
			<div class="label"><label for="newuser_icq"><?php echo T_('ICQ') ?>:</label></div>
			<div class="input"><input type="text" name="newuser_icq" id="newuser_icq" value="<?php user_info( 'icq', 'formvalue' ) ?>" size="40" tabindex="1" class="bComment" /></div>
		</fieldset>

		<fieldset>
			<div class="label"><label for="newuser_aim"><?php echo T_('AIM') ?>:</label></div>
			<div class="input"><input type="text" name="newuser_aim" id="newuser_aim" value="<?php user_info( 'aim', 'formvalue' ) ?>" size="40" tabindex="1" class="bComment" /></div>
		</fieldset>

		<fieldset>
			<div class="label"><label for="newuser_msn"><?php echo T_('MSN I.M.') ?>:</label></div>
			<div class="input"><input type="text" name="newuser_msn" id="newuser_msn" value="<?php user_info( 'msn', 'formvalue' ) ?>" size="40" tabindex="1" class="bComment" /></div>
		</fieldset>

		<fieldset>
			<div class="label"><label for="newuser_yim"><?php echo T_('Yahoo I.M.') ?>:</label></div>
			<div class="input"><input type="text" name="newuser_yim" id="newuser_yim" value="<?php user_info( 'yim', 'formvalue' ) ?>" size="40" tabindex="1" class="bComment" /></div>
		</fieldset>


		<fieldset>
			<div class="label"><label for="pass1"><?php echo T_('New password') ?>:</label></div>
			<div class="input"><input type="password" name="pass1" id="pass1" value="" size="16" tabindex="1" class="bComment" /></div>
		</fieldset>
	
		<fieldset>
			<div class="label"><label for="pass2"><?php echo T_('Confirm new password') ?>:</label></div>
			<div class="input"><input type="password" name="pass2" id="pass2" value="" size="16" tabindex="1" class="bComment" /></div>
		</fieldset>		
	
		<fieldset>
			<div class="input">
				<input type="submit" name="submit" class="buttonarea" value="<?php echo T_('Update') ?>" tabindex="8" />
			</div>
		</fieldset>
	
		<div class="clear"></div>
	
	</form>

