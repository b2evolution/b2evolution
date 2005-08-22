<?php
/**
 * This is the confirmation form
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
( $Settings->get('use_mail') ) ? $page_title = T_('Continue registration form'):$page_title = T_('Registration form');
$page_icon = 'icon_register.gif';
require dirname(__FILE__).'/_header.php';


$Form = & new Form( $htsrv_url.'confirm.php', '', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

$Form->hidden( 'action', $next_action);
$Form->hidden( 'redirect_to', $redirect_to );
$Form->hidden( 'key', $key );

echo $Form->fieldstart;

$Form->text( 'blogname' , format_to_output( $blogname , 'formvalue' ), 50 , T_('Blog name') , T_('This can only include letters and numbers') , 20 , 'input_text' );

$Form->text( 'yourname', format_to_output($yourname, 'formvalue'), 16,  T_('Login'), '', 20, 'input_text' );
?>

	<fieldset>
		<div class="label"><label for="pass1"><?php echo T_('Password') ?><br /><?php echo T_('(twice)').'<br />' ?></label></div>
		<div class="input">
		<input type="password" name="pass1" id="pass1" size="16" maxlength="20" value="" class="input_text" />
		<input type="password" name="pass2" id="pass2" size="16" maxlength="20" value="" class="input_text" />
		<span class="notes"><?php printf( T_('Minimum %d characters, please.'), $Settings->get('user_minpwdlen') ) ?></span>
		</div>
	</fieldset>

<?php
$Form->text( 'email', format_to_output($email, 'formvalue'), 16,  T_('Email'), ( $Settings->get( 'use_mail') ) ? T_('This must be the email address that the activation link was sent to'):'' , 100, 'input_text' );

$Form->select( 'locale', $locale, 'locale_options_return', T_('Locale'), T_('Preferred language') );

if ( $Settings->get('use_rules') )
{
	echo '<fieldset>
	<div class="label">'.T_('The rules').' :</div>
	<div class="input"><pre>'.file_get_contents('_rules.txt').'</pre></div>
	</fieldset>';
	$Form->checkbox( 'rules', $rules, T_('I agree') );
}
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