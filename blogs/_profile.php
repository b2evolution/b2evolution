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
	
		<div class="label"><?php echo T_('Login:') ?></div>
		<div class="input"><?php echo $user_login; ?></div>
		
		<fieldset>
			<div class="label"><label for="newuser_firstname"><?php echo T_('First name') ?>:</label></div>
			<div class="input"><input type="text" name="newuser_firstname" id="newuser_firstname" value="<?php user_info( 'firstname', 'formvalue' ) ?>" size="40" tabindex="1" class="bComment" /></div>
		</fieldset>

		<fieldset>
			<div class="label"><label for="newuser_lastname"><?php echo T_('Last name') ?>:</label></div>
			<div class="input"><input type="text" name="newuser_lastname" id="newuser_lastname" value="<?php user_info( 'lastname', 'formvalue' ) ?>" size="40" tabindex="1" class="bComment" /></div>
		</fieldset>

<p>under construction!</p>
</form>
<?php return  ?>

		<td align="right"><strong><?php echo T_('Nickname') ?>:</strong></td>
		<td><input type="text" name="newuser_nickname" value="<?php echo $profiledata["user_nickname"] ?>" class="postform" /></td>

		<td align="right"><strong><?php echo T_('Email') ?>:</strong></td>
		<td><input type="text" name="newuser_email" value="<?php echo $profiledata["user_email"] ?>" class="postform" /></td>

		<td align="right"><strong><?php echo T_('URL') ?>:</strong></td>
		<td><input type="text" name="newuser_url" value="<?php echo $profiledata["user_url"] ?>" class="postform" /></td>

		<td align="right"><strong><?php echo T_('ICQ') ?>:</strong></td>
		<td><input type="text" name="newuser_icq" value="<?php if ($profiledata["user_icq"] > 0) { echo $profiledata["user_icq"]; } ?>" class="postform" /></td>

		<td align="right"><strong><?php echo T_('AIM') ?>:</strong></td>
		<td><input type="text" name="newuser_aim" value="<?php echo $profiledata["user_aim"] ?>" class="postform" /></td>

		<td align="right"><strong><?php echo T_('MSN IM') ?>:</strong></td>
		<td><input type="text" name="newuser_msn" value="<?php echo $profiledata["user_msn"] ?>" class="postform" /></td>

		<td align="right"><strong><?php echo T_('YahooIM') ?>:</strong></td>
		<td><input type="text" name="newuser_yim" value="<?php echo $profiledata["user_yim"] ?>" class="postform" /></td>

		<td>
		<strong><?php echo T_('ID') ?>:</strong> <?php echo $profiledata["ID"] ?></td>

		<td>
		<strong><?php echo T_('Level') ?>:</strong> <?php echo $profiledata["user_level"] ?>
		</td>

		<td>
		<strong><?php echo T_('Posts') ?>:</strong>
		<?php
		$posts=get_usernumposts($user_ID);
		echo $posts;
		?>
		</td>

		<td>
		<?php echo T_('<strong>Identity</strong> on the blog') ?>:<br />
		<select name="newuser_idmode" class="postform">
		<option value="nickname"<?php
		if ($profiledata["user_idmode"]=="nickname")
		echo ' selected="selected"'; ?>><?php echo $profiledata["user_nickname"] ?></option>
		<option value="login"<?php
		if ($profiledata["user_idmode"]=="login")
		echo ' selected="selected"'; ?>><?php echo $profiledata["user_login"] ?></option>
		<option value="firstname"<?php
		if ($profiledata["user_idmode"]=="firstname")
		echo ' selected="selected"'; ?>><?php echo $profiledata["user_firstname"] ?></option>
		<option value="lastname"<?php
		if ($profiledata["user_idmode"]=="lastname")
		echo ' selected="selected"'; ?>><?php echo $profiledata["user_lastname"] ?></option>
		<option value="namefl"<?php
		if ($profiledata["user_idmode"]=="namefl")
		echo ' selected="selected"'; ?>><?php echo $profiledata["user_firstname"]." ".$profiledata["user_lastname"] ?></option>
		<option value="namelf"<?php
		if ($profiledata["user_idmode"]=="namelf")
		echo ' selected="selected"'; ?>><?php echo $profiledata["user_lastname"]." ".$profiledata["user_firstname"] ?></option>
		</select>
		</td>

		<td>
		<br />
		<?php echo T_('New <strong>password</strong> (twice):') ?><br />
		<input type="password" name="pass1" size="16" value="" class="postform" /><br />
		<input type="password" name="pass2" size="16" value="" class="postform" />
		</td>

		
		<fieldset>
			<div class="label"><label for="email"><?php echo T_('Email') ?>:</label></div>
			<div class="input"><input type="text" name="email" id="email" value="<?php echo $comment_author_email ?>" size="40" tabindex="2" class="bComment" /><br />
				<span class="notes"><?php echo T_('Your email address will <strong>not</strong> be displayed on this site.') ?></span>
			</div>
		</fieldset>
		
		<fieldset>
			<div class="label"><label for="url"><?php echo T_('Site/Url') ?>:</label></div>
			<div class="input"><input type="text" name="url" id="url" value="<?php echo $comment_author_url ?>" size="40" tabindex="3" class="bComment" /><br />
				<span class="notes"><?php echo T_('Your URL will be displayed.') ?></span>
			</div>
		</fieldset>
				
		<fieldset>
			<div class="label"><label for="comment"><?php echo T_('Comment text') ?>:</label></div>
			<div class="input"><textarea cols="40" rows="12" name="comment" id="comment" tabindex="4" class="bComment"></textarea><br />
				<span class="notes"><?php echo T_('Allowed XHTML tags'), ': ', htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags)) ?><br />
				<?php echo T_('URLs, email, AIM and ICQs will be converted automatically.') ?></span>
			</div>
		</fieldset>
				
		<fieldset>
			<div class="label"><?php echo T_('Options') ?>:</div>
			<div class="input">
			
			<?php if(substr($comments_use_autobr,0,4) == 'opt-') { ?>
			<input type="checkbox" name="comment_autobr" value="1" <?php if($comments_use_autobr == 'opt-out') echo ' checked="checked"' ?> tabindex="6" id="comment_autobr" /> <label for="comment_autobr"><?php echo T_('Auto-BR') ?></label> <span class="notes">(<?php echo T_('Line breaks become &lt;br /&gt;') ?>)</span><br />
			<?php } ?>

			<input type="checkbox" name="comment_cookies" value="1" checked="checked" tabindex="7" id="comment_cookies" /> <label for="comment_cookies"><?php echo T_('Remember me') ?></label> <span class="notes"><?php echo T_('(Set cookies for name, email &amp; url)') ?></span>
			</div>
		</fieldset>
	
		<fieldset>
			<div class="input">
				<input type="submit" name="submit" class="buttonarea" value="Send comment" tabindex="8" />
			</div>
		</fieldset>
	
		<div class="clear"></div>
	
	</form>

