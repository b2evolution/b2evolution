<?php
/**
 * This is the lost password form, from where the user can request
 * a set-password-link to be sent to his/her email address.
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
$page_title = T_('Lost password ?');
$page_icon = 'icon_login.gif';
require dirname(__FILE__).'/_header.php';

Log::display( '', '', T_('A link to change your password will be sent to you by email.'), 'note' );


$Form = & new Form( $htsrv_url_sensitive.'login.php', '', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

$Form->hidden( 'action', 'retrievepassword' );
$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );

$Form->begin_fieldset();
$Form->text( 'login', $login, 16, T_('Login'), '', 20 , 'input_text' );

echo $Form->fieldstart.$Form->inputstart;
// TODO: the form submit value is too wide (in Konqueror and most probably in IE!)
$Form->submit_input( array( /* TRANS: Text for submit button to request an activation link by email */ 'value' => T_('Request email to change my password!'), 'class' => 'ActionButton' ) );
echo $Form->inputend.$Form->fieldend;

$Form->end_fieldset();;

$Form->end_form();

require dirname(__FILE__).'/_footer.php';

/*
 * $Log$
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