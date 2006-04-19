<?php
/**
 * This page displays an error message if the user is denied access to the admin section
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title><?php echo T_('Access denied') ?></title>
	</head>
<body>
	<div style="background-color:#fee; border: 1px solid red; text-align:center;">
		<h1><?php echo T_('Access denied') ?></h1>
		<p><?php echo T_('Sorry, you have no permission to access this section.') ?></p>
	</div>
	<p style="text-align:center;"><?php
		echo '<a href="'.$htsrv_url.'login.php?action=logout">'.T_('Logout').'</a>
						&bull;
						<a href="'.$baseurl.'">'.T_('Exit to blogs').'</a>';
	?></p>
	<?php debug_info(); ?>
</body>
</html>
<?php
 	exit;
?>