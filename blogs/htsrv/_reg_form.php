<?php 
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This is the registration form
 */

	$page_title = T_('Register form');
	require(dirname(__FILE__).'/_header.php'); 
?>
registration</td>
</tr>

<tr height="150"><td align="right" valign="bottom" height="150" colspan="2">

<form method="post" action="<?php echo $htsrv_url ?>/register.php">
<input type="hidden" name="action" value="register" />
<input type="hidden" name="redirect_to" value="<?php echo $redirect_to ?>" />
<table border="0" width="180" class="menutop" style="background-color: #ffffff">
<tr>
<td width="150" align="right"><?php echo T_('Login:') ?></td>
<td>
<input type="text" name="login" size="8" maxlength="20" />
</td>
</tr>
<tr>
<td align="right"><?php echo T_('Password:') ?><br /><?php echo T_('(twice)') ?></td>
<td>
<input type="password" name="pass1" size="8" maxlength="100" />
<br />
<input type="password" name="pass2" size="8" maxlength="100" />
</td>
</tr>
<tr>
<td align="right"><?php echo T_('Email') ?></td>
<td>
<input type="text" name="email" size="8" maxlength="100" />
</td>
</tr>
<tr>
<td>&nbsp;</td>
<td><input type="submit" value="OK" class="search" name="submit">
</td>
</tr>
</table>

</form>

<?php 
	require(dirname(__FILE__).'/_footer.php'); 
?>