<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This is the registration form
 */

$page_title = T_('Register form');
$page_icon = 'icon_register.gif';
require(dirname(__FILE__).'/_header.php');
?>


<form method="post" action="<?php echo $htsrv_url ?>/register.php" class="fform">
<input type="hidden" name="action" value="register" />
<input type="hidden" name="redirect_to" value="<?php echo $redirect_to ?>" />

<fieldset>
	<fieldset>
		<div class="label"><label for="login"><?php echo T_('Login:') ?></label></div>
		<div class="input"><input type="text" name="login" id="login" size="16" maxlength="20" value="<?php echo format_to_output($login, 'formvalue'); ?>" class="large" /></div>
	</fieldset>

	<fieldset>
		<div class="label"><label for="pass1"><?php echo T_('Password:') ?><br /><?php echo T_('(twice)').'<br />' ?></label></div>
		<div class="input">
		<input type="password" name="pass1" id="pass1" size="16" maxlength="20" value="" class="large" />
		<input type="password" name="pass2" id="pass2" size="16" maxlength="20" value="" class="large" />
		<span class="notes"><?php printf( T_('Minimum %d characters, please.'), $Settings->get('user_minpwdlen') ) ?></span>
		</div>
	</fieldset>

	<fieldset>
		<div class="label"><label for="email"><?php echo T_('Email') ?>:</label></div>
		<div class="input"><input type="text" name="email" id="email" size="16" maxlength="100" value="<?php echo format_to_output($email, 'formvalue'); ?>" class="large" /></div>
	</fieldset>

	<?php
	form_select( 'locale', $default_locale, 'locale_options', T_('Locale'), T_('Prefered language') );
	?>
	<fieldset>
		<div class="input">
			<input type="submit" name="submit" value="<?php echo T_('Register!') ?>" class="search" />
		</div>
	</fieldset>
</fieldset>
</form>

<div style="text-align:right">
	<a href="<?php echo $htsrv_url.'/login.php' ?>"><?php echo T_('Log into existing account...') ?></a>
</div>

<?php
	require(dirname(__FILE__).'/_footer.php');
?>