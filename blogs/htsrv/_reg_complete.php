<?php 
/**
 * This is displayed when registration is complete
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package htsrv
 */

/**
 * Include page header:
 */
$page_title = T_('Registration complete');
$page_icon = 'icon_register.gif';
require(dirname(__FILE__).'/_header.php'); 
?>
<p><?php echo T_('Login:') ?> <strong><?php echo $login ?>&nbsp;</strong></p>
<p><?php echo T_('Email') ?>: <strong><?php echo $email ?>&nbsp;</strong></p>

<?php

	$Form =& new Form( $htsrv_url, 'login' );
	
	$Form->begin_form( '' );
	
	$Form->hidden( 'log', $login );
	$Form->hidden( 'redirect_to', $redirect_to );
	$Form->submit( 'submit', T_('Log in!'), 'search' );
	
	$Form->end_form();

<?php 
	require(dirname(__FILE__).'/_footer.php'); 
?>