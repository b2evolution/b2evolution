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
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Include page header:
 */
$page_title = T_('Lost password ?');
$page_icon = 'icon_login.gif';
require(dirname(__FILE__).'/_header.php');

Log::display( '', '', T_('A link to change your password will be sent to you by email.'), 'note' );


$Form = & new Form( $htsrv_url.'login.php', '', 'post', 'fieldset' );

$Form->begin_form( 'fform' );

$Form->hidden( 'action', 'retrievepassword' );
$Form->hidden( 'login', $login );
$Form->hidden( 'redirect_to', $redirect_to );

echo $Form->fieldstart;
$Form->text( 'login', '', 16, T_('Login'), '', 20 , 'large' );

echo $Form->fieldstart;
echo $Form->inputstart;
$Form->submit( array( '', T_('Send email to change your password!'), 'ActionButton' ) );
echo $Form->inputend;
echo $Form->fieldend;

echo $Form->fieldend;

$Form->end_form();

require(dirname(__FILE__).'/_footer.php');
?>