<?php
/**
 * This is displayed when registration is complete
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
$page_title = T_('Registration complete');
$page_icon = 'register';
require dirname(__FILE__).'/_html_header.inc.php';

// dh> TODO: this form is not really required and only used for the info fields below.
$Form = new Form( $secure_htsrv_url.'login.php', 'login', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

$Form->hidden( 'login', $login );
$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $secure_htsrv_url) );
$Form->hidden( 'inskin', 0 );

// Now the user has been logged in automatically at the end of the registration progress.
// Allow him to proceed or go to the blogs, though he will see the "validate account" screen then,
// if he has not clicked the validation link yet and validation is required.
if( empty($redirect_to) )
{
	$redirect_to = $baseurl; // dh> this was the old behaviour, I think there could be a better default
}

if( $action == 'reg_complete' )
{
	$Form->begin_fieldset();
	$Form->info( T_('Login'), $login );
	$Form->info( T_('Email'), $email );
	$Form->end_fieldset();

	echo '<p class="center"><a href="'
		.htmlspecialchars(url_rel_to_same_host($redirect_to, $secure_htsrv_url))
		.'">'.T_('Continue').' &raquo;</a> '; // dh> TODO: this does not seem to be sensible for dir=rtl.
	echo '</p>';
}
elseif( $action == 'reg_validation' )
{
	echo '<p>'.sprintf( T_( 'An email has just been sent to %s . Please check your email and click on the validation link you will find in that email.' ), '<b>'.$email.'</b>' ).'</p>';
	echo '<p>'.sprintf( T_( 'If you have not received the email in the next few minutes, please check your spam folder. The email was sent from %s and has the title &laquo;%s&raquo;.' ), $notify_from,
					'<b>'.sprintf( T_('Validate your email address for "%s"'), $login ).'</b>' ).'</p>';
	echo '<p>'.T_( 'If you still can\'t find the email or if you would like to try with a different email address,' ).' '.
					'<a href="'.$redirect_to.'">'.T_( 'click here to try again' ).'.</a></p>';
}

$Form->end_form();

require dirname(__FILE__).'/_html_footer.inc.php';

/*
 * $Log$
 * Revision 1.15  2011/09/30 10:16:51  efy-yurybakh
 * Make a big sprite with all backoffice icons
 *
 * Revision 1.14  2011/09/26 14:53:27  efy-asimo
 * Login problems with multidomain installs - fix
 * Insert globals: samedomain_htsrv_url, secure_htsrv_url;
 *
 * Revision 1.13  2011/09/22 08:55:00  efy-asimo
 * Login problems with multidomain installs - fix
 *
 * Revision 1.12  2011/09/06 03:25:41  fplanque
 * i18n update
 *
 * Revision 1.11  2011/09/05 23:00:24  fplanque
 * minor/doc/cleanup/i18n
 *
 * Revision 1.10  2011/09/05 18:02:56  sam2kb
 * Never break tranlsated text
 *
 * Revision 1.9  2011/09/04 22:13:25  fplanque
 * copyright 2011
 *
 * Revision 1.8  2011/06/14 13:33:56  efy-asimo
 * in-skin register
 *
 * Revision 1.7  2010/02/08 17:56:56  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.6  2010/01/31 18:11:49  blueyed
 * Fix previous &new replacements.
 *
 * Revision 1.5  2010/01/30 18:55:39  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.4  2009/03/08 23:58:01  fplanque
 * 2009
 *
 * Revision 1.3  2008/01/21 09:35:43  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/06/25 23:19:07  blueyed
 * Use $redirect_to (and fallback only to $baseurl) for "Continue" link in favor of too generic "Go to blogs link"
 *
 * Revision 1.1  2007/06/25 11:02:38  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.9  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.8  2007/02/13 21:03:40  blueyed
 * Improved login/register/validation process:
 * - "Your account has been validated already." if an account had already been validated
 * - "We have already sent you %d email(s) with a validation link." note
 * - Autologin the user after he has registered (he just typed his credentials!)
 *
 * Revision 1.7  2006/11/24 18:27:26  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.6  2006/10/15 21:30:46  blueyed
 * Use url_rel_to_same_host() for redirect_to params.
 *
 * Revision 1.5  2006/06/25 23:34:15  blueyed
 * wording pt2
 *
 * Revision 1.4  2006/06/25 23:23:38  blueyed
 * wording
 *
 * Revision 1.3  2006/06/22 22:30:04  blueyed
 * htsrv url for password related scripts (login, register and profile update)
 *
 * Revision 1.2  2006/04/19 20:13:52  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 */
?>