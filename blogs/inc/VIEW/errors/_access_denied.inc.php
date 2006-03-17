<?php
/**
 * This page displays an error message if the user is denied access to the admin section
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id$
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
	<p style="text-align:center;"><?php echo $app_exit_links ?></p>

	<?php debug_info(); ?>
</body>
</html>
<?php
 	exit;
?>