<?php 
/**
 * This is the registration form when disabled
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
$page_title = T_('Registration Currently Disabled');
require(dirname(__FILE__).'/_header.php'); 
?>
<p><?php echo T_('User registration is currently not allowed.') ?></p>
<p><a href="<?php echo $baseurl ?>" ><?php echo T_('Home') ?></a></p>
<?php 
	require(dirname(__FILE__).'/_footer.php'); 
?>