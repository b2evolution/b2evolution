<?php
/**
 * This page displays an error message if the config is not done yet.
 *
 * VERY IMPORTANT: this file should assume AS LITTLE AS POSSIBLE
 * on what configuration is already done or not
 * 
 * Before calling this page, you must set:
 * - $error_message
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evocore
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>b2evolution is not configured yet</title>
	</head>
<body>
	<div style="background-color:#fee; border: 1px solid red; text-align:center; ">
		<p>This is b2evolution version <?php global $app_version; echo $app_version ?>.</p>
		<p><strong>You cannot use the application before you finish configuration and installation.</strong></p>
		<?php echo $error_message; ?>
		<p>Please use the installer to finish your configuration/installation now.</p>
		<p>On most installations, the installer will probably be either <a href="install/">here</a> or <a href="../install/">here</a>... (but I can't be sure since I have no config info available! :P)</p>
	</div>
</body>
</html>
<?php
 	exit;
?>