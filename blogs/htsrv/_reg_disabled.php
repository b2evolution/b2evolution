<?php 
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This is the registration form
 */

	$page_title = T_('Registration Currently Disabled');
	require(dirname(__FILE__).'/_header.php'); 
?>
registration disabled
</td>
</tr>

<tr height="150">
<td align="center" valign="center" height="150" colspan="2">
<table width="80%" height="100%">
<tr><td class="b2menutop">
<?php echo T_('User registration is currently not allowed.') ?><br />
<a href="<?php echo $baseurl ?>" ><?php echo T_('Home') ?></a>
</td></tr></table>

<?php 
	require(dirname(__FILE__).'/_footer.php'); 
?>