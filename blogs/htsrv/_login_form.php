<?php 
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This is the login form
 */

	$page_title = T_('Login form');
	require(dirname(__FILE__).'/_header.php'); 
?>
<a href="<?php echo $htsrvurl ?>/register.php" class="b2menutop"><?php echo T_('register ?') ?></a><br />
<a href="<?php echo $htsrvurl ?>/login.php?action=lostpassword" class="b2menutop"><?php echo T_('lost your password ?') ?></a>
</td>
</tr>

<tr height="150"><td align="right" valign="bottom" height="150" colspan="2">

<?php
if ($error)
{
	echo "<div align=\"right\" style=\"padding:4px;\"><font color=\"#FF0000\">$error</font><br />&nbsp;</div>";
}
?>

<form name="" action="<?php echo $htsrvurl ?>/login.php" method="post">
<?php 
	if( $mode == 'bookmarklet' ) 
	{	// We're in the process of bookmarkletting somethin, we don't want to loose it:
		param( 'text', 'html', '' );
		param( 'popupurl', 'html', '' );
		param( 'popuptitle', 'html', '' );
	?>
		<input type="hidden" name="mode" value="bookmarklet" />
		<input type="hidden" name="text" value="<?php echo format_to_output( $text, 'formvalue' ) ?>" />
		<input type="hidden" name="popupurl" value="<?php echo format_to_output( $popupurl, 'formvalue' ) ?>" />
		<input type="hidden" name="popuptitle" value="<?php echo format_to_output( $popuptitle, 'formvalue' ) ?>" />
	<?php 
	} 
?>
	<input type="hidden" name="redirect_to" value="<?php echo $redirect_to ?>" />
	<input type="hidden" name="action" value="login" />
	<table width="100" style="background-color: #ffffff">
	<tr><td align="right"><?php echo T_('Login:') ?></td>
		<td><input type="text" name="log" value="<?php echo $log ?>" size="8" />&nbsp;&nbsp;&nbsp;</td></tr>
	<tr><td align="right"><?php echo T_('Password:') ?></td>
		<td><input type="password" name="pwd" value="" size="8" />&nbsp;&nbsp;&nbsp;</td></tr>
	<tr><td>&nbsp;</td>
		<td><input type="submit" name="Submit2" value="OK" class="search">&nbsp;&nbsp;&nbsp;</td></tr>
	</table>

</form>

<?php 
	require(dirname(__FILE__).'/_footer.php'); 
?>