<?php
/**
 * This is the account validation form. It gets included if the user needs to validate his account.
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
$page_title = T_('Account validation form');
$page_icon = 'icon_register.gif';
require dirname(__FILE__).'/_header.php';


$Form = & new Form( $htsrv_url_sensible.'login.php', 'form_validatemail', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

$Form->hidden( 'action', 'req_validatemail');
$Form->hidden( 'redirect_to', $redirect_to );
$Form->hidden( 'req_validatemail_submit', 1 ); // to know if the form has been submitted

$Form->begin_fieldset();

	$Form->text_input( 'email', $email, 16, T_('Email'), array( 'maxlength'=>255, 'class'=>'input_text', 'required'=>true ) );

	$Plugins->trigger_event( 'DisplayValidateAccountFormFieldset', array( 'Form' => & $Form ) );

// TODO: the form submit value is too wide (in Konqueror and most probably in IE!)
$Form->end_form( array(array( 'name'=>'form_validatemail_submit', 'value'=>T_('Request email to activate my account!'), 'class'=>'ActionButton' )) ); // display hidden fields etc
?>

<div style="text-align:right">
	<?php
	user_logout_link();
	?>
</div>

<?php
require dirname(__FILE__).'/_footer.php';


/*
 * $Log$
 * Revision 1.4  2006/06/22 22:30:04  blueyed
 * htsrv url for sensible scripts (login, register and profile update)
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