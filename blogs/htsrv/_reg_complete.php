<?php 
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This is displayed when registration is complete
 */
$page_title = T_('Registration complete');
$page_icon = 'icon_register.gif';
require(dirname(__FILE__).'/_header.php'); 
?>
<p><?php echo T_('Login:') ?> <strong><?php echo $login ?>&nbsp;</strong></p>
<p><?php echo T_('Email') ?>: <strong><?php echo $email ?>&nbsp;</strong></p>

<form name="login" action="<?php echo $htsrv_url ?>/login.php" method="post">
<input type="hidden" name="log" value="<?php echo $login ?>" />
<input type="hidden" name="redirect_to" value="<?php echo $redirect_to ?>" />
<input type="submit" class="search" value="<?php  echo T_('Log in!') ?>" name="submit" />
</form>


<?php 
	require(dirname(__FILE__).'/_footer.php'); 
?>