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
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Include page header:
 */
$page_title = T_('Register form');
$page_icon = 'icon_register.gif';
require dirname(__FILE__).'/_header.php';


$Form = & new Form( $htsrv_url_sensitive.'register.php', '', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

$Form->hidden( 'action', 'register');
$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );

echo $Form->fieldstart;

$Form->text_input( 'login', $login, 16,  T_('Login'), array( 'maxlength'=>20, 'class'=>'input_text', 'required'=>true ) );
?>

	<fieldset>
		<div class="label"><label for="pass1"><?php echo T_('Password') ?><br /><?php echo T_('(twice)').'<br />' ?></label></div>
		<div class="input">
		<input type="password" name="pass1" id="pass1" size="16" maxlength="50" value="" class="input_text field_required" />
		<input type="password" name="pass2" id="pass2" size="16" maxlength="50" value="" class="input_text field_required" />
		<span class="notes"><?php printf( T_('Minimum %d characters, please.'), $Settings->get('user_minpwdlen') ) ?></span>
		</div>
	</fieldset>

	<?php
	$Form->text_input( 'email', $email, 16, T_('Email'), array( 'maxlength'=>255, 'class'=>'input_text', 'required'=>true ) );

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
	<a href="<?php echo $htsrv_url_sensitive.'login.php' ?>"><?php echo T_('Log into existing account...') ?></a>
</div>

<?php
require dirname(__FILE__).'/_footer.php';

/*
 * $Log$
 * Revision 1.10  2006/11/24 18:27:26  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.9  2006/10/15 21:30:46  blueyed
 * Use url_rel_to_same_host() for redirect_to params.
 *
 * Revision 1.8  2006/06/25 23:34:15  blueyed
 * wording pt2
 *
 * Revision 1.7  2006/06/25 23:23:38  blueyed
 * wording
 *
 * Revision 1.6  2006/06/22 22:30:04  blueyed
 * htsrv url for password related scripts (login, register and profile update)
 *
 * Revision 1.5  2006/04/22 01:57:36  blueyed
 * adjusted maxlength for email
 *
 * Revision 1.4  2006/04/21 16:56:36  blueyed
 * Mark fields as required; small fix (double-encoding)
 *
 * Revision 1.3  2006/04/19 20:13:52  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 */
?>