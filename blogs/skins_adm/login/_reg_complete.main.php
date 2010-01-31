<?php
/**
 * This is displayed when registration is complete
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
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
$page_icon = 'icon_register.gif';
require dirname(__FILE__).'/_html_header.inc.php';

// dh> TODO: this form is not really required and only used for the info fields below.
$Form = new Form( $htsrv_url_sensitive.'login.php', 'login', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

$Form->hidden( 'login', $login );
$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );

$Form->begin_fieldset();
$Form->info( T_('Login'), $login );
$Form->info( T_('Email'), $email );
$Form->end_fieldset();

// Now the user has been logged in automatically at the end of the registration progress.
// Allow him to proceed or go to the blogs, though he will see the "validate account" screen then,
// if he has not clicked the validation link yet and validation is required.
if( empty($redirect_to) )
{
	$redirect_to = $baseurl; // dh> this was the old behaviour, I think there could be a better default
}
echo '<p class="center"><a href="'
	.htmlspecialchars(url_rel_to_same_host($redirect_to, $htsrv_url_sensitive))
	.'">'.T_('Continue').' &raquo;</a> '; // dh> TODO: this does not seem to be sensible for dir=rtl.
echo '</p>';


$Form->end_form();

require dirname(__FILE__).'/_html_footer.inc.php';

/*
 * $Log$
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