<?php
/**
 * This is the lost password form, from where the user can request
 * a set-password-link to be sent to his/her email address.
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
$page_title = T_('Lost password ?');
$page_icon = 'login';
require dirname(__FILE__).'/_html_header.inc.php';

$Form = new Form( $secure_htsrv_url.'login.php', '', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

	$Form->add_crumb( 'lostpassform' );
	$Form->hidden( 'action', 'retrievepassword' );
	$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $secure_htsrv_url) );

	$Form->begin_fieldset();

	echo '<ol>';
	echo '<li>'.T_('Please enter your login below. Do <strong>NOT</strong> enter your e-mail address!').'</li>';
	echo '<li>'.T_('An email will be sent to your registered email address immediately.').'</li>';
	echo '<li>'.T_('As soon as you receive the email, click on the link therein to change your password.').'</li>';
	echo '</ol>';

	$Form->text( 'login', $login, 16, T_('Login'), T_('Note: your login is NOT your email address.'), 20, 'input_text' );

	$Form->buttons_input( array(array( /* TRANS: Text for submit button to request an activation link by email */ 'value' => T_('Send me an email now!'), 'class' => 'ActionButton' )) );

	$Form->info( '', '', sprintf( T_('Your IP address (%s) and the current time are being logged.'), $Hit->IP ) );

	$Form->end_fieldset();;

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
 * Revision 1.12  2011/09/07 22:44:41  fplanque
 * UI cleanup
 *
 * Revision 1.11  2011/09/05 18:10:54  sam2kb
 * No break in tranlsated text
 *
 * Revision 1.10  2011/09/04 22:13:25  fplanque
 * copyright 2011
 *
 * Revision 1.9  2011/08/29 09:32:22  efy-james
 * Add ip on login form
 *
 * Revision 1.8  2011/08/26 03:01:54  efy-james
 * Add IP on login form
 *
 * Revision 1.7  2010/02/08 17:56:56  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.6  2010/01/03 13:45:36  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.5  2009/05/28 20:21:28  blueyed
 * Streamline code to display form buttons.
 *
 * Revision 1.4  2009/03/08 23:58:01  fplanque
 * 2009
 *
 * Revision 1.3  2008/01/21 09:35:43  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/11/01 19:52:13  fplanque
 * UI
 *
 * Revision 1.1  2007/06/25 11:02:37  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.12  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.11  2007/01/20 01:44:43  blueyed
 * removed done todo
 *
 * Revision 1.10  2007/01/19 03:06:56  fplanque
 * Changed many little thinsg in the login procedure.
 * There may be new bugs, sorry. I tested this for several hours though.
 * More refactoring to be done.
 *
 * Revision 1.9  2006/11/24 18:27:26  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.8  2006/10/15 21:30:46  blueyed
 * Use url_rel_to_same_host() for redirect_to params.
 *
 * Revision 1.7  2006/06/25 23:34:15  blueyed
 * wording pt2
 *
 * Revision 1.6  2006/06/25 23:23:38  blueyed
 * wording
 *
 * Revision 1.5  2006/06/22 22:30:04  blueyed
 * htsrv url for password related scripts (login, register and profile update)
 *
 * Revision 1.4  2006/04/27 21:49:55  blueyed
 * todo
 *
 * Revision 1.3  2006/04/24 20:52:31  fplanque
 * no message
 *
 * Revision 1.2  2006/04/19 20:13:52  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 */
?>