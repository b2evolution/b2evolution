<?php
/**
 * This is the lost password form, from where the user can request
 * a set-password-link to be sent to his/her email address.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package htsrv
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Include page header:
 */
$page_title = T_('Lost password ?');
$page_icon = 'icon_login.gif';
require dirname(__FILE__).'/_header.php';

Log::display( '', '', T_('A link to change your password will be sent to you by email.'), 'note' );


$Form = & new Form( $htsrv_url.'login.php', '', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

$Form->hidden( 'action', 'retrievepassword' );
$Form->hidden( 'redirect_to', $redirect_to );

$Form->begin_fieldset();
$Form->text( 'login', $login, 16, T_('Login'), '', 20 , 'input_text' );

echo $Form->fieldstart.$Form->inputstart;
$Form->submit_input( array( /* TRANS: Text for submit button to request an activation link by email */ 'value' => T_('Request email!'), 'class' => 'ActionButton' ) );
echo $Form->inputend.$Form->fieldend;

$Form->end_fieldset();;

$Form->end_form();

require dirname(__FILE__).'/_footer.php';
?>