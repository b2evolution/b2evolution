<?php
/**
 * This is the account validation form. It gets included if the user needs to validate his account.
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

/**
 * Include page header:
 */
$page_title = T_('Email address validation');
$page_icon = 'register';
require dirname(__FILE__).'/_html_header.inc.php';

$Form = new Form( $secure_htsrv_url.'login.php', 'form_validatemail', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'validateform' );
$Form->hidden( 'action', 'req_validatemail');
$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $secure_htsrv_url) );
$Form->hidden( 'req_validatemail_submit', 1 ); // to know if the form has been submitted

$Form->begin_fieldset( T_('Email address validation') );

	echo '<ol>';
	echo '<li>'.T_('Please confirm your email address below.').'</li>';
	echo '<li>'.T_('An email will be sent to this address immediately.').'</li>';
	echo '<li>'.T_('As soon as you receive the email, click on the link therein to activate your account.').'</li>';
	echo '</ol>';

	$Form->text_input( 'email', $email, 16, T_('Email'), '', array( 'maxlength'=>255, 'class'=>'input_text', 'required'=>true ) );

	$Plugins->trigger_event( 'DisplayValidateAccountFormFieldset', array( 'Form' => & $Form ) );

// TODO: the form submit value is too wide (in Konqueror and most probably in IE!)
$Form->end_form( array(array( 'name'=>'form_validatemail_submit', 'value'=>T_('Send me an email now!'), 'class'=>'ActionButton' )) ); // display hidden fields etc


if( $current_User->group_ID == 1 )
{ // allow admin users to validate themselves by a single click:
	$Form = new Form( $secure_htsrv_url.'login.php', 'form_validatemail', 'post', 'fieldset' );
	$Form->begin_form( 'fform' );

	$Form->add_crumb( 'validateform' );
	$Form->hidden( 'action', 'validatemail');
	$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $secure_htsrv_url) );
	$Form->hidden( 'reqID', 1 );
	$Form->hidden( 'sessID', $Session->ID );

	$Form->begin_fieldset();
	echo '<p>'.sprintf( T_('Since you are an admin user, you can validate your email address (%s) by a single click.' ), $current_User->email ).'</p>';
	// TODO: the form submit value is too wide (in Konqueror and most probably in IE!)
	$Form->end_form( array(array( 'name'=>'form_validatemail_admin_submit', 'value'=>T_('Activate my account!'), 'class'=>'ActionButton' )) ); // display hidden fields etc
}
?>

<div style="text-align:right">
	<?php
	user_logout_link();
	?>
</div>

<?php
require dirname(__FILE__).'/_html_footer.inc.php';


/*
 * $Log$
 * Revision 1.10  2011/09/30 10:16:51  efy-yurybakh
 * Make a big sprite with all backoffice icons
 *
 * Revision 1.9  2011/09/26 14:53:27  efy-asimo
 * Login problems with multidomain installs - fix
 * Insert globals: samedomain_htsrv_url, secure_htsrv_url;
 *
 * Revision 1.8  2011/09/22 08:55:00  efy-asimo
 * Login problems with multidomain installs - fix
 *
 * Revision 1.7  2011/09/04 22:13:25  fplanque
 * copyright 2011
 *
 * Revision 1.6  2010/02/08 17:56:56  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.5  2010/01/30 18:55:39  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.4  2010/01/03 13:45:37  fplanque
 * set some crumbs (needs checking)
 *
 * Revision 1.3  2009/03/08 23:58:09  fplanque
 * 2009
 *
 * Revision 1.2  2008/01/21 09:35:43  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:02:40  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.14  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.13  2007/01/19 03:06:56  fplanque
 * Changed many little thinsg in the login procedure.
 * There may be new bugs, sorry. I tested this for several hours though.
 * More refactoring to be done.
 *
 * Revision 1.12  2006/12/09 01:55:36  fplanque
 * feel free to fill in some missing notes
 * hint: "login" does not need a note! :P
 *
 * Revision 1.11  2006/11/24 18:27:26  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.10  2006/10/15 21:30:46  blueyed
 * Use url_rel_to_same_host() for redirect_to params.
 *
 * Revision 1.9  2006/07/08 17:04:18  fplanque
 * minor
 *
 * Revision 1.8  2006/07/08 13:33:54  blueyed
 * Autovalidate admin group instead of primary admin user only.
 * Also delegate to req_validatemail action on failure directly instead of providing a link.
 *
 * Revision 1.7  2006/07/04 23:38:11  blueyed
 * Validate email: admin user (#1) has an extra button to validate him/herself through the form; store multiple req_validatemail keys in the user's session.
 *
 * Revision 1.6  2006/06/25 23:34:15  blueyed
 * wording pt2
 *
 * Revision 1.5  2006/06/25 23:23:38  blueyed
 * wording
 *
 * Revision 1.4  2006/06/22 22:30:04  blueyed
 * htsrv url for password related scripts (login, register and profile update)
 *
 * Revision 1.3  2006/04/27 21:49:55  blueyed
 * todo
 *
 * Revision 1.2  2006/04/24 20:52:31  fplanque
 * no message
 *
 * Revision 1.1  2006/04/22 02:36:38  blueyed
 * Validate users on registration through email link (+cleanup around it)
 *
 */
?>