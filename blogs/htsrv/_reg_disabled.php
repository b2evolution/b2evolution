<?php
/**
 * This is the registration form when disabled
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
$page_title = T_('Registration Currently Disabled');
require dirname(__FILE__).'/_header.php';

Log::display( '', '', T_('User registration is currently not allowed.'), 'error' );

?>
<p class="center">
	<a href="<?php echo $baseurl ?>" ><?php echo T_('Home') ?></a>
</p>

<?php
require dirname(__FILE__).'/_footer.php';
?>