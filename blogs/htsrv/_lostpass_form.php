<?php
/**
 * This is the lost password form
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package htsrv
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


/**
 * Include page header:
 */
$page_title = T_('Lost password ?');
$page_icon = 'icon_login.gif';
require(dirname(__FILE__).'/_header.php'); 
?>
<p><?php echo T_('A new password will be generated and sent to you by email.') ?></p>

<?php

	$Form = & new Form( $htsrv_url, '', 'post', 'fieldset' );
	
	$Form->begin_form( 'fform' );
	
	$Form->hidden( 'action', 'retrievepassword' );
	$Form->hidden( 'redirect_to', $redirect_to );
	
	echo $Form->fieldstart;
	$Form->text( 'log', '', 16, T_('Login'), '', 20 , 'large' );
	
	echo $Form->fieldstart;
	echo $Form->inputstart;
	$Form->submit( array( 'submit', T_('Generate new password!'), 'search' ) );
	echo $Form->inputend;
	echo $Form->fieldend;
	
	echo $Form->fieldend;
	
	$Form->end_form();

	require(dirname(__FILE__).'/_footer.php'); 
?>