<?php
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This is the login form
 */
$page_title = T_('Login form');
$page_icon = 'icon_login.gif';
require(dirname(__FILE__).'/_header.php');

param( 'redirect_to', 'string', $ReqURI );
param( 'log', 'string', '' );		// last typed login

$location = $redirect_to;
?>

<form action="<?php echo $location  ?>" method="post" class="fform">

	<?php
		if( !empty($mode) )
		{	// We're in the process of bookmarkletting something, we don't want to loose it:
			param( 'text', 'html', '' );
			param( 'popupurl', 'html', '' );
			param( 'popuptitle', 'html', '' );
		?>
			<input type="hidden" name="mode" value="<?php echo format_to_output( $mode, 'formvalue' ) ?>" />
			<input type="hidden" name="text" value="<?php echo format_to_output( $text, 'formvalue' ) ?>" />
			<input type="hidden" name="popupurl" value="<?php echo format_to_output( $popupurl, 'formvalue' ) ?>" />
			<input type="hidden" name="popuptitle" value="<?php echo format_to_output( $popuptitle, 'formvalue' ) ?>" />
		<?php
		}
	?>

	<fieldset>

		<div class="center"><span class="notes"><?php printf( T_('You will have to accept cookies in order to log in.') ) ?></span></div>

		<fieldset>
			<div class="label"><label for="log"><?php echo T_('Login:') ?></label></div>
			<div class="input"><input type="text" name="log" id="log" size="16" maxlength="20" value="<?php echo format_to_output($log, 'formvalue'); ?>" class="large" /></div>
		</fieldset>

		<fieldset>
			<div class="label"><label for="pwd"><?php echo T_('Password:') ?></label></div>
			<div class="input"><input type="password" name="pwd" id="pwd" size="16" maxlength="20" value="" class="large" /></div>
		</fieldset>

		<fieldset>
			<div class="input">
				<input type="submit" name="submit" value="<?php echo T_('Log in!') ?>" class="search" />
			</div>
		</fieldset>
	</fieldset>
</form>

<div style="text-align:right">
<?php user_register_link( '', ' &middot; ' )?>
<a href="<?php echo $htsrv_url ?>/login.php?action=lostpassword&redirect_to=<?php echo urlencode( $redirect_to ) ?>"><?php echo T_('Lost your password ?') ?></a>
</div>

<?php
	require(dirname(__FILE__).'/_footer.php');
?>