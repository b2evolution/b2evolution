<?php
/**
 * This is the registration form
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
$page_icon = 'register';
require dirname(__FILE__).'/_html_header.inc.php';

// set secure htsrv url with the same domain as the request has
$secure_htsrv_url = get_secure_htsrv_url();

$Form = new Form( $secure_htsrv_url.'register.php', 'register_form', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

$Plugins->trigger_event( 'DisplayRegisterFormBefore', array( 'Form' => & $Form, 'inskin' => false ) );

$Form->add_crumb( 'regform' );
$Form->hidden( 'action', 'register' );
$source = param( 'source', 'string', '' );
$Form->hidden( 'source', $source );
$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $secure_htsrv_url) );

$Form->begin_fieldset();

	$Form->text_input( $dummy_fields[ 'login' ], $login, 22, T_('Login'), T_('Choose an username.'), array( 'maxlength' => 20, 'class' => 'input_text', 'required' => true, 'input_suffix' => ' <span id="login_status"></span>' ) );

	$Form->password_input( $dummy_fields[ 'pass1' ], '', 18, T_('Password'), array( 'note'=>T_('Choose a password.'), 'maxlength' => 70, 'class' => 'input_text', 'required'=>true ) );
	$Form->password_input( $dummy_fields[ 'pass2' ], '', 18, '', array( 'note'=>T_('Please type your password again.'), 'maxlength' => 70, 'class' => 'input_text', 'required'=>true ) );

	$Form->text_input( $dummy_fields[ 'email' ], $email, 55, T_('Email'), '<br />'.T_('We respect your privacy. Your email will remain strictly confidential.'), array( 'maxlength'=>255, 'class'=>'input_text', 'required'=>true ) );

	$registration_require_country = (bool)$Settings->get('registration_require_country');

	if( $registration_require_country )
	{
		$CountryCache = & get_CountryCache();
		$Form->select_country( 'country', param( 'country', 'integer', 0 ), $CountryCache, T_('Country'), array('allow_none'=>true, 'required'=>true) );
	}

	$registration_require_firstname = (bool)$Settings->get('registration_require_firstname');

	if( $registration_require_firstname )
	{
		$Form->text_input( 'firstname', $firstname, 18, T_('First name'), T_('Your real first name.'), array( 'maxlength' => 50, 'class' => 'input_text', 'required' => true ) );
	}

	$registration_require_gender = $Settings->get( 'registration_require_gender' );
	if( $registration_require_gender == 'required' )
	{
		$Form->radio_input( 'gender', $gender, array(
					array( 'value' => 'M', 'label' => T_('A man') ),
					array( 'value' => 'F', 'label' => T_('A woman') ),
				), T_('I am'), array( 'required' => true ) );
	}

	if( $Settings->get( 'registration_ask_locale' ) )
	{
		$Form->select( 'locale', $locale, 'locale_options_return', T_('Locale'), T_('Preferred language') );
	}

	$Plugins->trigger_event( 'DisplayRegisterFormFieldset', array( 'Form' => & $Form, 'inskin' => false ) );

	$Form->buttons_input( array( array('name'=>'submit', 'value'=>T_('Register my account now!'), 'class'=>'ActionInput', 'style'=>'font-size: 120%' ) ) );

$Form->end_fieldset();
$Form->end_form(); // display hidden fields etc

// Display javascript password strength indicator bar
display_password_indicator();

// Display javascript login validator
display_login_validator();
?>

<div style="margin-top: 1em">
	<a href="<?php echo $secure_htsrv_url.'login.php?redirect_to='.rawurlencode(url_rel_to_same_host($redirect_to, $secure_htsrv_url)) ?>">&laquo; <?php echo T_('Already have an account... ?') ?></a>
</div>

<?php
require dirname(__FILE__).'/_html_footer.inc.php';

/*
 * $Log$
 * Revision 1.35  2013/11/06 08:05:53  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>