<?php 
/**
 * This is the lost password form
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package htsrv
 */

/**
 * Include page header:
 */
$page_title = T_('Lost password ?');
$page_icon = 'icon_login.gif';
require(dirname(__FILE__).'/_header.php'); 
?>
<p><?php echo T_('A new password will be generated and sent to you by email.') ?></p>

<form action="<?php echo $htsrv_url ?>login.php" method="post" class="fform">
	<input type="hidden" name="action" value="retrievepassword" />
	<input type="hidden" name="redirect_to" value="<?php echo $redirect_to ?>" />

	<fieldset>
		<fieldset>
			<div class="label"><label for="log"><?php echo T_('Login:') ?></label></div> 
			<div class="input"><input type="text" name="log" id="log" size="16" maxlength="20" value="" class="large" /></div>
		</fieldset>
	
		<fieldset>
			<div class="input">
				<input type="submit" name="submit" value="<?php echo T_('Generate new password!') ?>" class="search" />
			</div>
		</fieldset>
	</fieldset>

</form>

<?php 
	require(dirname(__FILE__).'/_footer.php'); 
?>