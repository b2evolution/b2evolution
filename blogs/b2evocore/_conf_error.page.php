<?php
/**
 * This page displays an error message if the config is not done yet.
 *
 * VERY IMPORTANT: this file should assume AS LITTLE AS POSSIBLE on what configuration is already done or not
 * 
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package b2evocore
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
		<p>This is b2evolution version <?php global $b2_version; echo $b2_version ?>.</p>
		<p><strong>You cannot use the application before you finish configuration and installation.</strong></p>
		<p><?php echo $error_message; ?></p>
		<p>Please go to <a href="<?php global $core_dirout, $install_subdir; echo $core_dirout.'/'.$install_subdir.'/' ?>">/blogs/install</a> to finish your configuration/installation now.</p>
	</div>
</body>
</html>
<?php
 	exit;
?>