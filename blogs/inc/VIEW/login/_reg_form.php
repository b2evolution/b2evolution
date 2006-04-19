<?php
/**
 * This is the registration form
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://cvs.sourceforge.net/viewcvs.py/evocms/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


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

$Form->text( 'login', format_to_output($login, 'formvalue'), 16,  T_('Login'), '', 20, 'input_text' );
?>

	<fieldset>
		<div class="label"><label for="pass1"><?php echo T_('Password') ?><br /><?php echo T_('(twice)').'<br />' ?></label></div>
		<div class="input">
		<input type="password" name="pass1" id="pass1" size="16" maxlength="50" value="" class="input_text" />
		<input type="password" name="pass2" id="pass2" size="16" maxlength="50" value="" class="input_text" />
		<span class="notes"><?php printf( T_('Minimum %d characters, please.'), $Settings->get('user_minpwdlen') ) ?></span>
		</div>
	</fieldset>

	<?php
	$Form->text( 'email', format_to_output($email, 'formvalue'), 16,  T_('Email'), '', 100, 'input_text' );

	$Form->select( 'locale', $locale, 'locale_options_return', T_('Locale'), T_('Preferred language') );

	$Plugins->trigger_event( 'DisplayRegisterFormFieldset', array( 'Form' => & $Form ) );
	?>

	<fieldset>
		<div class="input">
			<input type="submit" name="submit" value="<?php echo T_('Register!') ?>" class="search" />
		</div>
	</fieldset>
</fieldset>
<?php
$Form->end_form(); // display hidden fields etc
?>

<div style="text-align:right">
	<a href="<?php echo $htsrv_url.'login.php' ?>"><?php echo T_('Log into existing account...') ?></a>
</div>

<?php
require dirname(__FILE__).'/_footer.php';

/*
 * $Log$
 * Revision 1.3  2006/04/19 20:13:52  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 */
?>