<?php
/**
 * This is displayed when activation email sent
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
$page_title = T_('Awaiting confirmation');
$page_icon = 'icon_register.gif';
require dirname(__FILE__).'/_header.php';

echo sprintf(T_('Thankyou %s, an activation email has been sent to ') , $yourname ).$email;

require dirname(__FILE__).'/_footer.php';
?>