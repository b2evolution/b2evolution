<?php
/**
 * This is the registration form
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package htsrv
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Include page header:
 */
$page_title = T_('Register form');
$page_icon = 'icon_register.gif';
require dirname(__FILE__).'/_header.php';


$Form = & new Form( $htsrv_url.'register.php', '', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

$Form->hidden( 'action', 'register');
$Form->hidden( 'redirect_to', $redirect_to );

echo $Form->fieldstart;

$Form->text( 'yourname', format_to_output($yourname, 'formvalue'), 16,  T_('Login'), '', 20, 'input_text' );
$Form->text( 'email', format_to_output($email, 'formvalue'), 16,  T_('Email'), '', 100, 'input_text' );
?>

	<fieldset>
		<div class="input">
			<input type="submit" name="submit" value="<?php echo T_('Register!') ?>" class="search" />
		</div>
	</fieldset>
</fieldset>
</form>

<div style="text-align:right">
	<a href="<?php echo $htsrv_url.'login.php' ?>"><?php echo T_('Log into existing account...') ?></a>
</div>

<?php
	require dirname(__FILE__).'/_footer.php';
?>