<?php
/**
 * This is the registration form
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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

load_class( 'regional/model/_country.class.php', 'Country' );

/**
 * Include page header:
 */
$page_title = T_('New account creation');
$wrap_width = '580px';
$wrap_height = '630px';

require dirname(__FILE__).'/_html_header.inc.php';

// set secure htsrv url with the same domain as the request has
$secure_htsrv_url = get_secure_htsrv_url();

echo str_replace( '$title$', $page_title,  $form_before );

$Form = new Form( $secure_htsrv_url.'register.php', 'register_form', 'post', 'fieldset' );

$Form->switch_template_parts( $login_form_params );

$Form->begin_form( 'form-login' );

$Plugins->trigger_event( 'DisplayRegisterFormBefore', array( 'Form' => & $Form, 'inskin' => false ) );

$Form->add_crumb( 'regform' );
$Form->hidden( 'action', 'register' );
$source = param( 'source', 'string', '' );
$Form->hidden( 'source', $source );
$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $secure_htsrv_url) );

$Form->begin_fieldset();

	$Form->text_input( $dummy_fields[ 'login' ], $login, 22, T_('Login'), '', array( 'placeholder' => T_('Choose an username'), 'maxlength' => 20, 'class' => 'input_text', 'required' => true, 'input_required' => 'required', 'input_suffix' => ' <span id="login_status"></span>', 'style' => 'width:250px' ) );

	$Form->password_input( $dummy_fields[ 'pass1' ], '', 18, T_('Password'), array( 'placeholder' => T_('Choose a password'), 'maxlength' => 70, 'class' => 'input_text', 'required' => true, 'input_required' => 'required', 'style' => 'width:250px', 'autocomplete' => 'off' ) );
	$Form->password_input( $dummy_fields[ 'pass2' ], '', 18, '', array( 'placeholder' => T_('Retype your password'), 'note' => '<div id="pass2_status" class="red"></div>', 'maxlength' => 70, 'class' => 'input_text', 'required' => true, 'input_required' => 'required', 'style' => 'width:250px', 'autocomplete' => 'off' ) );

	$Form->text_input( $dummy_fields[ 'email' ], $email, 50, T_('Email'), '<br />'.T_('We respect your privacy. Your email will remain strictly confidential.'), array( 'placeholder' => T_('Email address'), 'maxlength' => 255, 'class' => 'input_text', 'required' => true, 'input_required' => 'required', 'style' => 'width:390px' ) );

	$registration_require_country = (bool)$Settings->get('registration_require_country');

	if( $registration_require_country )
	{
		$CountryCache = & get_CountryCache();
		$Form->select_country( 'country', param( 'country', 'integer', 0 ), $CountryCache, T_('Country'), array( 'allow_none' => true, 'required' => true ) );
	}

	$registration_require_firstname = (bool)$Settings->get('registration_require_firstname');

	if( $registration_require_firstname )
	{
		$Form->text_input( 'firstname', $firstname, 18, T_('First name'), '', array( 'placeholder' => T_('Your real first name.'), 'maxlength' => 50, 'class' => 'input_text', 'required' => true, 'input_required' => 'required' ) );
	}

	$registration_require_gender = $Settings->get( 'registration_require_gender' );
	if( $registration_require_gender != 'hidden' )
	{ // Display a gender field if it is not hidden
		$Form->radio_input( 'gender', $gender, array(
					array( 'value' => 'M', 'label' => T_('A man') ),
					array( 'value' => 'F', 'label' => T_('A woman') ),
				), T_('I am'), array( 'required' => $registration_require_gender == 'required' ) );
	}

	if( $Settings->get( 'registration_ask_locale' ) )
	{
		$Form->select( 'locale', $locale, 'locale_options_return', T_('Locale'), T_('Preferred language') );
	}

	$Plugins->trigger_event( 'DisplayRegisterFormFieldset', array( 'Form' => & $Form, 'inskin' => false ) );

	$Form->buttons_input( array( array( 'name' => 'submit', 'value' => T_('Register my account now!'), 'class' => 'btn-primary btn-lg' ) ) );

$Form->end_fieldset();
$Form->end_form(); // display hidden fields etc

echo $form_after;

// Display javascript password strength indicator bar
display_password_indicator( array( 'field-width' => 252 ) );

// Display javascript login validator
display_login_validator();
?>

<div class="form-login-links">
	<a href="<?php echo $secure_htsrv_url.'login.php?redirect_to='.rawurlencode(url_rel_to_same_host($redirect_to, $secure_htsrv_url)) ?>">&laquo; <?php echo T_('Already have an account... ?') ?></a>
</div>

<?php
require dirname(__FILE__).'/_html_footer.inc.php';

?>