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
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $app_name;
global $app_version;

@header('Content-Type: text/html; charset=iso-8859-1');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php echo $app_name ?> is not configured yet</title>
	</head>
<body>
	<div style="background-color:#fee; border: 1px solid red; text-align:center; ">
		<p>This is <?php echo $app_name ?> version <?php echo $app_version ?>.</p>
		<p><strong>You cannot use the application before you finish configuration and installation.</strong></p>
		<div style="font-weight:bold; color:red;"><?php echo $error_message; ?></div>
		<p>Please use the installer to finish your configuration/installation now.</p>
		<p>On most installations, the installer will probably be either <a href="install/index.php">here</a> or <a href="../install/index.php">here</a>... (but I can't be sure since I have no config info available! :P)</p>
	</div>
</body>
</html>
<?php
 	exit(0);
?>