<?php
/**
 * This is displayed when registration is complete
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
$page_title = T_('Registration complete');
$page_icon = 'icon_register.gif';
require dirname(__FILE__).'/_header.php';


$Form =& new Form( $htsrv_url.'login.php', 'login', 'post', 'fieldset' );
$Form->hidden( 'login', $yourname );
$Form->hidden( 'redirect_to', $redirect_to );

$Form->begin_form( 'fform' );

$Form->fieldset();
$Form->info( T_('Login'), $yourname );
$Form->info( T_('Email'), $email );
$Form->fieldset_end();

$Form->fieldset( '', 'submit' );
$Form->submit( array( '', T_('Log in!'), 'ActionButton' ) );
$Form->fieldset_end();

$Form->end_form();

require dirname(__FILE__).'/_footer.php';
?>