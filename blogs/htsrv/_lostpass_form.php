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
	require(dirname(__FILE__).'/_header.php'); 
?>

&nbsp;</td>
</tr>

<tr height="150"><td align="right" valign="bottom" height="150" colspan="2">

<p align="center" style="color: #b0b0b0"><?php echo T_('Type your login here and click OK. You will receive an email with your password.') ?></p>
<?php
		if ($error)
		{
			echo "<div align=\"right\" style=\"padding:4px;\"><font color=\"#FF0000\">$error</font><br />&nbsp;</div>";
		}
?>

<form name="" action="<?php echo $htsrvurl ?>/login.php" method="post">
<input type="hidden" name="action" value="retrievepassword" />
<table width="100" style="background-color: #ffffff">
<tr><td align="right"><?php echo T_('Login') ?></td>
	<td><input type="text" name="user_login" value="" size="8" />&nbsp;&nbsp;&nbsp;</td></tr>
<tr><td>&nbsp;</td>
	<td><input type="submit" name="Submit2" value="<?php echo T_('OK') ?>" class="search">&nbsp;&nbsp;&nbsp;</td></tr>
</table>

</form>

<?php 
	require(dirname(__FILE__).'/_footer.php'); 
?>