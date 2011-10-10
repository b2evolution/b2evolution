<?php
/**
 * This is the registration form
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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

$Form = new Form( $secure_htsrv_url.'register.php', '', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'regform' );
$Form->hidden( 'action', 'register' );
$source = param( 'source', 'string', '' );
$Form->hidden( 'source', $source );
$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $secure_htsrv_url) );

$Form->begin_fieldset();

	$Form->text_input( 'login', $login, 22, T_('Login'), T_('Choose a username.'), array( 'maxlength' => 20, 'class' => 'input_text', 'required' => true ) );

	$Form->password_input( 'pass1', '', 18, T_('Password'), array( 'note'=>T_('Choose a password.'), 'maxlength' => 70, 'class' => 'input_text', 'required'=>true ) );
	$Form->password_input( 'pass2', '', 18, '', array( 'note'=>T_('Please type your password again.'), 'maxlength' => 70, 'class' => 'input_text', 'required'=>true ) );

	$Form->text_input( 'email', $email, 55, T_('Email'), '<br />'.T_('We respect your privacy. Your email will remain strictly confidential.'), array( 'maxlength'=>255, 'class'=>'input_text', 'required'=>true ) );

	$registration_require_country = (bool)$Settings->get('registration_require_country');

	if( $registration_require_country )
	{
		$CountryCache = & get_CountryCache();
		$Form->select_country( 'country', $country, $CountryCache, T_('Country'), array('allow_none'=>true, 'required'=>true) );
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

	$Plugins->trigger_event( 'DisplayRegisterFormFieldset', array( 'Form' => & $Form ) );

	$Form->buttons_input( array( array('name'=>'submit', 'value'=>T_('Register my account now!'), 'class'=>'ActionInput', 'style'=>'font-size: 120%' ) ) );

$Form->end_fieldset();
$Form->end_form(); // display hidden fields etc
?>

<div style="margin-top: 1em">
	<a href="<?php echo $secure_htsrv_url.'login.php?redirect_to='.rawurlencode(url_rel_to_same_host($redirect_to, $secure_htsrv_url)) ?>">&laquo; <?php echo T_('Already have an account... ?') ?></a>
</div>

<?php
require dirname(__FILE__).'/_html_footer.inc.php';

/*
 * $Log$
 * Revision 1.33  2011/10/10 19:48:31  fplanque
 * i18n & login display cleaup
 *
 * Revision 1.32  2011/09/30 10:16:51  efy-yurybakh
 * Make a big sprite with all backoffice icons
 *
 * Revision 1.31  2011/09/26 09:08:14  efy-vitalij
 * remake select_input_object to select_country field
 *
 * Revision 1.30  2011/09/22 08:55:00  efy-asimo
 * Login problems with multidomain installs - fix
 *
 * Revision 1.29  2011/09/18 00:58:44  fplanque
 * forms cleanup
 *
 * Revision 1.28  2011/09/12 08:05:18  efy-asimo
 * Remember to gender
 *
 * Revision 1.27  2011/09/08 23:29:27  fplanque
 * More blockcache/widget fixes around login/register links.
 *
 * Revision 1.26  2011/09/07 23:34:09  fplanque
 * i18n update
 *
 * Revision 1.25  2011/09/07 22:44:41  fplanque
 * UI cleanup
 *
 * Revision 1.24  2011/09/06 20:48:54  sam2kb
 * No new line at end of file
 *
 * Revision 1.23  2011/09/06 16:25:18  efy-james
 * Require special chars in password
 *
 * Revision 1.22  2011/09/06 03:25:41  fplanque
 * i18n update
 *
 * Revision 1.21  2011/09/05 18:10:54  sam2kb
 * No break in tranlsated text
 *
 * Revision 1.20  2011/09/04 22:13:25  fplanque
 * copyright 2011
 *
 * Revision 1.19  2011/08/29 09:32:22  efy-james
 * Add ip on login form
 *
 * Revision 1.18  2011/08/26 03:01:54  efy-james
 * Add IP on login form
 *
 * Revision 1.17  2011/06/14 13:33:56  efy-asimo
 * in-skin register
 *
 * Revision 1.16  2011/02/17 14:56:38  efy-asimo
 * Add user source param
 *
 * Revision 1.15  2010/11/24 16:05:52  efy-asimo
 * User country and gender options modifications
 *
 * Revision 1.14  2010/11/24 14:55:30  efy-asimo
 * Add user gender
 *
 * Revision 1.13  2010/02/08 17:56:56  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.12  2010/01/03 13:45:37  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.11  2009/10/10 21:43:09  tblue246
 * cleanup
 *
 * Revision 1.10  2009/09/26 12:00:44  tblue246
 * Minor/coding style
 *
 * Revision 1.9  2009/09/25 07:33:31  efy-cantor
 * replace get_cache to get_*cache
 *
 * Revision 1.8  2009/09/16 06:55:13  efy-bogdan
 * Require country checkbox added
 *
 * Revision 1.7  2009/03/08 23:58:09  fplanque
 * 2009
 *
 * Revision 1.6  2008/01/21 09:35:43  fplanque
 * (c) 2008
 *
 * Revision 1.5  2008/01/14 23:41:48  fplanque
 * cleanup load_funcs( urls ) in main because it is ubiquitously used
 *
 * Revision 1.4  2008/01/06 17:10:58  blueyed
 * Fix call to undefined function when accessing register.php and _url.funcs.php has not been loaded
 *
 * Revision 1.3  2007/12/09 22:59:22  blueyed
 * login and register form: Use Form::buttons_input for buttons
 *
 * Revision 1.2  2007/12/09 03:12:34  blueyed
 * Fix layout of register form
 *
 * Revision 1.1  2007/06/25 11:02:40  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.13  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.12  2007/02/12 00:20:41  blueyed
 * Pass redirect_to param to "Login..." link
 *
 * Revision 1.11  2006/12/09 01:55:36  fplanque
 * feel free to fill in some missing notes
 * hint: "login" does not need a note! :P
 *
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