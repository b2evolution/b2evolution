<?php 
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This is the registration form
 */

	$page_title = T_('Registration complete');
	require(dirname(__FILE__).'/_header.php'); 
?>
<?php echo T_('Registration complete') ?>
</td>
</tr>

<tr height="150"><td align="right" valign="bottom" height="150" colspan="2">

<table width="180">
<tr><td align="right" colspan="2"><?php echo T_('Login') ?>: <strong><?php echo $login ?>&nbsp;</strong></td></tr>
<tr><td align="right" colspan="2"><?php echo T_('Email') ?>: <strong><?php echo $email ?>&nbsp;</strong></td></tr>
<tr><td width="90">&nbsp;</td>
<td><form name="login" action="<?php echo $htsrv_url ?>/login.php" method="post">
<input type="hidden" name="log" value="<?php echo $user_login ?>" />
<input type="hidden" name="redirect_to" value="<?php echo $redirect_to ?>" />
<input type="submit" class="search" value="Login" name="submit" /></form></td></tr>
</table>

<?php 
	require(dirname(__FILE__).'/_footer.php'); 
?>