<?php 
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This is the registration form
 */

	$page_title = T_('Registration Currently Disabled');
	require(dirname(__FILE__).'/_header.php'); 
?>
<p><?php echo T_('User registration is currently not allowed.') ?></p>
<p><a href="<?php echo $baseurl ?>" ><?php echo T_('Home') ?></a></p>
<?php 
	require(dirname(__FILE__).'/_footer.php'); 
?>