<?php 
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This is the lost password form
 */
$page_title = T_('Lost password ?');
$page_icon = 'icon_login.gif';
require(dirname(__FILE__).'/_header.php'); 
?>
<p><?php echo T_('A new password will be generated and sent to you by email.') ?></p>

<form action="<?php echo $htsrv_url ?>/login.php" method="post" class="fform">
<input type="hidden" name="action" value="retrievepassword" />

	<fieldset>
		<div class="label"><label for="user_login"><?php echo T_('Login:') ?></label></div> 
		<div class="input"><input type="text" name="user_login" id="user_login" size="16" maxlength="20" value="" class="large" /></div>
	</fieldset>

	<fieldset>
		<div class="input">
			<input type="submit" name="submit" value="<?php echo T_('Generate new password!') ?>" class="search">
		</div>
	</fieldset>

</form>

<?php 
	require(dirname(__FILE__).'/_footer.php'); 
?>